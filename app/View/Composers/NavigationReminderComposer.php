<?php

namespace App\View\Composers;

use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Menyiapkan data lonceng notifikasi wali kelas (dipakai di navigasi, jadi
 * harus dihitung sekali per request lewat composer, bukan per controller).
 * Admin tidak dapat lonceng ini — reminder ini spesifik kelas binaan.
 */
class NavigationReminderComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();

        $siswaPerluPerhatian = collect();
        $siswaBelumWajah = collect();

        if ($user && $user->isWaliKelas()) {
            $kelasIds = Kelas::where('wali_kelas_id', $user->id)->pluck('id');

            if ($kelasIds->isNotEmpty()) {
                $today = Pengaturan::sekarang()->startOfDay();

                // Ambil 3 hari sekolah terakhir (bukan hari ini, bukan hari
                // libur) buat mendeteksi siswa yang tidak pernah tercatat
                // hadir/izin/sakit sama sekali di rentang itu.
                $hariSekolahTerakhir = collect();
                $cursor = $today->copy();
                while ($hariSekolahTerakhir->count() < 3 && $cursor->greaterThan($today->copy()->subDays(14))) {
                    $cursor->subDay();
                    if (! HariLibur::isLibur($cursor)) {
                        $hariSekolahTerakhir->push($cursor->toDateString());
                    }
                }

                if ($hariSekolahTerakhir->count() === 3) {
                    $siswaPerluPerhatian = Siswa::whereIn('kelas_id', $kelasIds)
                        ->where('is_active', true)
                        ->whereDoesntHave('absensi', function ($q) use ($hariSekolahTerakhir) {
                            $q->whereIn('tanggal', $hariSekolahTerakhir->all())
                                ->whereIn('status', ['hadir', 'terlambat', 'izin', 'sakit']);
                        })
                        ->orderBy('nama')
                        ->get();
                }

                $siswaBelumWajah = Siswa::whereIn('kelas_id', $kelasIds)
                    ->where('is_active', true)
                    ->doesntHave('faceDescriptors')
                    ->orderBy('nama')
                    ->get();
            }
        }

        $view->with([
            'reminderPerluPerhatian' => $siswaPerluPerhatian,
            'reminderBelumWajah' => $siswaBelumWajah,
        ]);
    }
}
