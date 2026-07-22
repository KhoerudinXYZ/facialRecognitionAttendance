<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-outfit font-black text-2xl sm:text-3xl text-transparent bg-clip-text bg-gradient-to-r from-slate-900 via-indigo-900 to-indigo-600 dark:from-white dark:via-indigo-100 dark:to-indigo-400 tracking-tight">Dashboard Overview</h2>
                <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta mt-0.5">Ringkasan Sistem Kehadiran</p>
            </div>
            <div class="hidden sm:flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black font-lexend bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-500/20 uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    {{ auth()->user()->isAdmin() ? 'Administrator' : 'Wali Kelas' }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
        <!-- Holiday Banner -->
        @if ($isLibur)
            <div class="bento-card rounded-[2rem] p-6 border-indigo-200/50 dark:border-indigo-800/40 relative overflow-hidden bg-gradient-to-r from-indigo-500/10 via-purple-500/10 to-indigo-500/5 backdrop-blur-md">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-indigo-500/30">
                        <x-icon name="calendar" class="w-6 h-6 stroke-[2.5]" />
                    </div>
                    <div>
                        <span class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 block">Hari Ini Libur Sekolah</span>
                        <p class="text-sm font-semibold text-slate-600 dark:text-slate-400 font-jakarta mt-1">Hari ini terdaftar sebagai hari libur resmi. Proses presensi siswa tidak diwajibkan.</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Missing Homeroom Teacher Banner -->
        @if ($kelasTanpaWali > 0)
            <div class="bento-card rounded-[2rem] p-6 border-amber-200/50 dark:border-amber-800/40 relative overflow-hidden bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent backdrop-blur-md">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-amber-500/30">
                            <x-icon name="alert-circle" class="w-6 h-6 stroke-[2.5]" />
                        </div>
                        <div>
                            <span class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 block">Konfigurasi Wali Kelas Belum Lengkap</span>
                            <p class="text-sm font-semibold text-slate-600 dark:text-slate-400 font-jakarta">Terdapat <strong class="text-amber-600 dark:text-amber-400 font-lexend font-black">{{ $kelasTanpaWali }} kelas</strong> yang belum ditugaskan wali kelas.</p>
                        </div>
                    </div>
                    <a href="{{ route('kelas.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-500 text-white font-black font-lexend text-xs uppercase tracking-wider shadow-lg shadow-amber-500/30 hover:bg-amber-600 transition-all duration-300">
                        Atur Kelas <x-icon name="arrow-right" class="w-4 h-4 stroke-[2.5]" />
                    </a>
                </div>
            </div>
        @endif

        <!-- Summary Statistics Bento Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @php
                $cards = [
                    ['label' => 'Total Siswa',   'value' => $totalSiswa, 'icon' => 'users',        'watermark' => 'TOTAL', 'color' => 'bg-blue-500',   'border' => 'border-blue-200/50 dark:border-blue-800/30',   'route' => route('siswa.index')],
                    ['label' => 'Hadir Hari Ini','value' => $hadir,      'icon' => 'check-circle', 'watermark' => 'HADIR', 'color' => 'bg-emerald-500','border' => 'border-emerald-200/50 dark:border-emerald-800/30', 'route' => route('absensi.index')],
                    ['label' => 'Terlambat',     'value' => $terlambat,  'icon' => 'clock',        'watermark' => 'TELAT', 'color' => 'bg-amber-500',  'border' => 'border-amber-200/50 dark:border-amber-800/30',   'route' => route('absensi.index')],
                    ['label' => 'Izin / Sakit',  'value' => $izinSakit,  'icon' => 'alert-circle', 'watermark' => 'IZIN',  'color' => 'bg-purple-500', 'border' => 'border-purple-200/50 dark:border-purple-800/30', 'route' => route('absensi.index')],
                ];
            @endphp
            @foreach ($cards as $c)
                <a href="{{ $c['route'] }}" class="bento-card rounded-[2rem] p-6 shadow-xl relative overflow-hidden group border {{ $c['border'] }} transition-all duration-500 hover:scale-[1.03]">
                    <div class="absolute -right-4 -bottom-6 text-[90px] font-black text-slate-900/[0.03] dark:text-white/[0.02] font-lexend pointer-events-none tracking-tighter leading-none select-none transition-transform duration-700 group-hover:scale-110">
                        {{ $c['watermark'] }}
                    </div>
                    
                    <div class="flex justify-between items-start relative z-10">
                        <div class="w-12 h-12 rounded-2xl {{ $c['color'] }} text-white flex items-center justify-center shadow-lg">
                            <x-icon :name="$c['icon']" class="w-6 h-6 stroke-[2.5]" />
                        </div>
                        <span class="text-4xl font-black font-lexend text-slate-800 dark:text-slate-100 tracking-tighter leading-none tabular-nums">{{ $c['value'] }}</span>
                    </div>
                    
                    <div class="mt-6 relative z-10">
                        <span class="text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 block">{{ $c['label'] }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- 7-Day Attendance Trend Chart -->
        <div class="bento-card rounded-[2.5rem] p-6 sm:p-8 shadow-xl relative overflow-hidden group">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-8 relative z-10">
                <div>
                    <h3 class="font-outfit font-black text-xl text-slate-800 dark:text-slate-100 tracking-tight">Tren Kehadiran Siswa</h3>
                    <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta mt-0.5">7 Hari Terakhir</p>
                </div>
                <span class="inline-flex items-center gap-1.5 text-xs font-black font-lexend text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-4 py-2 rounded-xl border border-indigo-200/50 dark:border-indigo-800/50 uppercase tracking-wider">
                    Visualisasi Data
                </span>
            </div>

            @php $maxJumlah = max(1, $tren7Hari->max('jumlah')); @endphp
            <div class="flex items-end gap-3 sm:gap-6 h-40 px-2 relative z-10">
                @foreach ($tren7Hari as $hari)
                    <div class="flex-1 flex flex-col items-center justify-end h-full gap-2 group">
                        <span class="text-xs font-black font-lexend text-indigo-600 dark:text-indigo-400 opacity-0 group-hover:opacity-100 transition-opacity duration-300 drop-shadow-sm">{{ $hari['jumlah'] }}</span>
                        <div @class([
                                 'w-full rounded-2xl transition-all duration-700 shadow-md',
                                 'bg-gradient-to-t from-indigo-600 to-violet-500 shadow-indigo-500/40 glow-indigo' => $hari['isToday'],
                                 'bg-slate-200/80 dark:bg-slate-800 group-hover:bg-indigo-400/50 dark:group-hover:bg-indigo-600/50' => ! $hari['isToday'],
                             ])
                             style="height: {{ $hari['jumlah'] > 0 ? max(12, round($hari['jumlah'] / $maxJumlah * 100)) : 6 }}%"></div>
                        <span @class([
                                'text-xs font-black font-lexend uppercase tracking-wider mt-1',
                                'text-indigo-600 dark:text-indigo-400' => $hari['isToday'],
                                'text-slate-400 dark:text-slate-500' => ! $hari['isToday'],
                            ])>{{ $hari['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Bottom Grid: Homeroom vs Administrator Dashboards -->
        @if (auth()->user()->isWaliKelas())
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Wali Kelas Quick Info -->
                <div class="bento-card rounded-[2.5rem] p-6 sm:p-8 shadow-xl space-y-6">
                    <div class="pb-4 border-b border-slate-200/50 dark:border-slate-700/50">
                        <h3 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100">Kelas Binaan Saya</h3>
                        <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta">Tugas Wali Kelas</span>
                    </div>
                    
                    <div class="space-y-3">
                        @forelse ($kelasBinaan as $k)
                            <div class="flex items-center justify-between py-3 px-4 rounded-2xl bg-white/50 dark:bg-slate-900/40 border border-white/60 dark:border-slate-700/50 shadow-inner backdrop-blur-sm">
                                <span class="font-black text-slate-800 dark:text-slate-100 font-lexend text-sm">{{ $k->nama_kelas }}</span>
                                <span class="text-xs font-bold font-jakarta text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-3 py-1 rounded-full border border-indigo-200/50 dark:border-indigo-800/50">{{ $k->siswa_count }} Siswa</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400 font-jakarta">Belum ditugaskan sebagai wali kelas manapun.</p>
                        @endforelse
                    </div>

                    <div class="pt-2 space-y-3">
                        <a href="{{ route('siswa.create') }}" class="relative flex items-center justify-center gap-2 w-full overflow-hidden bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white py-3.5 rounded-2xl font-black font-lexend shadow-lg shadow-indigo-500/20 transition-all duration-300 transform active:scale-95 text-xs uppercase tracking-wider">
                            <x-icon name="plus" class="w-4 h-4 stroke-[3]" /> Tambah Siswa Baru
                        </a>
                        <a href="{{ route('absensi.index') }}" class="flex items-center justify-center gap-2 w-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 py-3.5 rounded-2xl font-black font-lexend text-xs uppercase tracking-wider transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                            <x-icon name="clock" class="w-4 h-4 stroke-[2.5]" /> Rekap Absensi Kelas
                        </a>
                    </div>
                    
                    <div class="text-xs text-slate-500 dark:text-slate-400 pt-4 border-t border-slate-200/50 dark:border-slate-700/50 space-y-2 font-jakarta">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold">Siswa Terdaftar Wajah:</span>
                            <span class="font-black text-indigo-600 dark:text-indigo-400 font-lexend text-sm">{{ $sudahEnroll }} / {{ $totalSiswa }}</span>
                        </div>
                    </div>
                </div>

                <!-- Daily Class Roster -->
                <div class="bento-card rounded-[2.5rem] p-6 sm:p-8 shadow-xl lg:col-span-2 space-y-5">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-200/50 dark:border-slate-700/50">
                        <div>
                            <h3 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100">Roster Kehadiran Kelas</h3>
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta">Monitoring Siswa Hari Ini</span>
                        </div>
                    </div>

                    @if ($rosterHariIni->isEmpty())
                        <div class="p-8 text-center text-slate-500 dark:text-slate-400 font-jakarta">Belum ada data siswa di kelas binaan.</div>
                    @else
                        <div class="space-y-3">
                            @foreach ($rosterHariIni as $s)
                                @php $absenSiswa = $s->absensi->first(); @endphp
                                <div class="bento-card rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border border-white/60 dark:border-slate-700/50 transition-all duration-300 hover:scale-[1.01]">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white font-black font-lexend flex items-center justify-center shadow-md">
                                            {{ Illuminate\Support\Str::of($s->nama)->substr(0, 1)->upper() }}
                                        </div>
                                        <div>
                                            <a href="{{ route('siswa.show', $s) }}" class="font-black font-outfit text-base text-slate-800 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">{{ $s->nama }}</a>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                @if ($s->face_descriptors_count > 0)
                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-black font-lexend bg-emerald-500 text-white uppercase tracking-wider">Face ID Active</span>
                                                @else
                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-black font-lexend bg-slate-300 dark:bg-slate-700 text-slate-600 dark:text-slate-300 uppercase tracking-wider">No Face Data</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-6">
                                        <div class="flex flex-col text-right">
                                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest font-jakarta">Masuk</span>
                                            <span class="font-lexend font-black text-slate-800 dark:text-slate-200 text-sm">
                                                {{ $absenSiswa ? \Illuminate\Support\Str::of($absenSiswa->jam_masuk)->substr(0,5) : '—:—' }}
                                            </span>
                                        </div>
                                        <div class="shrink-0">
                                            @if ($isLibur)
                                                <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend bg-slate-500 text-white uppercase tracking-widest shadow-md">Libur</span>
                                            @elseif ($absenSiswa)
                                                @php
                                                    $bgMap = [
                                                        'hadir' => 'bg-emerald-500 text-white shadow-emerald-500/30',
                                                        'terlambat' => 'bg-amber-500 text-white shadow-amber-500/30',
                                                        'izin' => 'bg-purple-500 text-white shadow-purple-500/30',
                                                        'sakit' => 'bg-purple-500 text-white shadow-purple-500/30',
                                                        'alpha' => 'bg-rose-500 text-white shadow-rose-500/30',
                                                    ];
                                                    $bgClass = $bgMap[$absenSiswa->status] ?? 'bg-slate-500 text-white';
                                                @endphp
                                                <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest shadow-md {{ $bgClass }}">{{ $absenSiswa->status }}</span>
                                            @else
                                                <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend bg-rose-500 text-white uppercase tracking-widest shadow-md shadow-rose-500/30">Alpha</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Admin Quick Actions -->
                <div class="bento-card rounded-[2.5rem] p-6 sm:p-8 shadow-xl space-y-6">
                    <div class="pb-4 border-b border-slate-200/50 dark:border-slate-700/50">
                        <h3 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100">Aksi Pintar</h3>
                        <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta">Pintasan Administrator</span>
                    </div>
                    
                    <div class="space-y-3">
                        <a href="{{ route('siswa.create') }}" class="relative flex items-center justify-center gap-2 w-full overflow-hidden bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white py-3.5 rounded-2xl font-black font-lexend shadow-lg shadow-indigo-500/20 transition-all duration-300 transform active:scale-95 text-xs uppercase tracking-wider">
                            <x-icon name="plus" class="w-4 h-4 stroke-[3]" /> Tambah Siswa Baru
                        </a>
                        <a href="{{ route('absensi.index') }}" class="flex items-center justify-center gap-2 w-full bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 py-3.5 rounded-2xl font-black font-lexend text-xs uppercase tracking-wider transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                            <x-icon name="clock" class="w-4 h-4 stroke-[2.5]" /> Rekap Absensi
                        </a>
                    </div>

                    <div class="text-xs text-slate-500 dark:text-slate-400 pt-4 border-t border-slate-200/50 dark:border-slate-700/50 space-y-3 font-jakarta">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold">Total Kelas:</span>
                            <span class="font-black text-slate-800 dark:text-slate-100 font-lexend text-sm">{{ $totalKelas }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="font-semibold">Terdaftar Wajah:</span>
                            <span class="font-black text-indigo-600 dark:text-indigo-400 font-lexend text-sm">{{ $sudahEnroll }} / {{ $totalSiswa }}</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Attendance List -->
                <div class="bento-card rounded-[2.5rem] p-6 sm:p-8 shadow-xl lg:col-span-2 space-y-5">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-200/50 dark:border-slate-700/50">
                        <div>
                            <h3 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100">Live Absensi Terbaru</h3>
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta">Umpan Realtime Kehadiran</span>
                        </div>
                    </div>

                    @if ($absenTerbaru->isEmpty())
                        <div class="p-8 text-center text-slate-500 dark:text-slate-400 font-jakarta">
                            {{ $isLibur ? 'Hari ini libur, tidak ada absensi yang diharapkan.' : 'Belum ada data absensi tercatat hari ini.' }}
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($absenTerbaru as $a)
                                <div class="bento-card rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border border-white/60 dark:border-slate-700/50 transition-all duration-300 hover:scale-[1.01]">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-black font-lexend flex items-center justify-center border border-indigo-200/50 dark:border-indigo-800/50">
                                            <x-icon name="user-circle" class="w-5 h-5 stroke-[2]" />
                                        </div>
                                        <div>
                                            <span class="font-black font-outfit text-base text-slate-800 dark:text-slate-100 block">{{ $a->siswa->nama }}</span>
                                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 font-jakarta">Kelas {{ $a->kelas->nama_kelas ?? '-' }}</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-6">
                                        <div class="flex flex-col text-right">
                                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest font-jakarta">Jam Masuk</span>
                                            <span class="font-lexend font-black text-slate-800 dark:text-slate-200 text-sm">
                                                {{ \Illuminate\Support\Str::of($a->jam_masuk)->substr(0,5) }}
                                            </span>
                                        </div>
                                        @php
                                            $bgMap = [
                                                'hadir' => 'bg-emerald-500 text-white shadow-emerald-500/30',
                                                'terlambat' => 'bg-amber-500 text-white shadow-amber-500/30',
                                                'izin' => 'bg-purple-500 text-white shadow-purple-500/30',
                                                'sakit' => 'bg-purple-500 text-white shadow-purple-500/30',
                                                'alpha' => 'bg-rose-500 text-white shadow-rose-500/30',
                                            ];
                                            $bgClass = $bgMap[$a->status] ?? 'bg-slate-500 text-white';
                                        @endphp
                                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest shadow-md {{ $bgClass }}">{{ $a->status }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-app-layout>

