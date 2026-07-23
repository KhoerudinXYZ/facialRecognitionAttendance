<?php

namespace App\Http\Controllers\SiswaAuth;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\Pengaturan;
use App\Models\PengajuanIzin;
use App\Models\Siswa;
use App\Services\AbsensiRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SiswaAbsensiController extends Controller
{
    /**
     * Halaman absen mandiri. Hanya mengirim descriptor milik siswa yang
     * login sendiri (bukan seluruh sekolah) supaya biometrik siswa lain
     * tidak pernah terkirim ke HP siswa ini.
     *
     * Kalau hari ini libur atau siswa sudah absen masuk & pulang, kamera
     * tidak perlu dibuka sama sekali — langsung balik ke dashboard.
     */
    public function create(): View|RedirectResponse
    {
        /** @var Siswa $siswa */
        $siswa = Auth::guard('siswa')->user();

        $pengaturan = Pengaturan::get();
        $now = $pengaturan->waktuSekarang();
        $today = $now->copy()->startOfDay();

        if (HariLibur::isLibur($today)) {
            return redirect()->route('siswa.dashboard')->with('info', 'Hari ini libur, absensi tidak aktif.');
        }

        $absenHariIni = $siswa->absensi()->whereDate('tanggal', $today)->first();
        if ($absenHariIni && $absenHariIni->jam_pulang) {
            return redirect()->route('siswa.dashboard')->with('info', 'Kamu sudah absen masuk & pulang hari ini.');
        }

        if ($absenHariIni && in_array($absenHariIni->status, ['izin', 'sakit'], true)) {
            return redirect()->route('siswa.dashboard')->with('info', "Kamu tercatat {$absenHariIni->status} hari ini, absen tidak perlu dilakukan.");
        }

        $kameraTerkunci = false;
        $pesanTerkunci = '';

        $mulaiPulang = Carbon::parse($today->toDateString() . ' ' . $pengaturan->mulai_pulang);

        // Cek apakah siswa punya izin pulang cepat yang sudah disetujui hari ini
        $izinPulangCepat = PengajuanIzin::where('siswa_id', $siswa->id)
            ->whereDate('tanggal', $today)
            ->where('jenis', 'pulang_cepat')
            ->first();

        $izinPulangCepatDisetujui = $izinPulangCepat && $izinPulangCepat->status === 'disetujui';

        // Jika siswa sudah absen masuk (dan belum absen pulang), kamera baru terbuka saat jam pulang
        // KECUALI jika ada izin pulang cepat yang sudah disetujui
        if ($absenHariIni && $absenHariIni->jam_masuk && ! $absenHariIni->jam_pulang && $now->lessThan($mulaiPulang)) {
            if ($izinPulangCepatDisetujui) {
                // Kamera terbuka khusus untuk absen pulang cepat
                $kameraTerkunci = false;
            } else {
                $jamPulang = \Illuminate\Support\Str::of($pengaturan->mulai_pulang)->substr(0, 5);
                $kameraTerkunci = true;
                if ($izinPulangCepat && $izinPulangCepat->status === 'menunggu') {
                    $pesanTerkunci = "Pengajuan izin pulang cepat kamu sedang menunggu persetujuan wali kelas.";
                } else {
                    $pesanTerkunci = "Sudah absen masuk. Kamera untuk absen pulang baru terbuka pukul {$jamPulang}.";
                }
            }
        }

        // Sama seperti gate di AbsensiRecorder: siswa yang belum absen masuk
        // sama sekali (atau masih alpha) tidak perlu buka kamera segala kalau
        // jam absen masuk sudah ditutup — tidak ada yang bisa dicatat.
        if ((! $absenHariIni || $absenHariIni->status === 'alpha') && $now->greaterThanOrEqualTo($mulaiPulang)) {
            $kameraTerkunci = true;
            $pesanTerkunci = "Jam absen masuk sudah ditutup untuk hari ini (mulai {$pengaturan->mulai_pulang}).";
        }

        $siswa->load('faceDescriptors');

        $labeledDescriptors = [[
            'siswa_id'    => $siswa->id,
            'label'       => $siswa->nama,
            'descriptors' => $siswa->faceDescriptors->pluck('descriptor')->all(),
        ]];

        return view('siswa-auth.absen', [
            'siswa'                  => $siswa,
            'labeledDescriptors'     => $labeledDescriptors,
            'pengaturan'             => $pengaturan,
            'kameraTerkunci'         => $kameraTerkunci,
            'pesanTerkunci'          => $pesanTerkunci,
            'izinPulangCepat'        => $izinPulangCepat ?? null,
            'izinPulangCepatDisetujui' => $izinPulangCepatDisetujui ?? false,
        ]);
    }

    /**
     * Catat kehadiran siswa yang sedang login. Identitas diambil dari
     * sesi, bukan dari body request, sehingga siswa tidak bisa absen
     * atas nama siswa lain meski memodifikasi payload di browser.
     */
    public function store(Request $request, AbsensiRecorder $recorder): JsonResponse
    {
        /** @var Siswa $siswa */
        $siswa = Auth::guard('siswa')->user();

        $data = $request->validate([
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'liveness_verified' => ['required', 'accepted'],
        ]);

        return response()->json($recorder->record($siswa, $data['lat'] ?? null, $data['lng'] ?? null, true));
    }

    public function riwayat(Request $request): View
    {
        /** @var Siswa $siswa */
        $siswa = Auth::guard('siswa')->user();

        $bulan = $request->filled('bulan')
            ? Carbon::parse($request->string('bulan') . '-01')
            : Pengaturan::sekarang()->startOfMonth();

        $riwayat = $siswa->absensi()
            ->whereYear('tanggal', $bulan->year)
            ->whereMonth('tanggal', $bulan->month)
            ->orderBy('tanggal', 'desc')
            ->get();

        $statistik = [
            'hadir' => $riwayat->whereIn('status', ['hadir', 'terlambat'])->count(),
            'terlambat' => $riwayat->where('status', 'terlambat')->count(),
            'izinSakit' => $riwayat->whereIn('status', ['izin', 'sakit'])->count(),
        ];

        // Hari libur ikut ditampilkan supaya siswa tahu kenapa ada tanggal
        // yang "bolong" di riwayat (bukan alpha, tapi memang tidak ada sekolah).
        $tanggalAbsen = $riwayat->map(fn ($a) => $a->tanggal->toDateString());
        $liburBulanIni = HariLibur::whereYear('tanggal', $bulan->year)
            ->whereMonth('tanggal', $bulan->month)
            ->get()
            ->reject(fn ($h) => $tanggalAbsen->contains($h->tanggal->toDateString()));

        $riwayatGabungan = $riwayat->map(fn ($a) => ['tanggal' => $a->tanggal, 'absensi' => $a, 'libur' => null])
            ->concat($liburBulanIni->map(fn ($h) => ['tanggal' => $h->tanggal, 'absensi' => null, 'libur' => $h]))
            ->sortByDesc(fn ($item) => $item['tanggal'])
            ->values();

        $today = Pengaturan::sekarang();

        return view('siswa-auth.riwayat', compact('siswa', 'riwayatGabungan', 'bulan', 'statistik', 'today'));
    }
}
