<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSiswaAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('siswa')->check()) {
            return redirect()->guest(route('siswa.login'));
        }

        return $next($request);
    }
}
