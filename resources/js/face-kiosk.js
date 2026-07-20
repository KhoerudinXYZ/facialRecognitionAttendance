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
    const ringIdle = document.getElementById('kiosk-ring-idle');
    const ringScanning = document.getElementById('kiosk-ring-scanning');
    const successEl = document.getElementById('kiosk-success');

    const storeUrl = root.dataset.storeUrl;
    const dashboardUrl = root.dataset.dashboardUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const labeled = JSON.parse(root.dataset.labeled || '[]');
    const lokasiAktif = root.dataset.lokasiAktif === '1';

    // Peta id -> nama untuk menampilkan label
    const namaMap = {};
    let faceMatcher = null;
    const recentlyRecorded = new Map(); // siswa_id -> timestamp
    const blinkTracker = new BlinkTracker();
    let recording = false;
    let redirecting = false;

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

    async function loop() {
        if (redirecting) return;

        if (video.paused || video.ended) {
            return requestAnimationFrame(loop);
        }

        const detections = await faceapi
            .detectAllFaces(video, detectorOptions())
            .withFaceLandmarks()
            .withFaceDescriptors();

        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        const dims = faceapi.matchDimensions(canvas, video, true);
        const resized = faceapi.resizeResults(detections, dims);

        for (const det of resized) {
            const best = faceMatcher.findBestMatch(det.descriptor);
            const box = det.detection.box;
            const isMatch = best.label !== 'unknown';
            const nama = isMatch ? (namaMap[best.label] || 'Dikenali') : 'Tidak dikenal';

            new faceapi.draw.DrawBox(box, {
                label: nama,
                boxColor: isMatch ? '#16a34a' : '#dc2626',
            }).draw(canvas);

            if (isMatch && !recording && (!lokasiAktif || geoStatus === 'ok')) {
                const siswaId = parseInt(best.label, 10);
                const last = recentlyRecorded.get(siswaId) || 0;
                if (Date.now() - last > COOLDOWN_MS) {
                    if (blinkTracker.observe(siswaId, det.landmarks)) {
                        await recordAttendance(siswaId);
                    } else {
                        setStatus(`${nama} dikenali — kedipkan mata untuk verifikasi kehadiran.`);
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

            setStatus('Menyalakan kamera…');
            requestLocation();
            await startCamera(video);
            setStatus('Kamera aktif. Arahkan wajah ke kamera untuk absen otomatis.');
            requestAnimationFrame(loop);
        } catch (e) {
            console.error(e);
            setStatus('Gagal memulai kiosk: ' + e.message);
        }
    }

    init();
});
