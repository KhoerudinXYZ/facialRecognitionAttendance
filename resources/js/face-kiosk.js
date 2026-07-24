import { faceapi, loadModels, startCamera, detectorOptions } from './face-common.js';
import { BlinkTracker } from './face-liveness.js';

const MATCH_THRESHOLD = 0.5; // jarak Euclidean maksimum untuk dianggap cocok
const COOLDOWN_MS = 15000;   // jeda sebelum siswa yang sama bisa memicu absen lagi

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('kiosk-app');
    if (!root) return;

    const video = document.getElementById('kiosk-video');
    const canvas = document.getElementById('kiosk-overlay');
    const statusEl = document.getElementById('kiosk-status');
    const toastEl = document.getElementById('kiosk-toast');
    const switchCameraBtn = document.getElementById('switch-camera-btn');
    const ringIdle = document.getElementById('kiosk-ring-idle');
    const ringScanning = document.getElementById('kiosk-ring-scanning');
    const successEl = document.getElementById('kiosk-success');

    // Panel diagnostik on-screen, aktif hanya lewat ?debug=1 di URL --
    // supaya siswa yang absen normal tidak lihat teks debug ini, tapi kita
    // bisa baca backend tfjs & durasi inference langsung dari layar HP
    // tanpa perlu USB debugging/remote inspect.
    const debugMode = new URLSearchParams(window.location.search).get('debug') === '1';
    let debugEl = null;
    if (debugMode) {
        debugEl = document.createElement('div');
        debugEl.style.cssText =
            'position:fixed;left:8px;bottom:8px;z-index:9999;background:rgba(0,0,0,0.75);' +
            'color:#0f0;font:11px monospace;padding:6px 8px;border-radius:6px;white-space:pre;pointer-events:none;';
        debugEl.textContent = 'debug: menyiapkan…';
        document.body.appendChild(debugEl);
    }

    const storeUrl = root.dataset.storeUrl;
    const dashboardUrl = root.dataset.dashboardUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const labeled = JSON.parse(root.dataset.labeled || '[]');
    const lokasiAktif = root.dataset.lokasiAktif === '1';
    const kameraTerkunci = root.dataset.kameraTerkunci === '1';

    // Peta id -> nama untuk menampilkan label
    const namaMap = {};
    let faceMatcher = null;
    const recentlyRecorded = new Map(); // siswa_id -> timestamp
    const blinkTracker = new BlinkTracker();
    let recording = false;
    let redirecting = false;
    let cameraStream = null;
    let currentFacingMode = 'user';

    // currentPosition di-cache sekali per sesi halaman (bukan watchPosition)
    // supaya tidak terus-menerus minta lokasi device selama kamera aktif.
    let currentPosition = null;
    let geoStatus = lokasiAktif ? 'pending' : 'off'; // pending|ok|denied|unsupported|off

    function requestLocation() {
        if (!lokasiAktif) return;

        if (!navigator.geolocation) {
            geoStatus = 'unsupported';
            setStatus('Browser tidak mendukung lokasi GPS. Hubungi admin untuk absen manual.');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                currentPosition = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                geoStatus = 'ok';
            },
            (err) => {
                // code 1 = PERMISSION_DENIED, 2 = POSITION_UNAVAILABLE,
                // 3 = TIMEOUT -- sebelumnya ketiganya ditampilkan sebagai
                // "izin ditolak", padahal timeout (sinyal GPS lemah di
                // dalam ruangan) atau unavailable itu wajar & bisa dicoba
                // ulang, bukan masalah izin browser yang butuh reload.
                if (err.code === err.PERMISSION_DENIED) {
                    geoStatus = 'denied';
                    setStatus('Izin lokasi ditolak. Aktifkan izin lokasi di browser lalu muat ulang halaman.');
                    return;
                }

                geoStatus = 'pending';
                setStatus('Sinyal GPS lemah, mencoba lagi…');
                setTimeout(requestLocation, 3000);
            },
            { enableHighAccuracy: true, timeout: 15000 }
        );
    }

    function setStatus(msg) {
        statusEl.textContent = msg;
    }

    function setVisualState(state) {
        ringIdle.classList.toggle('hidden', state !== 'idle');
        ringScanning.classList.toggle('hidden', state !== 'scanning');
        if (state === 'success') {
            successEl.classList.remove('hidden');
            successEl.classList.add('flex');
            requestAnimationFrame(() => successEl.classList.replace('scale-0', 'scale-100'));
        } else {
            successEl.classList.add('hidden');
            successEl.classList.remove('flex');
            successEl.classList.replace('scale-100', 'scale-0');
        }
    }

    function showToast(message, ok = true) {
        toastEl.textContent = message;
        toastEl.className =
            'fixed bottom-24 left-1/2 -translate-x-1/2 z-50 px-6 py-3 rounded-lg shadow-lg text-white text-lg font-medium transition ' +
            (ok ? 'bg-green-600' : 'bg-yellow-600');
        toastEl.style.opacity = '1';
        setTimeout(() => (toastEl.style.opacity = '0'), 3000);
    }

    function buildMatcher() {
        const labeledDescriptors = labeled
            .map((item) => {
                namaMap[item.siswa_id] = item.label;
                const descs = (item.descriptors || [])
                    .filter((d) => Array.isArray(d) && d.length === 128)
                    .map((d) => new Float32Array(d));
                if (descs.length === 0) return null;
                return new faceapi.LabeledFaceDescriptors(String(item.siswa_id), descs);
            })
            .filter(Boolean);

        if (labeledDescriptors.length === 0) return null;
        return new faceapi.FaceMatcher(labeledDescriptors, MATCH_THRESHOLD);
    }

    async function recordAttendance(siswaId) {
        recording = true;
        setVisualState('scanning');
        try {
            const payload = { siswa_id: siswaId, liveness_verified: true };
            if (currentPosition) {
                payload.lat = currentPosition.lat;
                payload.lng = currentPosition.lng;
            }

            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify(payload),
            });

            // res.ok gagal (mis. 419 token kedaluwarsa, 500) tidak punya
            // body {status: ...} yang dikenali branch di bawah -- tanpa ini,
            // tidak ada satu pun branch yang jalan, ring scanning macet
            // selamanya sampai reload manual walau deteksi tetap lanjut
            // diam-diam di background.
            if (!res.ok) {
                showToast('⚠ Gagal menyimpan absen, coba lagi.', false);
                setVisualState('idle');
                return;
            }

            const data = await res.json();
            if (data.status === 'success') {
                showToast('✔ ' + data.message, true);
                setVisualState('success');
                setStatus(data.message);
                redirecting = true;
                setTimeout(() => {
                    window.location.href = dashboardUrl;
                }, 1800);
            } else if (data.status === 'already' || data.status === 'libur' || data.status === 'lokasi' || data.status === 'tutup') {
                showToast('ℹ ' + data.message, false);
                setVisualState('idle');
            } else {
                showToast('⚠ Respons tidak dikenali, coba lagi.', false);
                setVisualState('idle');
            }
            recentlyRecorded.set(siswaId, Date.now());
        } catch (e) {
            console.error(e);
            showToast('⚠ Gagal menyimpan absen, coba lagi.', false);
            setVisualState('idle');
        } finally {
            recording = false;
            blinkTracker.reset(siswaId);
        }
    }

    const SIMPLE_DETECT_INTERVAL_MS = 100; // ~box overlay refresh rate, jauh di bawah kecepatan RAF (60fps+)
    // Diturunkan dari 250ms setelah pindah ke backend wasm (~170ms/deteksi
    // di HP paling lambat yang dites, <100ms di laptop) -- sampling lebih
    // rapat = makin besar peluang menangkap frame tepat saat mata tertutup
    // (kedipan asli cuma ~100-400ms). "await" di bawah tetap jadi batas
    // alami kalau device lebih lambat dari nilai ini, jadi aman dinaikkan.
    const FULL_DETECT_INTERVAL_MS = 120;

    // Wajah harus mengisi minimal ~22% lebar frame kamera supaya area mata
    // cukup detail untuk EAR yang reliable -- lihat komentar di bawah pada
    // pemakaiannya.
    const MIN_FACE_WIDTH_RATIO = 0.22;

    let lastSimpleTime = 0;
    let lastRecognitionTime = 0;
    let cachedSimpleDetections = [];
    let cachedDetectionsWithDescriptors = [];

    // Diagnostik durasi inference (deteksi berat), di-log paling sering
    // sekali per detik supaya console tidak banjir -- dipakai untuk cek
    // apakah device tertentu (mis. HP) memang lambat secara inheren di
    // tiap forward pass, bukan cuma soal frekuensi loop.
    let lastTimingLog = 0;
    function logDetectTiming(ms) {
        const now = Date.now();
        if (now - lastTimingLog < 1000) return;
        lastTimingLog = now;
        const msg = `deteksi penuh: ${ms.toFixed(0)}ms`;
        console.info('[face-api] ' + msg);
        if (debugEl) {
            debugEl.textContent = `backend: ${faceapi.tf.getBackend()}\n${msg}`;
        }
    }

    async function loop() {
        if (redirecting) return;

        if (video.paused || video.ended) {
            return requestAnimationFrame(loop);
        }

        const now = Date.now();
        const ctx = canvas.getContext('2d');
        const dims = faceapi.matchDimensions(canvas, video, true);

        // Sebelumnya deteksi ringan berjalan di SETIAP tick RAF (bisa 60x/detik)
        // tanpa throttle, dan saat deteksi berat jalan, itu memicu forward pass
        // TinyFaceDetector KEDUA di frame yang sama (deteksi ringan + berat
        // dobel). Di CPU/GPU HP yang lemah ini bikin main thread selalu sibuk
        // menjalankan inference tanpa jeda -- kamera & deteksi jadi lag berat.
        // Sekarang: hanya SATU forward pass per siklus, masing-masing
        // digerbang oleh interval waktunya sendiri; tick di antaranya cukup
        // gambar ulang hasil cache tanpa inference baru.
        //
        // Deteksi berat (landmark+descriptor) juga cuma boleh jalan kalau
        // deteksi ringan terakhir memang menemukan wajah -- sebelumnya ini
        // jalan tiap 250ms TANPA SYARAT, jadi tetap membebani GPU walau
        // kamera kosong (tidak ada orang di depannya).
        const hasRecentFace = cachedSimpleDetections.length > 0;
        if (hasRecentFace && now - lastRecognitionTime > FULL_DETECT_INTERVAL_MS) {
            lastRecognitionTime = now;
            lastSimpleTime = now;
            const t0 = performance.now();
            cachedDetectionsWithDescriptors = await faceapi
                .detectAllFaces(video, detectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptors();
            logDetectTiming(performance.now() - t0);
            cachedSimpleDetections = cachedDetectionsWithDescriptors.map((d) => d.detection);
        } else if (now - lastSimpleTime > SIMPLE_DETECT_INTERVAL_MS) {
            lastSimpleTime = now;
            cachedSimpleDetections = await faceapi.detectAllFaces(video, detectorOptions());
        }

        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const resizedSimple = faceapi.resizeResults(cachedSimpleDetections, dims);
        const resizedFull = faceapi.resizeResults(cachedDetectionsWithDescriptors, dims);

        // 3. Gambar hasil deteksi visual
        for (const det of resizedSimple) {
            // Cari descriptor terdekat dari cache deteksi penuh berdasarkan posisi kotak wajah
            const matchedFullDet = resizedFull.find(fullDet => {
                const boxA = det.box || det.relativeBox;
                const boxB = fullDet.detection.box || fullDet.detection.relativeBox;
                if (!boxA || !boxB) return false;
                // Hitung irisan kotak (intersection over union) sederhana
                const dx = Math.abs(boxA.x - boxB.x);
                const dy = Math.abs(boxA.y - boxB.y);
                return dx < 50 && dy < 50;
            });

            let nama = 'Mendeteksi...';
            let isMatch = false;
            let best = null;

            if (matchedFullDet) {
                best = faceMatcher.findBestMatch(matchedFullDet.descriptor);
                isMatch = best.label !== 'unknown';
                nama = isMatch ? (namaMap[best.label] || 'Dikenali') : 'Tidak dikenal';
            }

            // Video di-mirror lewat CSS (scale-x-[-1]) untuk tampilan selfie,
            // tapi canvas overlay sengaja TIDAK ikut di-mirror lagi -- kalau
            // canvas ikut di-mirror, label nama yang digambar di atasnya jadi
            // ikut terbalik (teks mirror, tidak terbaca). Sebagai gantinya,
            // posisi X kotak dihitung ulang secara manual di sini supaya
            // kotak tetap pas di wajah pada video yang mirror, sementara
            // teks labelnya tetap digambar normal (tidak terbalik).
            const box = det.box || det;
            const mirroredBox = {
                x: dims.width - box.x - box.width,
                y: box.y,
                width: box.width,
                height: box.height,
            };

            new faceapi.draw.DrawBox(mirroredBox, {
                label: nama,
                boxColor: isMatch ? '#16a34a' : (nama === 'Tidak dikenal' ? '#dc2626' : '#94a3b8'),
            }).draw(canvas);

            if (matchedFullDet && isMatch && !recording && (!lokasiAktif || geoStatus === 'ok')) {
                const siswaId = parseInt(best.label, 10);
                const last = recentlyRecorded.get(siswaId) || 0;
                if (now - last > COOLDOWN_MS) {
                    // Wajah yang terlalu kecil di frame (jauh dari kamera) bikin area
                    // mata cuma beberapa piksel -- landmark 68-titiknya jadi kasar dan
                    // EAR nyaris tidak bergerak walau benar-benar berkedip (dikonfirmasi
                    // di device uji: EAR cuma turun ~7% saat kejauhan, vs jauh lebih
                    // dalam saat wajah dekat kamera). Kalau kejauhan, kasih tahu user
                    // untuk mendekat dulu alih-alih diam-diam gagal terus kedipannya.
                    if (box.width / dims.width < MIN_FACE_WIDTH_RATIO) {
                        setStatus(`${nama} dikenali — dekatkan wajah ke kamera untuk verifikasi kedipan.`);
                    } else if (blinkTracker.observe(siswaId, matchedFullDet.landmarks)) {
                        await recordAttendance(siswaId);
                    } else {
                        setStatus(`${nama} dikenali — kedipkan mata untuk verifikasi kehadiran.`);
                    }
                    if (debugEl) {
                        const bd = blinkTracker.getDebugInfo(siswaId);
                        if (bd) {
                            debugEl.textContent =
                                `backend: ${faceapi.tf.getBackend()}\n` +
                                `EAR: ${bd.ear.toFixed(3)}  threshold: ${bd.threshold.toFixed(3)}  baseline: ${bd.baseline.toFixed(3)}`;
                        }
                    }
                }
            }
        }

        requestAnimationFrame(loop);
    }

    async function init() {
        try {
            setStatus('Memuat model…');
            await loadModels();

            faceMatcher = buildMatcher();
            if (!faceMatcher) {
                setStatus('Belum ada siswa dengan wajah terdaftar. Daftarkan wajah siswa terlebih dahulu.');
                return;
            }

            if (kameraTerkunci || !video) {
                setStatus('Kamera terkunci. Harap tunggu waktu yang ditentukan.');
                return;
            }

            setStatus('Menyalakan kamera…');
            requestLocation();
            cameraStream = await startCamera(video, currentFacingMode);
            
            if (switchCameraBtn) {
                switchCameraBtn.addEventListener('click', async () => {
                    switchCameraBtn.classList.add('opacity-50', 'pointer-events-none');
                    if (cameraStream) {
                        cameraStream.getTracks().forEach((t) => t.stop());
                    }
                    currentFacingMode = currentFacingMode === 'user' ? 'environment' : 'user';
                    try {
                        cameraStream = await startCamera(video, currentFacingMode);
                    } catch (e) {
                        console.error('Gagal mengganti kamera:', e);
                        showToast('Gagal mengganti kamera', false);
                    }
                    switchCameraBtn.classList.remove('opacity-50', 'pointer-events-none');
                });
            }

            // Warm-up: forward pass PERTAMA di backend webgl jauh lebih lambat
            // dari yang berikutnya (browser compile shader GPU sekali di awal
            // -- bisa makan beberapa detik di HP). Kalau tidak di-warm-up di
            // sini, biaya itu jatuh ke iterasi pertama loop() sungguhan, yaitu
            // pas siswa sudah di depan kamera coba absen -- kerasa seperti
            // "ngelag lama". Jalankan sekali di layar loading supaya siswa
            // cuma nunggu di status "menyiapkan", bukan pas lagi discan.
            setStatus('Menyiapkan pengenalan wajah…');
            try {
                const t0 = performance.now();
                await faceapi.detectAllFaces(video, detectorOptions()).withFaceLandmarks().withFaceDescriptors();
                const warmupMs = performance.now() - t0;
                console.info(`[face-api] warm-up: ${warmupMs.toFixed(0)}ms`);
                if (debugEl) debugEl.textContent = `backend: ${faceapi.tf.getBackend()}\nwarm-up: ${warmupMs.toFixed(0)}ms`;
            } catch (e) {
                console.warn('Warm-up deteksi gagal (dilanjutkan tanpa warm-up):', e);
            }

            setStatus('Kamera aktif. Arahkan wajah ke kamera untuk absen otomatis.');
            requestAnimationFrame(loop);
        } catch (e) {
            console.error(e);
            setStatus('Gagal memulai kiosk: ' + e.message);
        }
    }

    init();
});
