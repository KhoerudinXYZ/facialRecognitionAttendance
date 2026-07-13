<?php

namespace App\Http\Controllers\SiswaAuth;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SiswaProfileController extends Controller
{
    public function edit(): View
    {
        /** @var Siswa $siswa */
        $siswa = Auth::guard('siswa')->user();
        $siswa->load('kelas');

        return view('siswa-auth.profile', compact('siswa'));
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var Siswa $siswa */
        $siswa = Auth::guard('siswa')->user();

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('siswa', 'username')->ignore($siswa->id)],
            'no_hp_orang_tua' => ['nullable', 'string', 'max:20'],
            'email_orang_tua' => ['nullable', 'email', 'max:255'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('foto')) {
            if ($siswa->foto) {
                Storage::disk('public')->delete($siswa->foto);
            }
            $validated['foto'] = $request->file('foto')->store('siswa', 'public');
        }

        $siswa->update($validated);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
