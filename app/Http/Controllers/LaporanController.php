<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Pengaturan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        [$dari, $sampai, $kelasId] = $this->periode($request);

        $data = $this->queryLaporan($request, $dari, $sampai, $kelasId)->get();
        $kelasList = Kelas::visibleTo($request->user())->with('waliKelas')->orderBy('nama_kelas')->get();

        $liburDalamPeriode = HariLibur::whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])->count();

        // Baris dari kelas yang belum punya wali kelas — supaya kelihatan
        // kalau ada bagian laporan yang "tidak ada penanggung jawabnya",
        // konsisten dengan peringatan wali kelas di form Kelas/Staff/Siswa.
        $barisTanpaWali = $data->filter(fn (Absensi $a) => ! $a->siswa?->kelas?->waliKelas)->count();

        return view('laporan.index', [
            'data' => $data,
            'dari' => $dari,
            'sampai' => $sampai,
            'kelasId' => $kelasId,
            'kelasList' => $kelasList,
            'liburDalamPeriode' => $liburDalamPeriode,
            'barisTanpaWali' => $barisTanpaWali,
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        [$dari, $sampai, $kelasId] = $this->periode($request);
        $data = $this->queryLaporan($request, $dari, $sampai, $kelasId)->get();
        $liburDalamPeriode = HariLibur::whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])->count();
        $barisTanpaWali = $data->filter(fn (Absensi $a) => ! $a->siswa?->kelas?->waliKelas)->count();

        $filename = 'laporan-absensi-' . $dari->format('Ymd') . '-' . $sampai->format('Ymd') . '.xlsx';

        return response()->streamDownload(function () use ($data, $liburDalamPeriode, $barisTanpaWali) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $headerStyle = new Style(fontBold: true, fontColor: Color::WHITE, backgroundColor: '4F46E5');
            $writer->addRow(Row::fromValuesWithStyle(
                ['Tanggal', 'NIS', 'Nama', 'Kelas', 'Wali Kelas', 'Status', 'Jam Masuk', 'Jam Pulang', 'Metode', 'Keterangan'],
                $headerStyle,
                20
            ));

            // Warna badge status — sama dengan palet yang dipakai di web & laporan PDF.
            $statusStyles = [
                'hadir' => new Style(fontBold: true, fontColor: '166534', backgroundColor: 'DCFCE7'),
                'terlambat' => new Style(fontBold: true, fontColor: '854D0E', backgroundColor: 'FEF9C3'),
                'izin' => new Style(fontBold: true, fontColor: '1E40AF', backgroundColor: 'DBEAFE'),
                'sakit' => new Style(fontBold: true, fontColor: '6B21A8', backgroundColor: 'F3E8FF'),
                'alpha' => new Style(fontBold: true, fontColor: '991B1B', backgroundColor: 'FEE2E2'),
            ];

            foreach ($data as $row) {
                $values = [
                    Carbon::parse($row->tanggal)->format('d/m/Y'),
                    $row->siswa->nis ?? '-',
                    $row->siswa->nama ?? '-',
                    $row->siswa->kelas->nama_kelas ?? '-',
                    $row->siswa->kelas?->waliKelas?->name ?? '-',
                    strtoupper($row->status),
                    $row->jam_masuk ?? '-',
                    $row->jam_pulang ?? '-',
                    strtoupper($row->metode),
                    $row->keterangan ?? '',
                ];

                $statusStyle = $statusStyles[$row->status] ?? null;
                $writer->addRow($statusStyle
                    ? Row::fromValuesWithStyles($values, [5 => $statusStyle])
                    : Row::fromValues($values));
            }

            if ($liburDalamPeriode > 0 || $barisTanpaWali > 0) {
                $writer->addRow(Row::fromValues([]));
            }

            if ($liburDalamPeriode > 0) {
                $writer->addRow(Row::fromValuesWithStyle(
                    ["Termasuk {$liburDalamPeriode} hari libur dalam periode ini."],
                    new Style(fontItalic: true, fontColor: '6B7280')
                ));
            }

            if ($barisTanpaWali > 0) {
                $writer->addRow(Row::fromValuesWithStyle(
                    ["{$barisTanpaWali} baris dari kelas yang belum punya wali kelas."],
                    new Style(fontItalic: true, fontBold: true, fontColor: '854D0E')
                ));
            }

            $sheet = $writer->getCurrentSheet();
            $sheet->setColumnWidth(12, 1);
            $sheet->setColumnWidth(14, 2);
            $sheet->setColumnWidth(24, 3);
            $sheet->setColumnWidth(14, 4);
            $sheet->setColumnWidth(18, 5);
            $sheet->setColumnWidth(12, 6);
            $sheet->setColumnWidth(10, 7);
            $sheet->setColumnWidth(10, 8);
            $sheet->setColumnWidth(10, 9);
            $sheet->setColumnWidth(24, 10);
            $sheet->setSheetView(new SheetView(freezeRow: 2));

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportPdf(Request $request)
    {
        [$dari, $sampai, $kelasId] = $this->periode($request);
        $data = $this->queryLaporan($request, $dari, $sampai, $kelasId)->get();
        $liburDalamPeriode = HariLibur::whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])->count();
        $barisTanpaWali = $data->filter(fn (Absensi $a) => ! $a->siswa?->kelas?->waliKelas)->count();

        $pdf = Pdf::loadView('laporan.pdf', [
            'data' => $data,
            'dari' => $dari,
            'sampai' => $sampai,
            'pengaturan' => Pengaturan::get(),
            'liburDalamPeriode' => $liburDalamPeriode,
            'barisTanpaWali' => $barisTanpaWali,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-absensi-' . $dari->format('Ymd') . '-' . $sampai->format('Ymd') . '.pdf');
    }

    private function periode(Request $request): array
    {
        $dari = $request->filled('dari')
            ? Carbon::parse($request->input('dari'))
            : Pengaturan::sekarang()->startOfMonth();

        $sampai = $request->filled('sampai')
            ? Carbon::parse($request->input('sampai'))
            : Pengaturan::sekarang();

        $kelasId = $request->integer('kelas_id') ?: null;

        return [$dari, $sampai, $kelasId];
    }

    private function queryLaporan(Request $request, Carbon $dari, Carbon $sampai, ?int $kelasId)
    {
        $query = Absensi::with('siswa.kelas.waliKelas')
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->visibleTo($request->user())
            ->orderBy('tanggal');

        if ($kelasId) {
            $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $kelasId));
        }

        return $query;
    }
}
