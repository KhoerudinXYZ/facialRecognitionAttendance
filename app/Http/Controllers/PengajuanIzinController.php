<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\PengajuanIzin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengajuanIzinController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->filled('status') ? $request->input('status') : 'menunggu';
        $kelasId = $request->integer('kelas_id') ?: null;

        $query = PengajuanIzin::with(['siswa.kelas'])->visibleTo($request->user());

        if ($status !== 'semua') {
            $query->where('status', $status);
        }

        if ($kelasId) {
            $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $kelasId));
        }

        $pengajuanList = $query->orderBy('tanggal', 'desc')->get();

        $kelasList = Kelas::visibleTo($request->user())->orderBy('nama_kelas')->get();

        return view('pengajuan-izin.index', [
            'pengajuanList' => $pengajuanList,
            'status' => $status,
            'kelasId' => $kelasId,
            'kelasList' => $kelasList,
        ]);
    }

    public function approve(PengajuanIzin $pengajuanIzin): RedirectResponse
    {
        $this->authorize('create', [Absensi::class, $pengajuanIzin->siswa]);
        abort_if($pengajuanIzin->status !== 'menunggu', 403);

        Absensi::updateOrCreate(
            [
                'siswa_id' => $pengajuanIzin->siswa_id,
                'tanggal' => $pengajuanIzin->tanggal,
            ],
            [
                'status' => $pengajuanIzin->jenis,
                'metode' => 'manual',
                'keterangan' => $pengajuanIzin->keterangan,
            ]
        );

        $pengajuanIzin->update([
            'status' => 'disetujui',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan disetujui.');
    }

    public function reject(Request $request, PengajuanIzin $pengajuanIzin): RedirectResponse
    {
        $this->authorize('create', [Absensi::class, $pengajuanIzin->siswa]);
        abort_if($pengajuanIzin->status !== 'menunggu', 403);

        $validated = $request->validate([
            'catatan_admin' => ['nullable', 'string', 'max:255'],
        ]);

        $pengajuanIzin->update([
            'status' => 'ditolak',
            'catatan_admin' => $validated['catatan_admin'] ?? null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan ditolak.');
    }
}
