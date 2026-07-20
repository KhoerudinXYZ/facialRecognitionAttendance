<?php

namespace App\Http\Controllers;

use App\Models\HariLibur;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HariLiburController extends Controller
{
    public function index(): View
    {
        $hariLibur = HariLibur::orderBy('tanggal')->get();
        $pengaturan = Pengaturan::get();

        return view('hari-libur.index', compact('hariLibur', 'pengaturan'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'dari' => ['required', 'date'],
            'sampai' => ['required', 'date', 'after_or_equal:dari'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $dari = Carbon::parse($data['dari']);
        $sampai = Carbon::parse($data['sampai']);

        // Form ini buat blok libur wajar (libur semester, cuti bersama),
        // bukan input tak terbatas -- tanpa batas, salah ketik tahun (mis.
        // 2026 jadi 2062) memicu ribuan HariLibur::create() satu per satu
        // dalam satu request, berisiko timeout dengan kalender libur yang
        // separuh ke-commit.
        if ($dari->diffInDays($sampai) > 366) {
            throw ValidationException::withMessages([
                'sampai' => 'Rentang tanggal maksimal 1 tahun sekali input.',
            ]);
        }

        $sudahAda = HariLibur::whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->pluck('tanggal')
            ->map(fn (Carbon $t) => $t->toDateString())
            ->all();

        $ditambahkan = DB::transaction(function () use ($dari, $sampai, $sudahAda, $data) {
            $jumlah = 0;
            for ($tanggal = $dari->copy(); $tanggal->lte($sampai); $tanggal->addDay()) {
                if (in_array($tanggal->toDateString(), $sudahAda, true)) {
                    continue;
                }

                HariLibur::create([
                    'tanggal' => $tanggal->toDateString(),
                    'keterangan' => $data['keterangan'] ?? null,
                ]);
                $jumlah++;
            }

            return $jumlah;
        });

        $pesan = $ditambahkan > 1 ? "{$ditambahkan} tanggal libur ditambahkan." : 'Hari libur ditambahkan.';
        if ($ditambahkan === 0) {
            $pesan = 'Semua tanggal di rentang itu sudah terdaftar sebagai libur.';
        }

        return back()->with('success', $pesan);
    }

    public function destroy(HariLibur $hariLibur): RedirectResponse
    {
        $hariLibur->delete();

        return back()->with('success', 'Hari libur dihapus.');
    }
}
