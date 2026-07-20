<?php

namespace App\Http\Controllers;

use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class StaffUserController extends Controller
{
    public function index(): View
    {
        $staff = User::with('kelasBinaan')->orderBy('name')->paginate(15);

        $today = Pengaturan::sekarang()->startOfDay();
        $isLibur = HariLibur::isLibur($today);

        // Untuk wali kelas, tampilkan nama kelas binaan (konsisten dengan
        // anotasi "sudah wali ..." di form Kelas) + ringkasan hadir hari ini
        // dari kelas itu — konsisten dengan kolom "Hadir Hari Ini" di Data Kelas.
        $staff->getCollection()->transform(function (User $user) use ($today) {
            if ($user->role === 'wali_kelas') {
                $kelasIds = $user->kelasBinaan->pluck('id');
                $user->total_siswa_binaan = Siswa::whereIn('kelas_id', $kelasIds)->count();
                $user->hadir_hari_ini_binaan = Siswa::whereIn('kelas_id', $kelasIds)
                    ->whereHas('absensi', fn ($q) => $q->whereDate('tanggal', $today)->whereIn('status', ['hadir', 'terlambat']))
                    ->count();
            }

            return $user;
        });

        // Mirip banner "N kelas belum punya wali kelas" di dashboard/Data
        // Kelas, tapi dari sisi sebaliknya: wali kelas yang belum ditugaskan
        // ke kelas manapun.
        $waliTanpaKelas = User::where('role', 'wali_kelas')->doesntHave('kelasBinaan')->count();

        return view('staff.index', compact('staff', 'isLibur', 'waliTanpaKelas'));
    }

    public function create(): View
    {
        return view('staff.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('staff.index')->with('success', 'Akun staff berhasil ditambahkan.');
    }

    public function edit(User $staff): View
    {
        $staff->load('kelasBinaan');

        return view('staff.edit', ['staff' => $staff]);
    }

    public function update(Request $request, User $staff): RedirectResponse
    {
        $data = $this->validateData($request, $staff->id, passwordRequired: false);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $sebelumnyaWaliKelas = $staff->role === 'wali_kelas';

        $staff->update($data);

        // kelas.wali_kelas_id cuma nullOnDelete kalau AKUN-nya dihapus --
        // ganti role keluar dari wali_kelas (bukan hapus akun) tidak
        // otomatis melepas kelas yang masih menunjuk ke dia, jadi kelas itu
        // jadi punya wali_kelas_id yang nunjuk ke user ber-role admin
        // (tidak lagi muncul di mana pun sebagai pilihan wali kelas).
        if ($sebelumnyaWaliKelas && $staff->role !== 'wali_kelas') {
            Kelas::where('wali_kelas_id', $staff->id)->update(['wali_kelas_id' => null]);
        }

        return redirect()->route('staff.index')->with('success', 'Akun staff berhasil diperbarui.');
    }

    public function destroy(User $staff): RedirectResponse
    {
        abort_if($staff->id === auth()->id(), 403, 'Tidak bisa menghapus akun sendiri.');

        $staff->delete();

        return redirect()->route('staff.index')->with('success', 'Akun staff berhasil dihapus.');
    }

    private function validateData(Request $request, ?int $ignoreId = null, bool $passwordRequired = true): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email' . ($ignoreId ? ",{$ignoreId}" : '')],
            'role' => ['required', 'in:admin,wali_kelas'],
            'password' => [$passwordRequired ? 'required' : 'nullable', 'confirmed', Password::defaults()],
        ]);
    }
}
