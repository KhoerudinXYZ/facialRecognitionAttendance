<?php

namespace App\Http\Controllers\SiswaAuth;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SiswaPasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('siswa-auth.forgot-password');
    }

    /**
     * Minta NIS + email orang tua sekaligus (bukan cuma email) supaya:
     * (a) kakak-adik yang kebetulan pakai email orang tua yang sama tetap
     *     bisa dibedakan siswa mana yang dimaksud, dan
     * (b) siswa yang belum pernah registrasi (belum ada username/password)
     *     diarahkan ke halaman registrasi, bukan diberi link reset yang
     *     tidak ada gunanya.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nis' => ['required', 'string'],
            'email_orang_tua' => ['required', 'email'],
        ]);

        $siswa = Siswa::where('nis', $data['nis'])
            ->where('email_orang_tua', $data['email_orang_tua'])
            ->first();

        if ($siswa && ! $siswa->isRegistered()) {
            throw ValidationException::withMessages([
                'nis' => 'Akun untuk NIS ini belum diregistrasi. Silakan registrasi dulu dengan NIS-mu.',
            ]);
        }

        $status = Password::broker('siswa')->sendResetLink($data);

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withInput($request->only('nis'))
                ->withErrors(['nis' => __($status)]);
    }
}
