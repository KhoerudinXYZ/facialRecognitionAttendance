<!DOCTYPE html>
<html>
<body style="font-family: sans-serif; color: #1f2937; line-height: 1.6;">
    <p>Yth. Wali Kelas,</p>

    <p>
        <strong>{{ $siswaNama }}</strong> ({{ $kelasNama }}) mengajukan
        <strong>{{ $jenis }}</strong> untuk tanggal <strong>{{ $tanggal->format('d/m/Y') }}</strong>
        dengan keterangan: "{{ $keterangan }}".
    </p>

    <p>Mohon ditinjau dan disetujui/ditolak sebelum jam pulang, supaya siswa tidak salah tercatat alpha.</p>

    <p>
        <a href="{{ route('pengajuan-izin.index') }}">Tinjau pengajuan</a>
    </p>

    <p style="color: #6b7280; font-size: 12px; margin-top: 24px;">
        Pesan ini dikirim otomatis oleh sistem absensi sekolah.
    </p>
</body>
</html>
