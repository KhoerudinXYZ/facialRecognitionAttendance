<?php

namespace App\Http\Controllers\SiswaAuth;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SiswaDashboardController extends Controller
{
    public function index(): View
    {
        /** @var Siswa $siswa */
        $siswa = Auth::guard('siswa')->user();
        $siswa->load('kelas', 'faceDescriptors');

        // Pakai jam simulasi (kalau aktif di Pengaturan) supaya konsisten
        // dengan AbsensiRecorder — kalau tidak, dashboard bisa menampilkan
        // tombol absen aktif padahal AbsensiRecorder akan menolaknya.
        $today = Pengaturan::sekarang()->startOfDay();

        $absenHariIni = $siswa->absensi()->whereDate('tanggal', $today)->first();
        $isLibur = HariLibur::isLibur($today);

        $bulanIni = $today->copy()->startOfMonth();
        $absensiBulanIni = $siswa->absensi()
            ->whereBetween('tanggal', [$bulanIni->toDateString(), $today->toDateString()])
            ->get();

        $statistikBulanIni = [
            'hadir' => $absensiBulanIni->whereIn('status', ['hadir', 'terlambat'])->count(),
            'terlambat' => $absensiBulanIni->where('status', 'terlambat')->count(),
            'izinSakit' => $absensiBulanIni->whereIn('status', ['izin', 'sakit'])->count(),
        ];

        $awalMinggu = $today->copy()->startOfWeek(Carbon::MONDAY);
        $absensiMingguIni = $siswa->absensi()
            ->whereBetween('tanggal', [$awalMinggu->toDateString(), $awalMinggu->copy()->endOfWeek(Carbon::SUNDAY)->toDateString()])
            ->get()
            ->keyBy(fn ($a) => $a->tanggal->toDateString());

        $labelHari = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        $mingguIni = collect(range(0, 6))->map(function (int $i) use ($awalMinggu, $absensiMingguIni, $labelHari, $today) {
            $tanggal = $awalMinggu->copy()->addDays($i);

            return [
                'label' => $labelHari[$i],
                'status' => $absensiMingguIni->get($tanggal->toDateString())?->status,
                'isFuture' => $tanggal->isAfter($today),
                'isToday' => $tanggal->isSameDay($today),
            ];
        });

        $pengaturan = Pengaturan::get();
        $now = $pengaturan->waktuSekarang();
        $mulaiPulang = Carbon::parse($today->toDateString() . ' ' . $pengaturan->mulai_pulang);
        $bisaAbsenPulang = $now->greaterThanOrEqualTo($mulaiPulang);

        return view('siswa-auth.dashboard', compact('siswa', 'absenHariIni', 'statistikBulanIni', 'mingguIni', 'isLibur', 'pengaturan', 'bisaAbsenPulang'));
    }
}
