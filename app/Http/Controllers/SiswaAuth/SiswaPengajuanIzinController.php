<?php

namespace App\Http\Controllers\SiswaAuth;

use App\Http\Controllers\Controller;
use App\Mail\PengajuanIzinBaruMail;
use App\Models\HariLibur;
use App\Models\PengajuanIzin;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class SiswaPengajuanIzinController extends Controller
{
    public function create(): View
    {
        /** @var Siswa $siswa */
        $siswa = Auth::guard('siswa')->user();

        $today = Pengaturan::sekarang()->startOfDay();
        $pengajuanHariIni = $siswa->pengajuanIzin()->whereDate('tanggal', $today)->whereIn('jenis', ['izin', 'sakit'])->first();
        $izinPulangCepat  = $siswa->pengajuanIzin()->whereDate('tanggal', $today)->where('jenis', 'pulang_cepat')->first();

        return view('siswa-auth.izin', [
            'siswa'           => $siswa,
            'pengajuanHariIni' => $pengajuanHariIni,
            'izinPulangCepat'  => $izinPulangCepat,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Siswa $siswa */
        $siswa = Auth::guard('siswa')->user();

        $validated = $request->validate([
            'jenis'       => ['required', 'in:izin,sakit,pulang_cepat'],
            'keterangan'  => ['required', 'string', 'max:255'],
            'bukti'       => ['required', 'image', 'max:2048'],
        ]);

        $today = Pengaturan::sekarang()->startOfDay();

        if (HariLibur::isLibur($today)) {
            return back()->with('error', 'Hari ini bukan hari sekolah.');
        }

        // Cuma kehadiran asli (hadir/terlambat) yang memblokir pengajuan --
        // baris 'alpha' (mis. ditulis PengajuanIzinController::reject() saat
        // pengajuan sebelumnya ditolak) tidak boleh ikut memblokir, supaya
        // siswa masih bisa ajukan izin/sakit yang baru untuk hari yang sama.
        $absenHariIni = $siswa->absensi()->whereDate('tanggal', $today)->first();

        if ($validated['jenis'] === 'pulang_cepat') {
            // Pulang cepat hanya bisa diajukan oleh siswa yang SUDAH absen masuk
            if (! $absenHariIni || ! in_array($absenHariIni->status, ['hadir', 'terlambat'], true)) {
                return back()->with('error', 'Izin pulang cepat hanya bisa diajukan setelah absen masuk.');
            }
            // Kalau sudah absen pulang, tidak perlu izin lagi
            if ($absenHariIni->jam_pulang) {
                return back()->with('error', 'Kamu sudah tercatat absen pulang hari ini.');
            }
        } else {
            // izin/sakit: siswa yang sudah hadir tidak bisa mengajukan tidak masuk
            if ($absenHariIni && in_array($absenHariIni->status, ['hadir', 'terlambat'], true)) {
                return back()->with('error', 'Kamu sudah tercatat hadir hari ini.');
            }
        }

        $pengajuanHariIni = $siswa->pengajuanIzin()->whereDate('tanggal', $today)->where('jenis', $validated['jenis'])->first();
        if ($pengajuanHariIni && in_array($pengajuanHariIni->status, ['menunggu', 'disetujui'], true)) {
            $label = $validated['jenis'] === 'pulang_cepat' ? 'izin pulang cepat' : 'izin/sakit';
            return back()->with('error', "Kamu sudah punya pengajuan {$label} untuk hari ini.");
        }

        if ($pengajuanHariIni) {
            Storage::disk('public')->delete($pengajuanHariIni->bukti);
        }

        $buktiPath = $request->file('bukti')->store('bukti-izin', 'public');

        $pengajuan = $pengajuanHariIni ?? new PengajuanIzin([
            'siswa_id' => $siswa->id,
            'tanggal' => $today,
        ]);

        $pengajuan->jenis = $validated['jenis'];
        $pengajuan->keterangan = $validated['keterangan'];
        $pengajuan->bukti = $buktiPath;
        $pengajuan->status = 'menunggu';
        $pengajuan->catatan_admin = null;
        $pengajuan->reviewed_by = null;
        $pengajuan->reviewed_at = null;

        try {
            $pengajuan->save();
        } catch (UniqueConstraintViolationException) {
            // Dua submit nyaris bersamaan (double-klik, retry jaringan) bisa
            // lolos pengecekan "belum ada pengajuan" di atas berdua sebelum
            // salah satunya sempat INSERT. updateOrCreate() lama akan diam-
            // diam menimpa punya pemenang race dengan punya yang kalah lewat
            // createOrFirst() -- di sini yang kalah malah ditolak jelas,
            // bukan menimpa punya orang lain secara diam-diam.
            Storage::disk('public')->delete($buktiPath);

            return back()->with('error', 'Kamu sudah punya pengajuan izin/sakit untuk hari ini.');
        }

        $this->notifikasiWaliKelas($siswa, $validated['jenis'], $validated['keterangan'], $today);

        $label = $validated['jenis'] === 'pulang_cepat' ? 'izin pulang cepat' : 'izin/sakit';
        return redirect()->route('siswa.izin')->with('success', "Pengajuan {$label} berhasil dikirim, menunggu persetujuan.");
    }

    /**
     * Best effort — kegagalan kirim email tidak boleh menggagalkan
     * pengajuan siswa itu sendiri. Kalau kelas belum punya wali kelas
     * (atau wali kelasnya belum punya email), diam saja: admin tetap bisa
     * approve lewat halaman pengajuan-izin kapan saja.
     */
    private function notifikasiWaliKelas(Siswa $siswa, string $jenis, string $keterangan, Carbon $tanggal): void
    {
        $waliKelas = $siswa->kelas?->waliKelas;
        if (! $waliKelas?->email) {
            return;
        }

        try {
            Mail::to($waliKelas->email)->send(new PengajuanIzinBaruMail(
                $siswa->nama,
                $siswa->kelas->nama_kelas,
                $jenis,
                $keterangan,
                $tanggal,
            ));
        } catch (Throwable) {
            // Diam saja — lihat docblock method ini.
        }
    }
}
