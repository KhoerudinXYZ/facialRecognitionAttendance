<?php

namespace App\Http\Controllers\SiswaAuth;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiswaAuth\SiswaLoginRequest;
use App\Models\HariLibur;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SiswaSessionController extends Controller
{
    public function create(): View
    {
        $isLibur = HariLibur::isLibur(Pengaturan::sekarang()->startOfDay());

        return view('siswa-auth.login', compact('isLibur'));
    }

    public function store(SiswaLoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('siswa.dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('siswa')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('siswa.login');
    }
}
