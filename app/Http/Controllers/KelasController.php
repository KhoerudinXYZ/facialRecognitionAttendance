<?php

namespace App\Http\Controllers;

use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KelasController extends Controller
{
    public function index(Request $request): View
    {
        $today = Pengaturan::sekarang()->startOfDay();

        $kelas = Kelas::withCount('siswa')
            ->withCount(['siswa as hadir_hari_ini_count' => function ($query) use ($today) {
                $query->whereHas('absensi', function ($query) use ($today) {
                    $query->whereDate('tanggal', $today)->whereIn('status', ['hadir', 'terlambat']);
                });
            }])
            ->with('waliKelas')
            ->visibleTo($request->user())
            ->orderBy('nama_kelas')
            ->paginate(15);

        $isLibur = HariLibur::isLibur($today);

        // Cuma admin yang bisa menugaskan wali kelas — konsisten dengan
        // banner yang sama di dashboard admin.
        $kelasTanpaWali = $request->user()->isAdmin()
            ? Kelas::visibleTo($request->user())->whereNull('wali_kelas_id')->count()
            : 0;

        return view('kelas.index', compact('kelas', 'isLibur', 'kelasTanpaWali'));
    }

    public function create(): View
    {
        $waliKelasList = User::where('role', 'wali_kelas')->with('kelasBinaan')->orderBy('name')->get();

        return view('kelas.create', compact('waliKelasList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        Kelas::create($data);

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function edit(Kelas $kelas): View
    {
        $kelas->load('waliKelas');
        $waliKelasList = User::where('role', 'wali_kelas')->with('kelasBinaan')->orderBy('name')->get();

        return view('kelas.edit', compact('kelas', 'waliKelasList'));
    }

    public function update(Request $request, Kelas $kelas): RedirectResponse
    {
        $data = $this->validateData($request);
        $kelas->update($data);

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Kelas $kelas): RedirectResponse
    {
        // siswa.kelas_id pakai cascadeOnDelete, dan absensi/pengajuan_izin/
        // face_descriptors.siswa_id juga cascadeOnDelete -- menghapus kelas
        // yang masih ada siswanya diam-diam ikut menghapus SELURUH riwayat
        // absensi, izin/sakit, & data wajah siswa itu, bukan cuma baris
        // kelasnya. Pindahkan/hapus siswanya dulu secara eksplisit.
        if ($kelas->siswa()->exists()) {
            return back()->with('error', 'Kelas masih punya siswa. Pindahkan atau hapus siswanya dulu sebelum menghapus kelas ini.');
        }

        $kelas->delete();

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'nama_kelas' => ['required', 'string', 'max:100'],
            'jurusan' => ['nullable', 'string', 'max:100'],
            'tingkat' => ['required', 'in:X,XI,XII'],
            'wali_kelas_id' => ['nullable', 'exists:users,id'],
        ]);
    }
}
