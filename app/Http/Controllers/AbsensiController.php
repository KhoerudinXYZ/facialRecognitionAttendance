<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\AbsensiAuditLog;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AbsensiController extends Controller
{
    /**
     * Rekap absensi harian dengan filter tanggal & kelas.
     */
    public function index(Request $request): View
    {
        $tanggal = $request->filled('tanggal')
            ? Carbon::parse($request->input('tanggal'))
            : Carbon::today();

        $kelasId = $request->integer('kelas_id') ?: null;

        // Semua siswa aktif (opsional difilter kelas), digabung status absensinya.
        $siswaQuery = Siswa::with('kelas')->where('is_active', true)->visibleTo($request->user());
        if ($kelasId) {
            $siswaQuery->where('kelas_id', $kelasId);
        }
        $siswaList = $siswaQuery->orderBy('nama')->get();

        $absensiMap = Absensi::whereDate('tanggal', $tanggal)
            ->visibleTo($request->user())
            ->get()
            ->keyBy('siswa_id');

        $rekap = $siswaList->map(fn (Siswa $s) => [
            'siswa' => $s,
            'absensi' => $absensiMap->get($s->id),
        ]);

        $kelasList = Kelas::visibleTo($request->user())->orderBy('nama_kelas')->get();

        return view('absensi.index', [
            'rekap' => $rekap,
            'tanggal' => $tanggal,
            'kelasId' => $kelasId,
            'kelasList' => $kelasList,
            'isLibur' => HariLibur::isLibur($tanggal),
        ]);
    }

    /**
     * Input / ubah absensi manual (izin, sakit, alpha, hadir).
     */
    public function manual(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'siswa_id' => ['required', 'exists:siswa,id'],
            'tanggal' => ['required', 'date'],
            'status' => ['required', 'in:hadir,terlambat,izin,sakit,alpha'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $siswa = Siswa::visibleTo($request->user())->findOrFail($validated['siswa_id']);
        $this->authorize('create', [Absensi::class, $siswa]);
        $validated['siswa_id'] = $siswa->id;

        Absensi::updateOrCreate(
            [
                'siswa_id' => $validated['siswa_id'],
                'tanggal' => $validated['tanggal'],
            ],
            [
                'status' => $validated['status'],
                'metode' => 'manual',
                'keterangan' => $validated['keterangan'] ?? null,
                'jam_masuk' => in_array($validated['status'], ['hadir', 'terlambat']) ? Carbon::now()->format('H:i:s') : null,
            ]
        );

        return back()->with('success', 'Absensi manual disimpan.');
    }

    /**
     * Hapus (reset) satu record absensi dari rekap. Baris absensi asli
     * akan hilang total (bukan soft delete), jadi datanya disalin dulu ke
     * absensi_audit_log sebelum dihapus — supaya tetap bisa dilacak siapa
     * menghapus apa, kapan, walau baris aslinya sudah tidak ada.
     */
    public function destroy(Absensi $absensi): RedirectResponse
    {
        $this->authorize('delete', $absensi);

        $nama = $absensi->siswa->nama ?? 'siswa';

        AbsensiAuditLog::create([
            'absensi_id' => $absensi->id,
            'siswa_id' => $absensi->siswa_id,
            'siswa_nama' => $nama,
            'tanggal' => $absensi->tanggal,
            'jam_masuk' => $absensi->jam_masuk,
            'jam_pulang' => $absensi->jam_pulang,
            'status' => $absensi->status,
            'metode' => $absensi->metode,
            'keterangan' => $absensi->keterangan,
            'dihapus_oleh_user_id' => Auth::id(),
            'dihapus_oleh_nama' => Auth::user()->name,
        ]);

        $absensi->delete();

        return back()->with('success', "Absensi {$nama} berhasil dihapus.");
    }

    /**
     * Daftar riwayat absensi yang pernah dihapus (admin saja) — murni
     * untuk akuntabilitas/oversight, bukan tempat mengembalikan data.
     */
    public function audit(): View
    {
        $log = AbsensiAuditLog::orderByDesc('created_at')->paginate(30);

        return view('absensi.audit', compact('log'));
    }
}
