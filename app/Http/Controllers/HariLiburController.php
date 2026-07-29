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
    public function index(Request $request): View
    {
        $hariLibur = HariLibur::orderBy('tanggal')->get();
        $pengaturan = Pengaturan::get();

        $tahun = max(2000, min(2100, (int) $request->query('tahun', Carbon::now()->year)));

        $tanggalLiburTahunIni = array_flip(
            HariLibur::whereYear('tanggal', $tahun)
                ->pluck('tanggal')
                ->map(fn (Carbon $t) => $t->toDateString())
                ->all()
        );

        $hariLiburMingguan = $pengaturan->liburMingguan();

        return view('hari-libur.index', compact(
            'hariLibur', 'pengaturan', 'tahun', 'tanggalLiburTahunIni', 'hariLiburMingguan'
        ));
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

        $tanggalList = [];
        for ($tanggal = $dari->copy(); $tanggal->lte($sampai); $tanggal->addDay()) {
            $tanggalList[] = $tanggal->toDateString();
        }

        $ditambahkan = $this->tambahLiburSkipDuplikat($tanggalList, $data['keterangan'] ?? null);

        $pesan = $ditambahkan > 1 ? "{$ditambahkan} tanggal libur ditambahkan." : 'Hari libur ditambahkan.';
        if ($ditambahkan === 0) {
            $pesan = 'Semua tanggal di rentang itu sudah terdaftar sebagai libur.';
        }

        return back()->with('success', $pesan);
    }

    public function storeBulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'array', 'min:1', 'max:366'],
            'tanggal.*' => ['date'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $tanggalList = array_values(array_unique(array_map(
            fn ($t) => Carbon::parse($t)->toDateString(),
            $data['tanggal']
        )));

        $ditambahkan = $this->tambahLiburSkipDuplikat($tanggalList, $data['keterangan'] ?? null);

        $pesan = $ditambahkan > 1 ? "{$ditambahkan} tanggal libur ditambahkan." : 'Hari libur ditambahkan.';
        if ($ditambahkan === 0) {
            $pesan = 'Semua tanggal yang dipilih sudah terdaftar sebagai libur.';
        }

        return back()->with('success', $pesan);
    }

    public function destroy(HariLibur $hariLibur): RedirectResponse
    {
        $hariLibur->delete();

        return back()->with('success', 'Hari libur dihapus.');
    }

    /**
     * @param  string[]  $tanggalList  Daftar tanggal 'Y-m-d', dipakai bareng oleh store() (rentang)
     *                                 dan storeBulk() (tersebar) supaya logika skip-duplicate satu tempat.
     */
    private function tambahLiburSkipDuplikat(array $tanggalList, ?string $keterangan): int
    {
        // whereIn('tanggal', ...) tidak bisa dipakai langsung -- kolom
        // 'tanggal' disimpan sebagai datetime string ('Y-m-d 00:00:00', ikut
        // format tanggal default koneksi DB) walau di-cast 'date' di model,
        // jadi exact-match string gagal cocok dengan 'Y-m-d' polos. Bungkus
        // kolomnya dengan DATE() biar dibandingkan sebagai tanggal murni.
        $sudahAda = HariLibur::whereIn(DB::raw('DATE(tanggal)'), $tanggalList)
            ->pluck('tanggal')
            ->map(fn (Carbon $t) => $t->toDateString())
            ->all();

        return DB::transaction(function () use ($tanggalList, $sudahAda, $keterangan) {
            $jumlah = 0;
            foreach ($tanggalList as $tanggal) {
                if (in_array($tanggal, $sudahAda, true)) {
                    continue;
                }

                HariLibur::create(['tanggal' => $tanggal, 'keterangan' => $keterangan]);
                $jumlah++;
            }

            return $jumlah;
        });
    }
}
