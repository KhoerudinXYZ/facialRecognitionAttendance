<?php

namespace App\Http\Controllers;

use App\Models\NotifikasiAbsensiLog;
use Illuminate\View\View;

class NotifikasiAbsensiController extends Controller
{
    /**
     * Riwayat notifikasi WhatsApp orang tua saat siswa alpha (admin saja) —
     * murni untuk memantau apakah notifikasi terkirim, bukan tempat
     * mengirim ulang.
     */
    public function index(): View
    {
        $log = NotifikasiAbsensiLog::orderByDesc('created_at')->paginate(30);

        return view('notifikasi.index', compact('log'));
    }
}
