<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #1f2937; margin: 0; }

        .header { background: #4f46e5; color: #fff; padding: 18px 24px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header .period { font-size: 10px; color: #e0e7ff; margin-top: 3px; }

        .content { padding: 16px 24px; }

        table { width: 100%; border-collapse: collapse; }
        thead th {
            background: #eef2ff;
            color: #3730a3;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 7px 8px;
            border-bottom: 2px solid #c7d2fe;
            text-align: left;
        }
        tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        tbody tr:nth-child(even) { background: #f9fafb; }
        .empty-row td { text-align: center; padding: 16px; color: #6b7280; }
        .wali { display: block; font-size: 8px; color: #9ca3af; }

        .badge {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 8px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-hadir { background: #dcfce7; color: #166534; }
        .badge-terlambat { background: #fef9c3; color: #854d0e; }
        .badge-izin { background: #dbeafe; color: #1e40af; }
        .badge-sakit { background: #f3e8ff; color: #6b21a8; }
        .badge-alpha { background: #fee2e2; color: #991b1b; }

        .footer { margin-top: 10px; font-size: 9px; color: #6b7280; }
        .footer .warn { color: #854d0e; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Laporan Absensi — {{ $pengaturan->nama_sekolah }}</h1>
        <div class="period">Periode: {{ $dari->format('d/m/Y') }} s/d {{ $sampai->format('d/m/Y') }}</div>
    </div>

    <div class="content">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Masuk</th>
                    <th>Pulang</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $i => $a)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $a->tanggal->format('d/m/Y') }}</td>
                        <td>{{ $a->siswa->nis ?? '-' }}</td>
                        <td>{{ $a->siswa->nama ?? '-' }}</td>
                        <td>
                            {{ $a->siswa->kelas->nama_kelas ?? '-' }}
                            @if ($a->siswa->kelas?->waliKelas)
                                <span class="wali">{{ $a->siswa->kelas->waliKelas->name }}</span>
                            @endif
                        </td>
                        <td>{{ \Illuminate\Support\Str::of($a->jam_masuk)->substr(0,5) ?: '-' }}</td>
                        <td>{{ \Illuminate\Support\Str::of($a->jam_pulang)->substr(0,5) ?: '-' }}</td>
                        <td><span class="badge badge-{{ $a->status }}">{{ strtoupper($a->status) }}</span></td>
                        <td>{{ $a->keterangan ?? '-' }}</td>
                    </tr>
                @empty
                    <tr class="empty-row"><td colspan="9">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            Total {{ $data->count() }} baris &middot; Dicetak {{ now()->format('d/m/Y H:i') }}
            @if ($liburDalamPeriode > 0)
                &middot; Termasuk {{ $liburDalamPeriode }} hari libur dalam periode ini
            @endif
            @if ($barisTanpaWali > 0)
                &middot; <span class="warn">{{ $barisTanpaWali }} baris dari kelas yang belum punya wali kelas</span>
            @endif
        </div>
    </div>
</body>
</html>
