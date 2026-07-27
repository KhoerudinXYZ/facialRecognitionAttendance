<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\KoreksiAbsensi;
use App\Services\AbsensiManualWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KoreksiAbsensiController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->filled('status') ? $request->input('status') : 'menunggu';
        $kelasId = $request->integer('kelas_id') ?: null;

        $query = KoreksiAbsensi::with(['siswa.kelas'])->visibleTo($request->user());

        if ($status !== 'semua') {
            $query->where('status', $status);
        }

        if ($kelasId) {
            $query->whereHas('siswa', fn ($q) => $q->where('kelas_id', $kelasId));
        }

        $koreksiList = $query->orderBy('tanggal', 'desc')->get();

        $kelasList = Kelas::visibleTo($request->user())->orderBy('nama_kelas')->get();

        return view('koreksi-absensi.index', [
            'koreksiList' => $koreksiList,
            'status' => $status,
            'kelasId' => $kelasId,
            'kelasList' => $kelasList,
        ]);
    }

    /**
     * Setuju & langsung perbaiki dalam satu aksi -- status yang dipilih
     * admin di sini (default terisi status_diminta siswa di form, tapi
     * admin boleh ubah) langsung ditulis ke baris Absensi lewat
     * AbsensiManualWriter yang sama dipakai AbsensiController::manual().
     */
    public function approve(Request $request, KoreksiAbsensi $koreksiAbsensi, AbsensiManualWriter $writer): RedirectResponse
    {
        $this->authorize('create', [Absensi::class, $koreksiAbsensi->siswa]);
        abort_if($koreksiAbsensi->status !== 'menunggu', 403);

        $validated = $request->validate([
            'status' => ['required', 'in:hadir,terlambat,izin,sakit'],
        ]);

        $writer->tulis(
            $koreksiAbsensi->siswa,
            $koreksiAbsensi->tanggal->toDateString(),
            $validated['status'],
            $koreksiAbsensi->alasan,
        );

        $koreksiAbsensi->update([
            'status' => 'disetujui',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Koreksi disetujui, absensi sudah diperbarui.');
    }

    public function reject(Request $request, KoreksiAbsensi $koreksiAbsensi): RedirectResponse
    {
        $this->authorize('create', [Absensi::class, $koreksiAbsensi->siswa]);
        abort_if($koreksiAbsensi->status !== 'menunggu', 403);

        $validated = $request->validate([
            'catatan_admin' => ['nullable', 'string', 'max:255'],
        ]);

        // Beda dari PengajuanIzinController::reject() -- di situ perlu
        // fallback tulis baris 'alpha' kalau belum ada sama sekali (izin
        // soal hari yang belum tentu punya baris). Koreksi selalu soal
        // baris yang SUDAH ada (siswa membantah sesuatu yang sudah
        // tercatat), jadi menolak cukup biarkan baris aslinya apa adanya.
        $koreksiAbsensi->update([
            'status' => 'ditolak',
            'catatan_admin' => $validated['catatan_admin'] ?? null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Koreksi ditolak.');
    }
}
