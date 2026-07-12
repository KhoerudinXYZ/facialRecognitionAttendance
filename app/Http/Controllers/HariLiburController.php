<?php

namespace App\Http\Controllers;

use App\Models\HariLibur;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $sudahAda = HariLibur::whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->pluck('tanggal')
            ->map(fn (Carbon $t) => $t->toDateString())
            ->all();

        $ditambahkan = 0;
        for ($tanggal = $dari->copy(); $tanggal->lte($sampai); $tanggal->addDay()) {
            if (in_array($tanggal->toDateString(), $sudahAda, true)) {
                continue;
            }

            HariLibur::create([
                'tanggal' => $tanggal->toDateString(),
                'keterangan' => $data['keterangan'] ?? null,
            ]);
            $ditambahkan++;
        }

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
