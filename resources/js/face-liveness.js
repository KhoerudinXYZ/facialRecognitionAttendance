/**
 * Deteksi kedipan mata (blink) dari landmark 68-titik wajah yang sudah
 * dihitung face-api.js di setiap frame kiosk (lihat face-kiosk.js) — supaya
 * absensi mandiri tidak bisa dipicu pakai foto/layar statis (foto tidak
 * bisa berkedip). Pakai formula Eye Aspect Ratio (Soukupova & Cech).
 */

function dist(a, b) {
    return Math.hypot(a.x - b.x, a.y - b.y);
}

function earForEye(eye) {
    return (dist(eye[1], eye[5]) + dist(eye[2], eye[4])) / (2 * dist(eye[0], eye[3]));
}

export function earFor(landmarks) {
    return (earForEye(landmarks.getLeftEye()) + earForEye(landmarks.getRightEye())) / 2;
}

/**
 * Satu instance dipakai untuk seluruh sesi kiosk, melacak progres kedipan
 * per siswa_id secara terpisah (beberapa wajah bisa terlihat kamera).
 * `observe()` mengembalikan true persis sekali mata sudah menutup lalu
 * terbuka lagi (pola close->reopen, bukan cuma nilai EAR rendah sesaat)
 * — dipanggil ulang tiap frame sampai true, lalu caller memanggil `reset()`
 * setelah absen berhasil supaya scan berikutnya butuh kedipan baru lagi.
 */
export class BlinkTracker {
    // consecFrames = 1: loop deteksi ini async (deteksi + landmark + descriptor
    // matching per iterasi), jadi kecepatan efektifnya jauh di bawah 60fps —
    // satu kedipan asli (~100-400ms) biasanya cuma sempat kena 1 sampel EAR
    // rendah sebelum mata kebuka lagi. Mensyaratkan 2 sampel berturut-turut
    // (seperti rekomendasi umum untuk video 30-60fps) membuat kedipan nyaris
    // tidak pernah terdeteksi di loop ini.
    constructor({ threshold = 0.25, consecFrames = 1, staleMs = 1500 } = {}) {
        this.threshold = threshold;
        this.consecFrames = consecFrames;
        this.staleMs = staleMs;
        this.sessions = new Map();
    }

    observe(siswaId, landmarks) {
        const now = Date.now();
        let session = this.sessions.get(siswaId);
        if (!session || now - session.lastSeen > this.staleMs) {
            session = { closedStreak: 0, blinked: false, lastSeen: now };
        }
        session.lastSeen = now;

        if (earFor(landmarks) < this.threshold) {
            session.closedStreak += 1;
        } else {
            if (session.closedStreak >= this.consecFrames) {
                session.blinked = true;
            }
            session.closedStreak = 0;
        }

        this.sessions.set(siswaId, session);
        return session.blinked;
    }

    reset(siswaId) {
        this.sessions.delete(siswaId);
    }
}
