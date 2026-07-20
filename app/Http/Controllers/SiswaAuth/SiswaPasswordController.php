<?php

namespace App\Http\Controllers\SiswaAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SiswaPasswordController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password:siswa'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $siswa = Auth::guard('siswa')->user();
        $siswa->password = Hash::make($validated['password']);
        $siswa->save();

        return back()->with('success', 'Password berhasil diperbarui.');
    }
}
