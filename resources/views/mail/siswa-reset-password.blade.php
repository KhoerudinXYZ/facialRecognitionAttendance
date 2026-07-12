<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #1f2937; line-height: 1.6;">
    <p>Halo,</p>

    <p>
        Ada permintaan reset password untuk akun portal siswa atas nama
        <strong>{{ $siswaNama }}</strong>. Klik tombol di bawah untuk membuat password baru:
    </p>

    <p>
        <a href="{{ $resetUrl }}"
           style="display:inline-block; padding:10px 20px; background:#4f46e5; color:#fff; text-decoration:none; border-radius:6px;">
            Reset Password
        </a>
    </p>

    <p style="font-size: 13px; color: #6b7280;">
        Kalau tombol di atas tidak bisa diklik, salin tautan ini ke browser:<br>
        <span style="word-break: break-all;">{{ $resetUrl }}</span>
    </p>

    <p>Tautan ini berlaku selama 60 menit. Kalau kamu tidak meminta reset password, abaikan saja email ini.</p>

    <p style="color: #6b7280; font-size: 12px; margin-top: 24px;">
        Pesan ini dikirim otomatis oleh sistem absensi sekolah.
    </p>
</body>
</html>
