<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\AbsensiAuditLog;
use App\Models\AbsensiKecepatanAnomaliLog;
use App\Models\AbsensiLokasiGagalLog;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\AbsensiManualWriter;
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

        // Siswa yang absennya hari itu memicu anomali kecepatan (lihat
        // AbsensiRecorder::cekAnomaliKecepatan()) -- dipakai buat nampilkan
        // badge kecurigaan di rekap, murni informasi buat wali kelas/admin,
        // sama sekali tidak mengubah status absensinya.
        $siswaAnomali = AbsensiKecepatanAnomaliLog::whereDate('created_at', $tanggal)
            ->whereIn('siswa_id', $siswaList->pluck('id'))
            ->pluck('siswa_id')
            ->unique();

        $rekap = $siswaList->map(fn (Siswa $s) => [
            'siswa' => $s,
            'absensi' => $absensiMap->get($s->id),
            'dicurigaiFakeGps' => $siswaAnomali->contains($s->id),
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
    public function manual(Request $request, AbsensiManualWriter $writer): RedirectResponse
    {
        $validated = $request->validate([
            'siswa_id' => ['required', 'exists:siswa,id'],
            'tanggal' => ['required', 'date'],
            'status' => ['required', 'in:hadir,terlambat,izin,sakit,alpha'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $siswa = Siswa::visibleTo($request->user())->findOrFail($validated['siswa_id']);
        $this->authorize('create', [Absensi::class, $siswa]);

        $writer->tulis($siswa, $validated['tanggal'], $validated['status'], $validated['keterangan'] ?? null);

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

    /**
     * Dua sinyal audit lokasi -- cuma buat direview manual wali kelas/admin,
     * tidak ada satupun yang otomatis memblokir absen: (1) percobaan yang
     * ditolak cekLokasi() (lihat AbsensiRecorder), dan (2) lompatan lokasi
     * mustahil antara bacaan GPS "buka halaman" vs "submit absen" (lihat
     * AbsensiRecorder::cekAnomaliKecepatan()).
     *
     * Sebelumnya ada sinyal ketiga ("Koordinat Kembar" -- siswa berbeda
     * dengan koordinat nyaris identik) tapi dihapus: uji nyata di lapangan
     * pakai app fake-GPS gratisan menunjukkan bucket-nya (dibulatkan ~1m)
     * nyaris tidak pernah kena karena app itu sendiri sudah nambahin jitter
     * beberapa meter. Anomali kecepatan terbukti jauh lebih efektif nangkep
     * kasus nyata (lihat memory audit-lokasi-anti-spoofing).
     */
    public function auditLokasi(): View
    {
        $gagalLog = AbsensiLokasiGagalLog::with('siswa')
            ->orderByDesc('created_at')
            ->paginate(30);

        $anomaliKecepatan = AbsensiKecepatanAnomaliLog::with('siswa')
            ->orderByDesc('created_at')
            ->paginate(30, ['*'], 'anomali_page');

        return view('absensi.audit-lokasi', [
            'gagalLog' => $gagalLog,
            'anomaliKecepatan' => $anomaliKecepatan,
        ]);
    }
}
