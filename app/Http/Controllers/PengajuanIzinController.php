<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\PengajuanIzin;
use Illuminate\Database\UniqueConstraintViolationException;
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

        $this->tulisAbsensiDisetujui($pengajuanIzin);

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

        // AbsensiAlphaChecker cuma menandai alpha kalau dijalankan SEBELUM
        // pengajuan ini ditolak (lihat exclude-nya untuk status 'menunggu').
        // Kalau penolakan terjadi setelah run terakhir hari itu (atau di
        // hari berikutnya), tidak ada job lain yang akan menutup baris
        // tanggal ini -- tulis di sini juga supaya tidak ada tanggal yang
        // bolong sama sekali di riwayat. firstOrCreate: kalau baris sudah
        // ada (mis. checker sempat jalan duluan, atau siswa ternyata absen
        // beneran hari itu), biarkan apa adanya.
        Absensi::firstOrCreate(
            ['siswa_id' => $pengajuanIzin->siswa_id, 'tanggal' => $pengajuanIzin->tanggal],
            ['kelas_id' => $pengajuanIzin->siswa->kelas_id, 'status' => 'alpha', 'metode' => 'manual']
        );

        return back()->with('success', 'Pengajuan ditolak.');
    }

    /**
     * Tulis/timpa baris Absensi jadi izin/sakit sesuai pengajuan ini.
     * $percobaanKedua cuma kepakai kalau INSERT kita sendiri tabrakan
     * constraint unik siswa+tanggal karena race dengan penulisan lain
     * (mis. AbsensiRecorder yang tepat saat itu memproses scan wajah siswa
     * yang sama) -- re-fetch baris yang barusan menang race itu lalu
     * terapkan perubahan approve() ke situ. Pengajuan ini TETAP harus
     * berakhir disetujui, tidak boleh gagal cuma karena kalah race.
     */
    private function tulisAbsensiDisetujui(PengajuanIzin $pengajuanIzin, bool $percobaanKedua = false): void
    {
        $absensi = Absensi::where('siswa_id', $pengajuanIzin->siswa_id)
            ->whereDate('tanggal', $pengajuanIzin->tanggal)
            ->first();

        // kelas_id cuma di-stamp untuk baris BARU -- baris yang sudah ada
        // (mis. siswa sempat absen wajah sebelum pengajuannya disetujui)
        // menyimpan snapshot kelas siswa pada tanggal itu, jangan ditimpa.
        if (! $absensi) {
            $absensi = new Absensi([
                'siswa_id' => $pengajuanIzin->siswa_id,
                'kelas_id' => $pengajuanIzin->siswa->kelas_id,
                'tanggal' => $pengajuanIzin->tanggal,
            ]);
        }

        $absensi->status = $pengajuanIzin->jenis;
        $absensi->metode = 'manual';
        $absensi->keterangan = $pengajuanIzin->keterangan;
        // Baris izin/sakit tidak boleh menyisakan jam kehadiran dari status
        // lama (lihat komentar di atas soal absen wajah sebelum disetujui).
        $absensi->jam_masuk = null;
        $absensi->jam_pulang = null;

        try {
            $absensi->save();
        } catch (UniqueConstraintViolationException $e) {
            if ($percobaanKedua) {
                throw $e;
            }

            $this->tulisAbsensiDisetujui($pengajuanIzin, percobaanKedua: true);
        }
    }
}
