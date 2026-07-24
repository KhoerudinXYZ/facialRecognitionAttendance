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
    //
    // Threshold FIXED (pernah dicoba 0.25, 0.28, 0.22) ternyata tidak
    // reliable -- EAR "mata terbuka" itu berbeda-beda tergantung sudut
    // kamera & bentuk mata orangnya, jadi satu angka tetap gampang salah:
    // kalau baseline orang itu di bawah angka tetapnya, mata dianggap
    // "tertutup" terus-menerus (blink tidak pernah terdeteksi karena EAR
    // tidak pernah balik ke atas ambang); kalau di atas, malah gampang
    // ke-trigger cuma dari menyipit. Sekarang threshold dihitung ADAPTIF
    // per sesi: closeRatio * baseline EAR "mata terbuka" orang itu sendiri
    // (recentMax, meluruh perlahan), bukan angka mutlak.
    //
    // closeRatio: 0.93 (butuh EAR turun 7%, sama persis dengan kedipan
    // sengaja yang terekam di device uji) ternyata KEBALIKANNYA jadi
    // masalah -- noise/jitter kamera biasa juga gampang menyentuh 7%,
    // jadi ke-trigger duluan sebelum benar-benar kedip. Dinaikkan sedikit
    // ke 0.87 (butuh turun ~13%) sebagai titik tengah antara sinyal asli
    // (~7%, dulu kelewat longgar) dan 0.75/turun 25% (dulu kelewat ketat,
    // nyaris tidak pernah ke-trigger). Masih tetap lebih longgar dari
    // idealnya karena model landmark di device ini memang kurang sensitif
    // terhadap penutupan mata -- lihat catatan di atas.
    constructor({ closeRatio = 0.87, minEar = 0.15, consecFrames = 1, staleMs = 1500, baselineDecayMs = 4000 } = {}) {
        this.closeRatio = closeRatio;
        this.minEar = minEar;
        this.consecFrames = consecFrames;
        this.staleMs = staleMs;
        this.baselineDecayMs = baselineDecayMs;
        this.sessions = new Map();
    }

    observe(siswaId, landmarks) {
        const now = Date.now();
        const ear = earFor(landmarks);
        let session = this.sessions.get(siswaId);
        if (!session || now - session.lastSeen > this.staleMs) {
            session = { closedStreak: 0, blinked: false, lastSeen: now, baseline: ear, baselineAt: now };
        }
        session.lastSeen = now;

        // baseline dianggap basi setelah baselineDecayMs supaya tracker ikut
        // menyesuaikan kalau sudut wajah/kamera berubah selama sesi, bukan
        // terjebak di baseline lama.
        if (ear > session.baseline || now - session.baselineAt > this.baselineDecayMs) {
            session.baseline = ear;
            session.baselineAt = now;
        }

        const threshold = Math.max(this.minEar, session.baseline * this.closeRatio);

        if (ear < threshold) {
            session.closedStreak += 1;
        } else {
            if (session.closedStreak >= this.consecFrames) {
                session.blinked = true;
            }
            session.closedStreak = 0;
        }

        session.lastEar = ear;
        session.lastThreshold = threshold;
        this.sessions.set(siswaId, session);
        return session.blinked;
    }

    // Untuk panel diagnostik (?debug=1 di face-kiosk.js) -- lihat EAR &
    // threshold adaptif yang sedang dipakai tanpa perlu nebak dari log.
    getDebugInfo(siswaId) {
        const session = this.sessions.get(siswaId);
        if (!session) return null;
        return { ear: session.lastEar, threshold: session.lastThreshold, baseline: session.baseline };
    }

    reset(siswaId) {
        this.sessions.delete(siswaId);
    }
}
