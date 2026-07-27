import { loadModels, startCamera, stopCamera, getSingleDescriptor } from './face-common.js';

const SAMPLE_TARGET = 5; // jumlah sampel wajah yang direkam

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('enroll-app');
    if (!root) return;

    const video = document.getElementById('enroll-video');
    const statusEl = document.getElementById('enroll-status');
    const countEl = document.getElementById('enroll-count');
    const captureBtn = document.getElementById('enroll-capture');
    const saveBtn = document.getElementById('enroll-save');
    const progressBar = document.getElementById('enroll-progress');
    const retryBtn = document.getElementById('enroll-retry-btn');

    const storeUrl = root.dataset.storeUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    const descriptors = [];
    let stream = null;

    function setStatus(msg) {
        statusEl.textContent = msg;
    }

    // Beda dari setStatus() biasa: dipakai khusus saat izin kamera sudah
    // TERLANJUR ditolak siswa. Begitu ditolak sekali, browser tidak akan
    // pernah menampilkan popup izin lagi secara otomatis (sengaja dibuat
    // begitu oleh semua browser, bukan sesuatu yang bisa dipaksa lewat
    // kode) -- jadi satu-satunya jalan siswa yang tidak paham teknis bisa
    // lanjut adalah instruksi jelas + tombol nyata, bukan pesan error
    // mentah yang tidak actionable. Sama seperti face-kiosk.js.
    function setStatusIzinDitolak(msg) {
        setStatus(msg);
        retryBtn?.classList.remove('hidden');
        retryBtn?.classList.add('flex');
    }

    function updateCount() {
        countEl.textContent = `${descriptors.length} / ${SAMPLE_TARGET}`;
        progressBar.style.width = `${Math.min(100, (descriptors.length / SAMPLE_TARGET) * 100)}%`;
        saveBtn.disabled = descriptors.length === 0;
        captureBtn.disabled = descriptors.length >= SAMPLE_TARGET;
        if (descriptors.length >= SAMPLE_TARGET) {
            setStatus('Sampel cukup. Klik "Simpan Wajah".');
        }
    }

    async function init() {
        try {
            setStatus('Memuat model pengenalan wajah…');
            await loadModels();
            setStatus('Menyalakan kamera…');
            try {
                stream = await startCamera(video);
            } catch (camErr) {
                if (camErr.name === 'NotAllowedError' || camErr.name === 'PermissionDeniedError') {
                    setStatusIzinDitolak(
                        'Izin kamera diblokir.\nKetuk ikon gembok/info di sebelah alamat website (atas layar), ' +
                        'pilih "Izin Situs" atau "Site Settings", aktifkan Kamera, lalu tekan Coba Lagi di bawah.'
                    );
                    return;
                }
                if (camErr.name === 'NotFoundError' || camErr.name === 'DevicesNotFoundError') {
                    setStatus('Tidak ada kamera terdeteksi di perangkat ini.');
                    return;
                }
                if (camErr.name === 'NotReadableError' || camErr.name === 'TrackStartError') {
                    setStatusIzinDitolak('Kamera sedang dipakai aplikasi lain. Tutup aplikasi kamera/video call lain, lalu tekan Coba Lagi.');
                    return;
                }
                throw camErr;
            }
            setStatus('Arahkan wajah ke kamera lalu klik "Ambil Sampel".');
            captureBtn.disabled = false;
        } catch (e) {
            console.error(e);
            setStatus('Gagal mengakses kamera / model: ' + e.message);
        }
    }

    captureBtn.addEventListener('click', async () => {
        captureBtn.disabled = true;
        setStatus('Mendeteksi wajah…');
        const descriptor = await getSingleDescriptor(video);
        if (!descriptor) {
            setStatus('Wajah tidak terdeteksi. Coba lagi dengan pencahayaan lebih baik.');
            captureBtn.disabled = false;
            return;
        }
        descriptors.push(Array.from(descriptor));
        updateCount();
        setStatus(`Sampel ${descriptors.length} tersimpan. Ubah sedikit posisi/ekspresi lalu ambil lagi.`);
        captureBtn.disabled = descriptors.length >= SAMPLE_TARGET;
    });

    saveBtn.addEventListener('click', async () => {
        if (descriptors.length === 0) return;
        saveBtn.disabled = true;
        setStatus('Menyimpan ke server…');
        try {
            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ descriptors }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal menyimpan.');
            setStatus(`Berhasil! Total sampel wajah siswa: ${data.total}.`);
            descriptors.length = 0;
            updateCount();
            setTimeout(() => window.location.href = root.dataset.redirectUrl, 1200);
        } catch (e) {
            console.error(e);
            setStatus('Gagal menyimpan: ' + e.message);
            saveBtn.disabled = false;
        }
    });

    window.addEventListener('beforeunload', () => stopCamera(stream));

    init();
});
