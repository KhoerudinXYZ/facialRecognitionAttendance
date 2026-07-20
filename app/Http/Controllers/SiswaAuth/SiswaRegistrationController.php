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

        // UPDATE bersyarat (WHERE username IS NULL), bukan $siswa->save() --
        // dua registrasi nyaris bersamaan buat NIS yang sama bisa lolos
        // whereNull('username') di atas berdua sebelum salah satunya sempat
        // menulis, dan $siswa->save() polos akan diam-diam menimpa siapa
        // pun yang menang duluan. Affected-row-count di sini yang jadi
        // wasit sebenarnya (atomik di level DB), bukan objek $siswa di
        // memori yang bisa saja sudah basi.
        $klaimBerhasil = Siswa::where('id', $siswa->id)
            ->whereNull('username')
            ->update([
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
            ]);

        if (! $klaimBerhasil) {
            throw ValidationException::withMessages([
                'nis' => 'NIS tidak ditemukan atau akun sudah terdaftar. Hubungi wali kelas/admin.',
            ]);
        }

        $siswa->refresh();

        Auth::guard('siswa')->login($siswa);
        $request->session()->regenerate();

        // Lanjut langsung ke pendaftaran wajah supaya registrasi terasa
        // satu alur utuh (data diri -> wajah), bukan dua langkah terpisah.
        return redirect()->route('siswa.enroll.create')
            ->with('success', 'Registrasi berhasil! Lanjutkan dengan mendaftarkan wajahmu.');
    }
}
