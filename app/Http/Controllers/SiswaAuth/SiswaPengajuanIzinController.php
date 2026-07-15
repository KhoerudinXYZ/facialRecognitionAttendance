<?php

namespace App\Http\Controllers\SiswaAuth;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\PengajuanIzin;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SiswaPengajuanIzinController extends Controller
{
    public function create(): View
    {
        /** @var Siswa $siswa */
        $siswa = Auth::guard('siswa')->user();

        $today = Pengaturan::sekarang()->startOfDay();
        $pengajuanHariIni = $siswa->pengajuanIzin()->whereDate('tanggal', $today)->first();

        return view('siswa-auth.izin', [
            'siswa' => $siswa,
            'pengajuanHariIni' => $pengajuanHariIni,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Siswa $siswa */
        $siswa = Auth::guard('siswa')->user();

        $validated = $request->validate([
            'jenis' => ['required', 'in:izin,sakit'],
            'keterangan' => ['required', 'string', 'max:255'],
            'bukti' => ['required', 'image', 'max:2048'],
        ]);

        $today = Pengaturan::sekarang()->startOfDay();

        if (HariLibur::isLibur($today)) {
            return back()->with('error', 'Hari ini bukan hari sekolah.');
        }

        if ($siswa->absensi()->whereDate('tanggal', $today)->exists()) {
            return back()->with('error', 'Kamu sudah tercatat hadir hari ini.');
        }

        $pengajuanHariIni = $siswa->pengajuanIzin()->whereDate('tanggal', $today)->first();
        if ($pengajuanHariIni && in_array($pengajuanHariIni->status, ['menunggu', 'disetujui'], true)) {
            return back()->with('error', 'Kamu sudah punya pengajuan izin/sakit untuk hari ini.');
        }

        if ($pengajuanHariIni) {
            Storage::disk('public')->delete($pengajuanHariIni->bukti);
        }

        $buktiPath = $request->file('bukti')->store('bukti-izin', 'public');

        PengajuanIzin::updateOrCreate(
            [
                'siswa_id' => $siswa->id,
                'tanggal' => $today,
            ],
            [
                'jenis' => $validated['jenis'],
                'keterangan' => $validated['keterangan'],
                'bukti' => $buktiPath,
                'status' => 'menunggu',
                'catatan_admin' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]
        );

        return redirect()->route('siswa.izin')->with('success', 'Pengajuan izin/sakit berhasil dikirim, menunggu persetujuan.');
    }
}
