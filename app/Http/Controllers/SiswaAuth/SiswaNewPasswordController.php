<?php

namespace App\Http\Controllers\SiswaAuth;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class SiswaNewPasswordController extends Controller
{
    public function create(Request $request): View
    {
        return view('siswa-auth.reset-password', ['request' => $request]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required'],
            'nis' => ['required', 'string'],
            'email_orang_tua' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::broker('siswa')->reset(
            $data,
            function (Siswa $siswa) use ($request) {
                $siswa->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($siswa));
            }
        );

        return $status == Password::PASSWORD_RESET
            ? redirect()->route('siswa.login')->with('status', __($status))
            : back()->withInput($request->only('nis', 'email_orang_tua'))
                ->withErrors(['nis' => __($status)]);
    }
}
