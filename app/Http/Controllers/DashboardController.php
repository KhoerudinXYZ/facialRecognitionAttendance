<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        // Pakai jam simulasi (kalau aktif) supaya "hari ini" di dashboard
        // konsisten dengan seluruh portal siswa & halaman admin lain.
        $today = Pengaturan::sekarang()->startOfDay();
        $isLibur = HariLibur::isLibur($today);
        $user = $request->user();

        $totalSiswa = Siswa::where('is_active', true)->visibleTo($user)->count();
        $totalKelas = Kelas::visibleTo($user)->count();
        $sudahEnroll = Siswa::whereHas('faceDescriptors')->visibleTo($user)->count();

        $absensiHariIni = Absensi::whereDate('tanggal', $today)->visibleTo($user);
        $hadir = (clone $absensiHariIni)->whereIn('status', ['hadir', 'terlambat'])->count();
        $terlambat = (clone $absensiHariIni)->where('status', 'terlambat')->count();
        $izinSakit = (clone $absensiHariIni)->whereIn('status', ['izin', 'sakit'])->count();

        $absenTerbaru = Absensi::with(['siswa', 'kelas.waliKelas'])
            ->whereDate('tanggal', $today)
            ->visibleTo($user)
            ->latest('jam_masuk')
            ->take(10)
            ->get();

        $tujuhHariLalu = $today->copy()->subDays(6);
        $absensi7Hari = Absensi::whereBetween('tanggal', [$tujuhHariLalu->toDateString(), $today->toDateString()])
            ->visibleTo($user)
            ->get()
            ->groupBy(fn (Absensi $a) => $a->tanggal->toDateString());

        $labelHari = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        $tren7Hari = collect(range(0, 6))->map(function (int $i) use ($tujuhHariLalu, $absensi7Hari, $labelHari, $today) {
            $tanggal = $tujuhHariLalu->copy()->addDays($i);
            $rows = $absensi7Hari->get($tanggal->toDateString(), collect());

            return [
                'label' => $labelHari[$tanggal->dayOfWeekIso - 1],
                'jumlah' => $rows->whereIn('status', ['hadir', 'terlambat'])->count(),
                'isToday' => $tanggal->isSameDay($today),
            ];
        });

        // Wali kelas mengurus satu (atau beberapa) kelas kecil — "10 absensi
        // terbaru" generik tidak cukup buat mereka lihat siapa yang belum
        // absen. Ganti dengan roster lengkap kelas binaan + status hari ini.
        $kelasBinaan = null;
        $rosterHariIni = null;
        $kelasTanpaWali = 0;
        if ($user->isWaliKelas()) {
            $kelasBinaan = Kelas::where('wali_kelas_id', $user->id)->withCount('siswa')->orderBy('nama_kelas')->get();

            $rosterHariIni = Siswa::whereIn('kelas_id', $kelasBinaan->pluck('id'))
                ->where('is_active', true)
                ->withCount('faceDescriptors')
                ->with(['absensi' => fn ($q) => $q->whereDate('tanggal', $today)])
                ->orderBy('nama')
                ->get();
        } else {
            // Cuma admin yang bisa menugaskan wali kelas, jadi cuma admin
            // yang perlu diberi tahu kalau ada kelas yang belum punya —
            // konsisten dengan peringatan wali kelas di Laporan/Kelas/Staff/Siswa.
            $kelasTanpaWali = Kelas::visibleTo($user)->whereNull('wali_kelas_id')->count();
        }

        return view('dashboard', compact(
            'totalSiswa',
            'totalKelas',
            'sudahEnroll',
            'hadir',
            'terlambat',
            'izinSakit',
            'absenTerbaru',
            'tren7Hari',
            'isLibur',
            'kelasBinaan',
            'rosterHariIni',
            'kelasTanpaWali'
        ));
    }
}
