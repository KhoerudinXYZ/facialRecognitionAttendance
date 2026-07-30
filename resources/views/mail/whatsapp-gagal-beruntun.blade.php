<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #1f2937; line-height: 1.6;">
    <p>Yth. Admin,</p>

    <p>
        {{ $jumlahGagalBeruntun }} notifikasi WhatsApp berturut-turut gagal terkirim.
        Kemungkinan besar device Fonnte terputus (perlu scan ulang QR) atau token
        Fonnte bermasalah.
    </p>

    @if ($alasanTerakhir)
        <p style="background: #fef3c7; padding: 12px 16px; border-radius: 8px;">
            Alasan gagal terakhir dari Fonnte: <strong>{{ $alasanTerakhir }}</strong>
        </p>
    @endif

    <p>
        Selama ini belum diperbaiki, orang tua siswa <strong>tidak menerima peringatan
        belum hadir maupun alpha</strong> lewat WhatsApp.
    </p>

    <p>
        <a href="{{ route('notifikasi-absensi.index') }}">Cek log notifikasi</a>
        untuk detail, lalu periksa status device di dashboard Fonnte.
    </p>

    <p style="color: #6b7280; font-size: 12px; margin-top: 24px;">
        Pesan ini dikirim otomatis oleh sistem absensi sekolah dan tidak akan
        dikirim ulang sampai satu pengiriman WhatsApp berhasil lagi.
    </p>
</body>
</html>
