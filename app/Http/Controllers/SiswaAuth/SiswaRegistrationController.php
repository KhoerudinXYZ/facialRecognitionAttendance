<?php

namespace App\Http\Controllers\SiswaAuth;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SiswaRegistrationController extends Controller
{
    public function create(): View
    {
        $isLibur = HariLibur::isLibur(Pengaturan::sekarang()->startOfDay());

        return view('siswa-auth.register', compact('isLibur'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nis' => ['required', 'string'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:siswa,username'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $siswa = Siswa::where('nis', $data['nis'])->whereNull('username')->first();

        if (! $siswa) {
            throw ValidationException::withMessages([
                'nis' => 'NIS tidak ditemukan atau akun sudah terdaftar. Hubungi wali kelas/admin.',
            ]);
        }

        $siswa->username = $data['username'];
        $siswa->password = Hash::make($data['password']);
        $siswa->save();

        Auth::guard('siswa')->login($siswa);
        $request->session()->regenerate();

        // Lanjut langsung ke pendaftaran wajah supaya registrasi terasa
        // satu alur utuh (data diri -> wajah), bukan dua langkah terpisah.
        return redirect()->route('siswa.enroll.create')
            ->with('success', 'Registrasi berhasil! Lanjutkan dengan mendaftarkan wajahmu.');
    }
}
