<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\AbsensiAuditLog;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Database\UniqueConstraintViolationException;
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

        $this->tulisAbsensiManual($siswa, $validated);

        return back()->with('success', 'Absensi manual disimpan.');
    }

    /**
     * $percobaanKedua cuma kepakai kalau INSERT kita sendiri tabrakan
     * constraint unik siswa+tanggal karena race dengan penulisan lain
     * (mis. AbsensiRecorder yang tepat saat itu memproses scan wajah siswa
     * yang sama) -- re-fetch baris yang barusan menang race itu lalu
     * terapkan koreksi manual ini ke situ. Koreksi admin TETAP harus
     * tersimpan, tidak boleh gagal cuma karena kalah race.
     */
    private function tulisAbsensiManual(Siswa $siswa, array $validated, bool $percobaanKedua = false): void
    {
        // Status non-hadir (izin/sakit/alpha) tidak boleh menyisakan jam
        // masuk/pulang dari baris sebelumnya -- kalau tidak, koreksi manual
        // hari yang sudah lengkap (mis. hadir -> sakit setelah surat dokter
        // menyusul) bisa meninggalkan jam_pulang lama nempel di baris sakit.
        $statusHadir = in_array($validated['status'], ['hadir', 'terlambat'], true);

        // whereDate(), bukan firstOrNew(['tanggal' => ...]) langsung: kolom
        // tanggal tersimpan sebagai datetime penuh ("Y-m-d H:i:s"), sedangkan
        // $validated['tanggal'] dari form cuma string "Y-m-d" mentah (beda
        // dari PengajuanIzinController yang selalu pakai objek Carbon dari
        // atribut model ter-cast). Pencarian exact-match dengan string mentah
        // itu tidak akan pernah ketemu baris yang sudah ada, jadi berakhir
        // coba INSERT baris baru dan gagal kena constraint unik siswa+tanggal.
        $absensi = Absensi::where('siswa_id', $validated['siswa_id'])
            ->whereDate('tanggal', $validated['tanggal'])
            ->first();

        // kelas_id cuma di-stamp untuk baris BARU -- baris yang sudah ada
        // menyimpan snapshot kelas siswa pada tanggal itu (lihat migration
        // add_kelas_id_to_absensi), jangan ditimpa jadi kelas siswa SEKARANG
        // cuma karena admin sedang mengoreksi status hari itu.
        if (! $absensi) {
            $absensi = new Absensi([
                'siswa_id' => $validated['siswa_id'],
                'kelas_id' => $siswa->kelas_id,
                'tanggal' => $validated['tanggal'],
            ]);
        }

        $absensi->status = $validated['status'];
        $absensi->metode = 'manual';
        $absensi->keterangan = $validated['keterangan'] ?? null;
        $absensi->jam_masuk = $statusHadir ? Carbon::now()->format('H:i:s') : null;

        if (! $statusHadir) {
            $absensi->jam_pulang = null;
        }

        try {
            $absensi->save();
        } catch (UniqueConstraintViolationException $e) {
            if ($percobaanKedua) {
                throw $e;
            }

            $this->tulisAbsensiManual($siswa, $validated, percobaanKedua: true);
        }
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
