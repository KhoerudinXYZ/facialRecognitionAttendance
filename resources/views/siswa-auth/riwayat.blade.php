<x-siswa-layout>
    @php
        $namaBulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $bulanSebelum = $bulan->copy()->subMonth();
        $bulanSetelah = $bulan->copy()->addMonth();
        $bisaMaju = $bulanSetelah->lte($today->copy()->startOfMonth());
    @endphp

    <div class="space-y-4">
        {{-- Navigasi bulan --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex items-center justify-between">
            <a href="{{ route('siswa.riwayat', ['bulan' => $bulanSebelum->format('Y-m')]) }}"
               class="w-9 h-9 flex items-center justify-center rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                &lsaquo;
            </a>
            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $namaBulan[$bulan->month - 1] }} {{ $bulan->year }}</span>
            @if ($bisaMaju)
                <a href="{{ route('siswa.riwayat', ['bulan' => $bulanSetelah->format('Y-m')]) }}"
                   class="w-9 h-9 flex items-center justify-center rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                    &rsaquo;
                </a>
            @else
                <span class="w-9 h-9 flex items-center justify-center rounded-full text-gray-300 dark:text-gray-600">&rsaquo;</span>
            @endif
        </div>

        {{-- Ringkasan bulan --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <div class="grid grid-cols-3 gap-2">
                <div class="text-center">
                    <div class="w-9 h-9 mx-auto rounded-full bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400 flex items-center justify-center">
                        <x-icon name="check-circle" class="w-4 h-4" />
                    </div>
                    <div class="text-lg font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $statistik['hadir'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Hadir</div>
                </div>
                <div class="text-center">
                    <div class="w-9 h-9 mx-auto rounded-full bg-yellow-100 text-yellow-600 dark:bg-yellow-900/40 dark:text-yellow-400 flex items-center justify-center">
                        <x-icon name="clock" class="w-4 h-4" />
                    </div>
                    <div class="text-lg font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $statistik['terlambat'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Terlambat</div>
                </div>
                <div class="text-center">
                    <div class="w-9 h-9 mx-auto rounded-full bg-purple-100 text-purple-600 dark:bg-purple-900/40 dark:text-purple-400 flex items-center justify-center">
                        <x-icon name="clipboard" class="w-4 h-4" />
                    </div>
                    <div class="text-lg font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $statistik['izinSakit'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Izin/Sakit</div>
                </div>
            </div>
        </div>

        {{-- Daftar riwayat --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">Riwayat Absensi</h3>
            @if ($riwayatGabungan->isEmpty())
                <div class="flex flex-col items-center gap-2 py-6 text-center text-gray-500 dark:text-gray-400">
                    <div class="w-11 h-11 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 flex items-center justify-center">
                        <x-icon name="clipboard" class="w-5 h-5" />
                    </div>
                    Tidak ada riwayat absensi di bulan ini.
                </div>
            @else
                <table class="min-w-full text-sm">
                    <thead><tr class="text-left text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                        <th class="py-2 pr-4">Tanggal</th><th class="py-2 pr-4">Masuk</th><th class="py-2 pr-4">Pulang</th><th class="py-2 pr-4">Status</th><th class="py-2">Metode</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($riwayatGabungan as $item)
                            <tr class="border-b dark:border-gray-700 last:border-0">
                                <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">{{ $item['tanggal']->format('d/m/Y') }}</td>
                                @if ($item['libur'])
                                    <td class="py-2 pr-4 text-gray-400 dark:text-gray-500" colspan="2">
                                        <span class="inline-flex items-center gap-1">
                                            <x-icon name="x-circle" class="w-3.5 h-3.5" />
                                            {{ $item['libur']->keterangan ?: 'Hari libur' }}
                                        </span>
                                    </td>
                                    <td class="py-2 pr-4"><x-status-badge status="libur" /></td>
                                    <td class="py-2 text-gray-500 dark:text-gray-400">-</td>
                                @else
                                    <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">{{ \Illuminate\Support\Str::of($item['absensi']->jam_masuk)->substr(0,5) ?: '-' }}</td>
                                    <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">{{ \Illuminate\Support\Str::of($item['absensi']->jam_pulang)->substr(0,5) ?: '-' }}</td>
                                    <td class="py-2 pr-4"><x-status-badge :status="$item['absensi']->status" /></td>
                                    <td class="py-2 text-gray-500 dark:text-gray-400">{{ ucfirst($item['absensi']->metode) }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <a href="{{ route('siswa.dashboard') }}" class="inline-block mt-4 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">Kembali</a>
        </div>
    </div>
</x-siswa-layout>
