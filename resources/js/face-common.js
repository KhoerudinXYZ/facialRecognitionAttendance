import * as faceapi from '@vladmandic/face-api';

const MODEL_URL = '/models';

let modelsLoaded = false;

// Versi harus PERSIS sama dengan versi tfjs-backend-wasm yang di-bundle di
// dalam @vladmandic/face-api (dicek dari string versi ter-embed di
// face-api.esm.js) -- glue code JS dan file .wasm binary harus satu versi,
// kalau beda bisa gagal/corrupt.
const TFJS_WASM_VERSION = '4.22.0';

/**
 * Coba paksa backend tfjs ke "wasm" (CPU + SIMD). Beberapa GPU Android
 * kelas bawah punya driver WebGL yang buruk untuk CNN kecil semacam ini
 * (dikonfirmasi: 900ms+/deteksi bahkan setelah warm-up & inputSize kecil di
 * satu device uji) -- wasm kadang lebih cepat di situasi itu. Tidak ada
 * file .wasm yang di-bundle lokal, jadi diambil dari CDN jsdelivr. Gagal
 * dengan aman (fallback tetap ke backend default/webgl) kalau browser tidak
 * dukung atau CDN tidak bisa diakses (mis. jaringan sekolah yang ketat).
 */
async function tryEnableWasmBackend() {
    try {
        const setWasmPaths = faceapi.tf.wasm?.setWasmPaths || faceapi.tf.setWasmPaths;
        if (typeof setWasmPaths !== 'function') {
            console.warn('[face-api] setWasmPaths tidak tersedia di build ini, skip backend wasm.');
            return;
        }
        setWasmPaths(`https://cdn.jsdelivr.net/npm/@tensorflow/tfjs-backend-wasm@${TFJS_WASM_VERSION}/dist/`);
        await faceapi.tf.setBackend('wasm');
        await faceapi.tf.ready();
        console.info('[face-api] backend wasm aktif:', faceapi.tf.getBackend());
    } catch (e) {
        console.warn('[face-api] Gagal pindah ke backend wasm, tetap pakai default:', e);
    }
}

/**
 * Muat model face-api dari public/models (sekali saja).
 */
export async function loadModels() {
    if (modelsLoaded) return;

    await tryEnableWasmBackend();

    await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
    ]);
    modelsLoaded = true;

    // Diagnostik: kalau backend ternyata "cpu" (bukan "webgl"/"wasm"),
    // inference bisa 10-50x lebih lambat -- itu penyebab paling umum kamera
    // ngelag/deteksi lama di device lemah. Cek console browser di HP untuk
    // lihat baris ini (mis. via chrome://inspect dari laptop yang disambung
    // USB ke HP Android, atau lewat panel debug di layar via ?debug=1).
    console.info('[face-api] tfjs backend:', faceapi.tf.getBackend());
}

// inputSize sempat diturunkan bertahap (320 -> 224 -> 160) demi kecepatan
// selagi backend masih webgl (lambat di GPU HP tertentu). Setelah pindah ke
// backend wasm ternyata sudah sangat cepat (170ms di HP lambat, <100ms di
// laptop -- jauh di bawah budget), jadi inputSize kecil sudah tidak perlu
// lagi. Dikembalikan ke 320 karena inputSize kecil bikin kotak wajah yang
// terdeteksi kurang presisi, yang berimbas ke akurasi 68 titik landmark
// (termasuk titik di sekitar mata yang dipakai hitung EAR untuk deteksi
// kedipan) -- itu penyebab paling mungkin kenapa kedipan jadi susah
// terdeteksi setelah inputSize diperkecil.
export function detectorOptions() {
    return new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.4 });
}

/**
 * Nyalakan webcam dan sambungkan ke elemen <video>.
 */
export async function startCamera(videoEl, facingMode = 'user') {
    const stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: facingMode, width: 640, height: 480 },
        audio: false,
    });
    videoEl.srcObject = stream;
    await new Promise((resolve) => {
        videoEl.onloadedmetadata = () => {
            videoEl.play();
            resolve();
        };
    });
    return stream;
}

export function stopCamera(stream) {
    stream?.getTracks().forEach((t) => t.stop());
}

/**
 * Deteksi satu wajah + hitung descriptor 128-d. Null bila tak ada wajah.
 */
export async function getSingleDescriptor(videoEl) {
    const result = await faceapi
        .detectSingleFace(videoEl, detectorOptions())
        .withFaceLandmarks()
        .withFaceDescriptor();
    return result ? result.descriptor : null;
}

export { faceapi };
