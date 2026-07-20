import * as faceapi from '@vladmandic/face-api';

const MODEL_URL = '/models';

let modelsLoaded = false;

/**
 * Muat model face-api dari public/models (sekali saja).
 */
export async function loadModels() {
    if (modelsLoaded) return;
    await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
        faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
        faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
    ]);
    modelsLoaded = true;
}

export function detectorOptions() {
    return new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 });
}

/**
 * Nyalakan webcam dan sambungkan ke elemen <video>.
 */
export async function startCamera(videoEl) {
    const stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user', width: 640, height: 480 },
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
