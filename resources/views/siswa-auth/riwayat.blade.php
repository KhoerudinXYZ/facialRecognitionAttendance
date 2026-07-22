<x-siswa-layout>
    @php
        $namaBulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $bulanSebelum = $bulan->copy()->subMonth();
        $bulanSetelah = $bulan->copy()->addMonth();
        $bisaMaju = $bulanSetelah->lte($today->copy()->startOfMonth());
    @endphp

    <div class="space-y-6">
        <!-- Header / Month Navigation -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="font-outfit font-black text-2xl sm:text-3xl text-transparent bg-clip-text bg-gradient-to-r from-indigo-900 to-indigo-600 dark:from-indigo-100 dark:to-indigo-400 tracking-tight">Riwayat Absensi</h1>
                <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta mt-1">Laporan Kehadiran Bulanan</p>
            </div>
            
            <div class="bento-card rounded-2xl shadow-lg p-2 flex items-center justify-between gap-4 border border-white/40 dark:border-slate-700/50 backdrop-blur-md">
                <a href="{{ route('siswa.riwayat', ['bulan' => $bulanSebelum->format('Y-m')]) }}"
                   class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-all duration-300 shadow-sm border border-transparent hover:border-indigo-200 dark:hover:border-indigo-800">
                    <x-icon name="arrow-left" class="w-4 h-4 stroke-[2.5]" />
                </a>
                <span class="font-black text-slate-800 dark:text-slate-100 font-lexend text-base uppercase tracking-wider min-w-[140px] text-center">{{ $namaBulan[$bulan->month - 1] }} {{ $bulan->year }}</span>
                @if ($bisaMaju)
                    <a href="{{ route('siswa.riwayat', ['bulan' => $bulanSetelah->format('Y-m')]) }}"
                       class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-all duration-300 shadow-sm border border-transparent hover:border-indigo-200 dark:hover:border-indigo-800">
                        <x-icon name="arrow-right" class="w-4 h-4 stroke-[2.5]" />
                    </a>
                @else
                    <span class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-50 dark:bg-slate-900 text-slate-300 dark:text-slate-700 cursor-not-allowed border border-slate-200 dark:border-slate-800/50">
                        <x-icon name="arrow-right" class="w-4 h-4 opacity-40 stroke-[2.5]" />
                    </span>
                @endif
            </div>
        </div>

        <!-- Monthly Summary Bento Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bento-card rounded-3xl shadow-xl p-5 border border-emerald-200/50 dark:border-emerald-800/30 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                <div class="flex justify-between items-start relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                        <x-icon name="check-circle" class="w-6 h-6 text-white stroke-[2.5]" />
                    </div>
                    <div class="text-4xl font-black font-lexend text-slate-800 dark:text-slate-100 tracking-tighter">{{ $statistik['hadir'] }}</div>
                </div>
                <div class="mt-4 relative z-10 text-[11px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest font-jakarta">Total Hadir</div>
            </div>
            
            <div class="bento-card rounded-3xl shadow-xl p-5 border border-amber-200/50 dark:border-amber-800/30 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-amber-500/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                <div class="flex justify-between items-start relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500 flex items-center justify-center shadow-lg shadow-amber-500/30">
                        <x-icon name="clock" class="w-6 h-6 text-white stroke-[2.5]" />
                    </div>
                    <div class="text-4xl font-black font-lexend text-slate-800 dark:text-slate-100 tracking-tighter">{{ $statistik['terlambat'] }}</div>
                </div>
                <div class="mt-4 relative z-10 text-[11px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-widest font-jakarta">Terlambat</div>
            </div>
            
            <div class="bento-card rounded-3xl shadow-xl p-5 border border-purple-200/50 dark:border-purple-800/30 relative overflow-hidden group">
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-purple-500/10 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                <div class="flex justify-between items-start relative z-10">
                    <div class="w-12 h-12 rounded-2xl bg-purple-500 flex items-center justify-center shadow-lg shadow-purple-500/30">
                        <x-icon name="alert-circle" class="w-6 h-6 text-white stroke-[2.5]" />
                    </div>
                    <div class="text-4xl font-black font-lexend text-slate-800 dark:text-slate-100 tracking-tighter">{{ $statistik['izinSakit'] }}</div>
                </div>
                <div class="mt-4 relative z-10 text-[11px] font-black text-purple-600 dark:text-purple-400 uppercase tracking-widest font-jakarta">Izin / Sakit</div>
            </div>
        </div>

        <!-- History Records Floating List -->
        <div class="space-y-4">
            <span class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta block ml-2">Detail Kehadiran</span>
            
            @if ($riwayatGabungan->isEmpty())
                <div class="bento-card rounded-3xl p-10 flex flex-col items-center justify-center text-center">
                    <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-300 dark:text-slate-600 flex items-center justify-center mb-4">
                        <x-icon name="calendar" class="w-8 h-8 stroke-[2]" />
                    </div>
                    <h3 class="text-lg font-black font-lexend text-slate-700 dark:text-slate-300">Belum Ada Riwayat</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 font-jakarta">Tidak ada data absensi yang tercatat pada bulan ini.</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($riwayatGabungan as $item)
                        @php
                            // Set styling vars for floating row
                            $isLibur = $item['libur'] ? true : false;
                            $status = $isLibur ? 'libur' : $item['absensi']->status;
                            
                            $rowClass = 'bento-card rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all duration-300 hover:scale-[1.01] hover:shadow-xl border ';
                            $rowClass .= $isLibur ? 'border-slate-200 dark:border-slate-800 opacity-75' : 'border-white/60 dark:border-slate-700/50';
                            
                            $badgeColors = [
                                'hadir' => 'bg-emerald-500 text-white shadow-emerald-500/30 border-emerald-400',
                                'terlambat' => 'bg-amber-500 text-white shadow-amber-500/30 border-amber-400',
                                'izin' => 'bg-purple-500 text-white shadow-purple-500/30 border-purple-400',
                                'sakit' => 'bg-purple-500 text-white shadow-purple-500/30 border-purple-400',
                                'alpha' => 'bg-rose-500 text-white shadow-rose-500/30 border-rose-400',
                                'libur' => 'bg-slate-500 text-white shadow-slate-500/30 border-slate-400'
                            ];
                            $badgeClass = $badgeColors[$status] ?? 'bg-slate-500 text-white shadow-slate-500/30';
                        @endphp
                        
                        <div class="{{ $rowClass }}">
                            <div class="flex items-center gap-4">
                                <!-- Date block -->
                                <div class="w-14 h-14 shrink-0 rounded-xl bg-slate-100 dark:bg-slate-800 flex flex-col items-center justify-center border border-slate-200/50 dark:border-slate-700/50 shadow-inner">
                                    <span class="text-[10px] font-black uppercase text-slate-500 font-jakarta">{{ $item['tanggal']->translatedFormat('D') }}</span>
                                    <span class="text-xl font-black font-lexend text-slate-800 dark:text-slate-100 leading-none mt-0.5">{{ $item['tanggal']->format('d') }}</span>
                                </div>
                                
                                <!-- Info block -->
                                <div>
                                    @if ($isLibur)
                                        <div class="font-black font-outfit text-base text-slate-700 dark:text-slate-300 flex items-center gap-2">
                                            <x-icon name="calendar" class="w-4 h-4 text-indigo-500" />
                                            {{ $item['libur']->keterangan ?: 'Hari libur' }}
                                        </div>
                                    @else
                                        <div class="flex items-center gap-6 mt-1">
                                            <div class="flex flex-col">
                                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest font-jakarta mb-0.5">Masuk</span>
                                                <span class="font-lexend font-black text-slate-800 dark:text-slate-200">
                                                    {{ \Illuminate\Support\Str::of($item['absensi']->jam_masuk)->substr(0,5) ?: '--:--' }}
                                                </span>
                                            </div>
                                            <div class="w-px h-8 bg-slate-200 dark:bg-slate-700"></div>
                                            <div class="flex flex-col">
                                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest font-jakarta mb-0.5">Pulang</span>
                                                <span class="font-lexend font-black text-slate-800 dark:text-slate-200">
                                                    {{ \Illuminate\Support\Str::of($item['absensi']->jam_pulang)->substr(0,5) ?: '--:--' }}
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Status Badge -->
                            <div class="shrink-0 flex sm:justify-end">
                                <span class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest shadow-lg border {{ $badgeClass }}">
                                    {{ $status }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="text-center pt-6">
                <a href="{{ route('siswa.dashboard') }}" class="inline-flex items-center gap-2 text-sm font-black font-lexend text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 bg-white/50 dark:bg-slate-800/50 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 px-6 py-3 rounded-xl border border-white dark:border-slate-700/50 shadow-sm transition-all duration-300 group">
                    <x-icon name="arrow-left" class="w-4 h-4 stroke-[2.5] group-hover:-translate-x-1 transition-transform" /> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</x-siswa-layout>
