<x-siswa-layout>
    @php
        $namaBulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $bulanSebelum = $bulan->copy()->subMonth();
        $bulanSetelah = $bulan->copy()->addMonth();
        $bisaMaju = $bulanSetelah->lte($today->copy()->startOfMonth());
    @endphp

    <div class="space-y-6">
        <!-- Month Navigation Card -->
        <div class="glass-card rounded-2xl shadow-sm p-4 flex items-center justify-between border border-slate-200/50 dark:border-slate-800/55">
            <a href="{{ route('siswa.riwayat', ['bulan' => $bulanSebelum->format('Y-m')]) }}"
               class="w-10 h-10 flex items-center justify-center rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition duration-300">
                <x-icon name="arrow-left" class="w-4 h-4" />
            </a>
            <span class="font-bold text-slate-800 dark:text-slate-100 font-outfit text-base">{{ $namaBulan[$bulan->month - 1] }} {{ $bulan->year }}</span>
            @if ($bisaMaju)
                <a href="{{ route('siswa.riwayat', ['bulan' => $bulanSetelah->format('Y-m')]) }}"
                   class="w-10 h-10 flex items-center justify-center rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 transition duration-300">
                    <x-icon name="arrow-right" class="w-4 h-4" />
                </a>
            @else
                <span class="w-10 h-10 flex items-center justify-center rounded-xl text-slate-300 dark:text-slate-700 cursor-not-allowed">
                    <x-icon name="arrow-right" class="w-4 h-4 opacity-40" />
                </span>
            @endif
        </div>

        <!-- Monthly Summary Cards -->
        <div class="glass-card rounded-2xl shadow-sm p-5 border border-slate-200/50 dark:border-slate-800/55">
            <div class="grid grid-cols-3 gap-3">
                <div class="text-center bg-slate-50/50 dark:bg-slate-800/10 py-3 rounded-xl border border-slate-100 dark:border-slate-800/50">
                    <div class="w-9 h-9 mx-auto rounded-xl bg-green-100 text-green-600 dark:bg-green-950/30 dark:text-green-400 flex items-center justify-center shadow-inner">
                        <x-icon name="check-circle" class="w-4.5 h-4.5" />
                    </div>
                    <div class="text-xl font-bold font-outfit text-slate-800 dark:text-slate-100 mt-1.5 leading-none">{{ $statistik['hadir'] }}</div>
                    <div class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mt-1.5">Hadir</div>
                </div>
                <div class="text-center bg-slate-50/50 dark:bg-slate-800/10 py-3 rounded-xl border border-slate-100 dark:border-slate-800/50">
                    <div class="w-9 h-9 mx-auto rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400 flex items-center justify-center shadow-inner">
                        <x-icon name="clock" class="w-4.5 h-4.5" />
                    </div>
                    <div class="text-xl font-bold font-outfit text-slate-800 dark:text-slate-100 mt-1.5 leading-none">{{ $statistik['terlambat'] }}</div>
                    <div class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mt-1.5">Terlambat</div>
                </div>
                <div class="text-center bg-slate-50/50 dark:bg-slate-800/10 py-3 rounded-xl border border-slate-100 dark:border-slate-800/50">
                    <div class="w-9 h-9 mx-auto rounded-xl bg-purple-100 text-purple-600 dark:bg-purple-950/30 dark:text-purple-400 flex items-center justify-center shadow-inner">
                        <x-icon name="alert-circle" class="w-4.5 h-4.5" />
                    </div>
                    <div class="text-xl font-bold font-outfit text-slate-800 dark:text-slate-100 mt-1.5 leading-none">{{ $statistik['izinSakit'] }}</div>
                    <div class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider mt-1.5">Izin/Sakit</div>
                </div>
            </div>
        </div>

        <!-- History Records Table Card -->
        <div class="glass-card rounded-2xl shadow-sm p-5 border border-slate-200/50 dark:border-slate-800/55 space-y-4">
            <h3 class="font-outfit font-bold text-base text-slate-800 dark:text-slate-50">Daftar Kehadiran Bulanan</h3>
            @if ($riwayatGabungan->isEmpty())
                <div class="flex flex-col items-center gap-2 py-8 text-center text-slate-500 dark:text-slate-400">
                    <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-900 text-slate-400 dark:text-slate-500 flex items-center justify-center">
                        <x-icon name="calendar" class="w-5 h-5" />
                    </div>
                    <span class="text-xs font-semibold">Tidak ada riwayat absensi di bulan ini.</span>
                </div>
            @else
                <div class="overflow-x-auto rounded-xl border border-slate-200/50 dark:border-slate-800/70">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50/50 dark:bg-slate-800/30 text-left text-slate-450 dark:text-slate-500 font-semibold font-outfit border-b dark:border-slate-800">
                                <th class="py-3 px-3">Tanggal</th>
                                <th class="py-3 px-3">Masuk</th>
                                <th class="py-3 px-3">Pulang</th>
                                <th class="py-3 px-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-150 dark:divide-slate-800">
                            @foreach ($riwayatGabungan as $item)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                                    <td class="py-3 px-3 font-semibold text-slate-800 dark:text-slate-100 font-outfit">{{ $item['tanggal']->format('d/m/Y') }}</td>
                                    @if ($item['libur'])
                                        <td class="py-3 px-3 text-slate-400 dark:text-slate-500 font-medium text-xs" colspan="2">
                                            <span class="inline-flex items-center gap-1">
                                                <x-icon name="calendar" class="w-3.5 h-3.5 text-indigo-500" />
                                                {{ $item['libur']->keterangan ?: 'Hari libur' }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-3">
                                            <x-status-badge status="libur" class="px-2 py-0.5 rounded-md text-[10px] font-bold" />
                                        </td>
                                    @else
                                        <td class="py-3 px-3 text-slate-600 dark:text-slate-400 font-medium font-outfit">{{ \Illuminate\Support\Str::of($item['absensi']->jam_masuk)->substr(0,5) ?: '--:--' }}</td>
                                        <td class="py-3 px-3 text-slate-600 dark:text-slate-400 font-medium font-outfit">{{ \Illuminate\Support\Str::of($item['absensi']->jam_pulang)->substr(0,5) ?: '--:--' }}</td>
                                        <td class="py-3 px-3">
                                            <x-status-badge :status="$item['absensi']->status" class="px-2 py-0.5 rounded-md text-[10px] font-bold" />
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="text-center pt-2">
                <a href="{{ route('siswa.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                    <x-icon name="arrow-left" class="w-3.5 h-3.5" /> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</x-siswa-layout>
