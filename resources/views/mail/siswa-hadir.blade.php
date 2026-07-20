<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #1f2937; line-height: 1.6;">
    <p>Yth. Orang Tua/Wali dari <strong>{{ $siswaNama }}</strong>,</p>

    <p>
        Kami informasikan ananda sudah tiba di sekolah pada
        <strong>{{ $waktu->format('d/m/Y H:i') }}</strong>
        ({{ $status === 'terlambat' ? 'terlambat' : 'tepat waktu' }}).
    </p>

    <p style="color: #6b7280; font-size: 12px; margin-top: 24px;">
        Pesan ini dikirim otomatis oleh sistem absensi sekolah.
    </p>
</body>
</html>
