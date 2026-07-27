<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #1f2937; line-height: 1.6;">
    <p>Yth. Wali Kelas,</p>

    <p>
        <strong>{{ $siswaNama }}</strong> ({{ $kelasNama }}) mengajukan koreksi absensi
        untuk tanggal <strong>{{ $tanggal->format('d/m/Y') }}</strong>, minta diubah jadi
        <strong>{{ $statusDiminta }}</strong>, dengan alasan: "{{ $alasan }}".
    </p>

    <p>Mohon ditinjau — kalau disetujui, baris absensi tanggal itu akan langsung diperbarui.</p>

    <p>
        <a href="{{ route('koreksi-absensi.index') }}">Tinjau pengajuan</a>
    </p>

    <p style="color: #6b7280; font-size: 12px; margin-top: 24px;">
        Pesan ini dikirim otomatis oleh sistem absensi sekolah.
    </p>
</body>
</html>
