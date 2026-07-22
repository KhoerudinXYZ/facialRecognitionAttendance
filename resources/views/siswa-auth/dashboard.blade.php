<x-siswa-layout>
    <div class="space-y-4">
        <!-- ── ULTRA PREMIUM BENTO GRID ─────────────────── -->
        
        <!-- BENTO 1: Main Identity Card (Full width on mobile, taking primary focus) -->
        <div class="bento-card rounded-[2rem] p-6 relative overflow-hidden transition-all duration-500 hover:shadow-2xl hover:-translate-y-1 group">
            <!-- Massive Watermark Text Background -->
            <div class="absolute -right-8 -bottom-10 text-[100px] sm:text-[140px] font-black text-slate-900/[0.03] dark:text-white/[0.02] font-lexend pointer-events-none tracking-tighter leading-none select-none transition-transform duration-700 group-hover:scale-105">
                {{ Str::upper(explode(' ', $siswa->nama)[0] ?? 'STUDENT') }}
            </div>
            
            <div class="relative z-10">
                <div class="flex justify-between items-start mb-6">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] sm:text-xs font-black font-lexend bg-indigo-50/80 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-500/20 shadow-sm backdrop-blur-md uppercase tracking-widest">
                        NIS {{ $siswa->nis }}
                    </span>
                    <span class="text-[10px] sm:text-xs font-black font-lexend text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                        {{ now()->translatedFormat('d M Y') }}
                    </span>
                </div>

                <div class="flex items-center gap-4 sm:gap-5">
                    <!-- Pro Avatar with Dynamic Glow -->
                    <div class="relative shrink-0 group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute inset-0 bg-indigo-500 rounded-full blur-xl opacity-30 group-hover:opacity-60 transition-opacity duration-500"></div>
                        @if ($siswa->foto)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($siswa->foto) }}"
                                 alt="{{ $siswa->nama }}"
                                 class="relative w-16 h-16 sm:w-20 sm:h-20 rounded-full object-cover ring-4 ring-white/80 dark:ring-slate-800/80 shadow-xl">
                        @else
                            <div class="relative w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 ring-4 ring-white/80 dark:ring-slate-800/80 flex items-center justify-center text-white font-black font-lexend text-3xl shadow-xl">
                                {{ Illuminate\Support\Str::of($siswa->nama)->substr(0, 1)->upper() }}
                            </div>
                        @endif
                        
                        <!-- Face Enrolled Status Attached to Avatar -->
                        <div class="absolute -bottom-1 -right-1 w-6 h-6 sm:w-7 sm:h-7 {{ $siswa->faceDescriptors->isNotEmpty() ? 'bg-emerald-500' : 'bg-amber-500' }} border-[3px] border-white dark:border-slate-900 rounded-full flex items-center justify-center shadow-lg"
                             title="{{ $siswa->faceDescriptors->isNotEmpty() ? 'Wajah Terdaftar' : 'Wajah Belum Terdaftar' }}">
                            @if($siswa->faceDescriptors->isNotEmpty())
                                <x-icon name="check" class="w-3.5 h-3.5 text-white stroke-[4]" />
                            @else
                                <x-icon name="alert-circle" class="w-3.5 h-3.5 text-white stroke-[3]" />
                            @endif
                        </div>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <!-- Premium typography for Name -->
                        <h1 class="font-outfit text-2xl sm:text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-slate-900 to-slate-600 dark:from-white dark:to-slate-400 tracking-tight leading-tight truncate">
                            {{ $siswa->nama }}
                        </h1>
                        <p class="text-xs sm:text-sm font-jakarta font-bold text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ $siswa->kelas->nama_kelas ?? 'Kelas tidak diketahui' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- BENTO 2: Daily Attendance Journey (Masuk -> Kamera -> Pulang) -->
        <div class="bento-card rounded-[2rem] p-6 sm:p-8 relative overflow-hidden group">
            <!-- Subtle glow in the center behind the camera -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-48 h-48 bg-indigo-500/10 blur-3xl rounded-full pointer-events-none transition-all duration-500 group-hover:bg-indigo-500/20"></div>

            <div class="flex items-center justify-between mb-8 relative z-10">
                <span class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta">Siklus Kehadiran Harian</span>
                @if($isLibur)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black font-lexend bg-slate-100 dark:bg-slate-800 text-slate-500 uppercase tracking-widest border border-slate-200 dark:border-slate-700 shadow-sm">Libur</span>
                @elseif($absenHariIni)
                    @php
                        $badgeColors = [
                            'hadir' => 'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                            'terlambat' => 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                            'izin' => 'bg-purple-50 text-purple-600 border-purple-200 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/20',
                            'sakit' => 'bg-purple-50 text-purple-600 border-purple-200 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/20',
                            'alpha' => 'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20',
                        ];
                        $badgeClass = $badgeColors[$absenHariIni->status] ?? 'bg-slate-50 text-slate-600 border-slate-200';
                    @endphp
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest border shadow-sm {{ $badgeClass }}">
                        Status: {{ $absenHariIni->status }}
                    </span>
                @endif
            </div>

            <!-- Attendance Step Tracker Premium -->
            @php
                $izinSakit = in_array($absenHariIni->status ?? null, ['izin', 'sakit'], true);
                $sudahMasuk = (bool) ($absenHariIni->jam_masuk ?? null);
                $sudahPulang = (bool) ($absenHariIni->jam_pulang ?? null);
                $absenSelesai = ($sudahMasuk && $sudahPulang) || $izinSakit;
                $absenNonaktif = $isLibur || $absenSelesai;
            @endphp
            <div class="flex items-center justify-between relative z-10 px-2 sm:px-4">
                <!-- Step 1: Masuk -->
                <div class="flex flex-col items-center w-24">
                    <div @class([
                            'w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center transition-all duration-500 border shadow-lg z-10',
                            'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white border-emerald-400 shadow-emerald-500/40 scale-105' => $sudahMasuk,
                            'bg-white/80 dark:bg-slate-800/80 text-slate-400 border-white dark:border-slate-700 backdrop-blur-md' => ! $sudahMasuk,
                        ])>
                        @if ($sudahMasuk)
                            <x-icon name="check" class="w-6 h-6 sm:w-7 sm:h-7 stroke-[3]" />
                        @else
                            <span class="text-xl sm:text-2xl font-black font-lexend opacity-50">1</span>
                        @endif
                    </div>
                    <span class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-4 font-jakarta">Masuk</span>
                    <span @class([
                            'text-sm sm:text-base font-black mt-1 font-lexend tabular-nums tracking-tight',
                            'text-slate-900 dark:text-white drop-shadow-sm' => $sudahMasuk,
                            'text-slate-300 dark:text-slate-600' => ! $sudahMasuk,
                        ])>
                        {{ $sudahMasuk ? \Illuminate\Support\Str::of($absenHariIni->jam_masuk)->substr(0,5) : '—:—' }}
                    </span>
                </div>

                <!-- Connection Line 1 -->
                <div @class([
                        'flex-grow h-1.5 mx-2 sm:mx-4 -mt-12 transition-all duration-700 rounded-full shadow-[inset_0_1px_2px_rgba(0,0,0,0.1)] dark:shadow-none',
                        'bg-gradient-to-r from-emerald-500 to-indigo-500 shadow-[0_0_10px_rgba(16,185,129,0.3)]' => $sudahMasuk && !$sudahPulang,
                        'bg-emerald-500' => $sudahPulang,
                        'bg-slate-200/80 dark:bg-slate-800' => !$sudahMasuk,
                    ])></div>

                <!-- Signature Camera Kiosk Trigger Button (Center) -->
                @if ($absenNonaktif)
                    <div class="flex flex-col items-center shrink-0 -mt-5 z-20" title="{{ $isLibur ? 'Absensi tidak aktif saat hari libur' : ($izinSakit ? 'Kamu tercatat izin/sakit hari ini' : 'Kamu sudah absen masuk & pulang hari ini') }}">
                        <div @class([
                                'w-20 h-20 sm:w-24 sm:h-24 rounded-[2rem] flex items-center justify-center shadow-xl border-2 backdrop-blur-md',
                                'bg-white/50 text-slate-400 border-white/60 dark:bg-slate-800/50 dark:text-slate-500 dark:border-slate-700/50' => $isLibur || $izinSakit,
                                'bg-emerald-50/80 text-emerald-500 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800/50' => !$isLibur && !$izinSakit,
                            ])>
                            <x-icon :name="$isLibur ? 'calendar' : ($izinSakit ? 'alert-circle' : 'check-circle')" class="w-10 h-10 stroke-[2]" />
                        </div>
                    </div>
                @else
                    <a href="{{ route('siswa.absen') }}" class="flex flex-col items-center shrink-0 -mt-5 group relative z-20">
                        <!-- Intense pulse effect behind the massive center button -->
                        <div class="absolute inset-0 bg-indigo-500 rounded-[2rem] blur-xl opacity-40 animate-pulse-scan group-hover:opacity-70 transition-opacity duration-300"></div>
                        
                        <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-[2rem] flex flex-col items-center justify-center shadow-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-600 text-white hover:scale-105 active:scale-95 transition-all duration-300 relative border border-white/20 overflow-hidden">
                            <!-- Techy corner accents inside button -->
                            <div class="absolute top-2 left-2 w-2 h-2 border-t-2 border-l-2 border-white/40"></div>
                            <div class="absolute bottom-2 right-2 w-2 h-2 border-b-2 border-r-2 border-white/40"></div>
                            
                            <x-icon name="camera" class="w-8 h-8 sm:w-10 sm:h-10 relative z-10 stroke-[2.5] drop-shadow-lg group-hover:scale-110 transition-transform duration-500" />
                        </div>
                        <div class="absolute -bottom-8 whitespace-nowrap">
                            <span class="text-[11px] sm:text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest font-lexend group-hover:underline drop-shadow-sm">
                                {{ $sudahMasuk ? 'Absen Pulang' : 'Absen Masuk' }}
                            </span>
                        </div>
                    </a>
                @endif

                <!-- Connection Line 2 -->
                <div @class([
                        'flex-grow h-1.5 mx-2 sm:mx-4 -mt-12 transition-all duration-700 rounded-full shadow-[inset_0_1px_2px_rgba(0,0,0,0.1)] dark:shadow-none',
                        'bg-gradient-to-r from-slate-200 to-emerald-500 dark:from-slate-800 dark:to-emerald-500' => $sudahPulang && !$sudahMasuk,
                        'bg-emerald-500' => $sudahPulang,
                        'bg-slate-200/80 dark:bg-slate-800' => !$sudahPulang,
                    ])></div>

                <!-- Step 2: Pulang -->
                <div class="flex flex-col items-center w-24">
                    <div @class([
                            'w-12 h-12 sm:w-14 sm:h-14 rounded-2xl flex items-center justify-center transition-all duration-500 border shadow-lg z-10',
                            'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white border-emerald-400 shadow-emerald-500/40 scale-105' => $sudahPulang,
                            'bg-indigo-50 text-indigo-600 border-white shadow-indigo-500/20 dark:bg-indigo-500/20 dark:text-indigo-400 dark:border-indigo-500/30 backdrop-blur-md' => $sudahMasuk && !$sudahPulang,
                            'bg-white/80 dark:bg-slate-800/80 text-slate-400 border-white dark:border-slate-700 backdrop-blur-md' => ! $sudahMasuk,
                        ])>
                        @if ($sudahPulang)
                            <x-icon name="check" class="w-6 h-6 sm:w-7 sm:h-7 stroke-[3]" />
                        @else
                            <span class="text-xl sm:text-2xl font-black font-lexend opacity-50">2</span>
                        @endif
                    </div>
                    <span class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mt-4 font-jakarta">Pulang</span>
                    <span @class([
                            'text-sm sm:text-base font-black mt-1 font-lexend tabular-nums tracking-tight',
                            'text-slate-900 dark:text-white drop-shadow-sm' => $sudahPulang,
                            'text-slate-300 dark:text-slate-600' => ! $sudahPulang,
                        ])>
                        {{ $sudahPulang ? \Illuminate\Support\Str::of($absenHariIni->jam_pulang)->substr(0,5) : '—:—' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- BENTO 4: Continuous Timeline Journey -->
        <div class="bento-card rounded-[2rem] p-5 sm:p-6 transition-all duration-300">
            <div class="flex items-center justify-between mb-6">
                <span class="text-[11px] font-black text-slate-400 dark:text-slate-450 uppercase tracking-widest font-jakarta">Perjalanan Minggu Ini</span>
                <span class="text-xs font-black text-indigo-600 dark:text-indigo-400 font-lexend bg-indigo-50/80 dark:bg-indigo-500/10 px-3 py-1 rounded-full border border-indigo-200/50 dark:border-indigo-500/20 shadow-sm backdrop-blur-md">
                    {{ \Carbon\Carbon::now()->startOfWeek()->translatedFormat('d') }} - {{ \Carbon\Carbon::now()->endOfWeek()->translatedFormat('d M') }}
                </span>
            </div>
            
            <div class="relative px-2 sm:px-4">
                <!-- Track Line Background -->
                <div class="absolute top-1/2 left-4 right-4 h-1.5 -translate-y-1/2 bg-slate-200/60 dark:bg-slate-700/50 rounded-full z-0"></div>
                
                <!-- Track Line Progress (Green) -->
                @php
                    $todayIndex = collect($mingguIni)->search(fn($hari) => $hari['isToday']);
                    $progressPercent = $todayIndex !== false ? ($todayIndex / (count($mingguIni) - 1)) * 100 : 0;
                @endphp
                <div class="absolute top-1/2 left-4 h-1.5 -translate-y-1/2 bg-gradient-to-r from-emerald-400 to-indigo-500 rounded-full z-0 transition-all duration-1000 shadow-[0_0_10px_rgba(16,185,129,0.5)]" 
                     style="width: calc({{ $progressPercent }}% - 1rem);"></div>

                <div class="flex justify-between relative z-10">
                    @foreach ($mingguIni as $index => $hari)
                        @php
                            $status = $hari['status'];
                            if (!$status && !$hari['isFuture'] && !$hari['isToday']) {
                                $status = 'alpha';
                            }
                            
                            $nodeClasses = 'w-8 h-8 sm:w-10 sm:h-10 rounded-full flex items-center justify-center font-black font-lexend transition-all duration-300 border-2 ';
                            $textClasses = 'text-[10px] sm:text-[11px] font-bold font-lexend mt-2 transition-all duration-300 ';
                            
                            if ($hari['isFuture']) {
                                $nodeClasses .= 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-300 dark:text-slate-600 scale-75';
                                $textClasses .= 'text-slate-300 dark:text-slate-600 opacity-50';
                                $icon = '•';
                            } elseif ($hari['isToday']) {
                                $nodeClasses .= 'bg-indigo-600 border-indigo-400 text-white shadow-[0_0_20px_rgba(79,70,229,0.5)] ring-4 ring-indigo-100 dark:ring-indigo-900/30 scale-110';
                                $textClasses .= 'text-indigo-600 dark:text-indigo-400 font-black';
                                $icon = '✓'; // Assuming today is ongoing or done. Real logic uses $status below.
                            } else {
                                // Past days
                                if ($status === 'hadir') {
                                    $nodeClasses .= 'bg-emerald-500 border-emerald-400 text-white shadow-[0_0_15px_rgba(16,185,129,0.4)]';
                                    $icon = '✓';
                                } elseif ($status === 'terlambat') {
                                    $nodeClasses .= 'bg-amber-500 border-amber-400 text-white shadow-[0_0_15px_rgba(245,158,11,0.4)]';
                                    $icon = '!';
                                } elseif (in_array($status, ['izin', 'sakit'])) {
                                    $nodeClasses .= 'bg-purple-500 border-purple-400 text-white shadow-[0_0_15px_rgba(168,85,247,0.4)]';
                                    $icon = 'i';
                                } elseif ($status === 'alpha') {
                                    $nodeClasses .= 'bg-rose-500 border-rose-400 text-white shadow-[0_0_15px_rgba(244,63,94,0.4)]';
                                    $icon = '×';
                                } else {
                                    $nodeClasses .= 'bg-slate-200 dark:bg-slate-700 border-slate-300 dark:border-slate-600 text-slate-500';
                                    $icon = '•';
                                }
                                $textClasses .= 'text-slate-500 dark:text-slate-400';
                            }

                            // Override icon for today based on real status if it exists
                            if ($hari['isToday'] && $status) {
                                if ($status === 'hadir') $icon = '✓';
                                elseif ($status === 'terlambat') $icon = '!';
                                elseif (in_array($status, ['izin', 'sakit'])) $icon = 'i';
                                elseif ($status === 'alpha') $icon = '×';
                            }
                        @endphp
                        
                        <div class="flex flex-col items-center group cursor-default" title="{{ $hari['label'] }}: {{ ucfirst($status ?: 'Menunggu') }}">
                            <div class="{{ $nodeClasses }} group-hover:scale-110">
                                {{ $icon }}
                            </div>
                            <span class="{{ $textClasses }}">{{ $hari['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- BENTO 5: Stats Quick View -->
        <a href="{{ route('siswa.riwayat', ['bulan' => now()->format('Y-m')]) }}"
           class="block bento-card rounded-[2rem] p-5 sm:p-6 hover:shadow-xl transition-all duration-300 group active:scale-95">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="font-lexend font-black text-slate-900 dark:text-slate-50 text-base sm:text-lg tracking-tight">Statistik Kehadiran</h3>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-jakarta font-semibold">Bulan {{ now()->translatedFormat('F Y') }}</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-indigo-50 group-hover:text-indigo-600 dark:group-hover:bg-indigo-500/20 dark:group-hover:text-indigo-400 transition-colors">
                    <x-icon name="chevron-right" class="w-5 h-5 stroke-[3] group-hover:translate-x-0.5 transition-transform" />
                </div>
            </div>
            
            <div class="grid grid-cols-3 gap-3">
                <div class="flex flex-col items-center justify-center py-4 rounded-2xl bg-gradient-to-b from-white/50 to-white/10 dark:from-slate-800/50 dark:to-slate-800/10 border border-white/60 dark:border-slate-700/50 shadow-sm backdrop-blur-sm group-hover:-translate-y-1 transition-transform duration-300 delay-75">
                    <span class="text-2xl sm:text-3xl font-black text-emerald-500 font-outfit tabular-nums leading-none drop-shadow-sm">{{ $statistikBulanIni['hadir'] }}</span>
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest font-jakarta mt-2">Hadir</span>
                </div>
                <div class="flex flex-col items-center justify-center py-4 rounded-2xl bg-gradient-to-b from-white/50 to-white/10 dark:from-slate-800/50 dark:to-slate-800/10 border border-white/60 dark:border-slate-700/50 shadow-sm backdrop-blur-sm group-hover:-translate-y-1 transition-transform duration-300 delay-100">
                    <span class="text-2xl sm:text-3xl font-black text-amber-500 font-outfit tabular-nums leading-none drop-shadow-sm">{{ $statistikBulanIni['terlambat'] }}</span>
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest font-jakarta mt-2">Telat</span>
                </div>
                <div class="flex flex-col items-center justify-center py-4 rounded-2xl bg-gradient-to-b from-white/50 to-white/10 dark:from-slate-800/50 dark:to-slate-800/10 border border-white/60 dark:border-slate-700/50 shadow-sm backdrop-blur-sm group-hover:-translate-y-1 transition-transform duration-300 delay-150">
                    <span class="text-2xl sm:text-3xl font-black text-purple-500 font-outfit tabular-nums leading-none drop-shadow-sm">{{ $statistikBulanIni['izinSakit'] }}</span>
                    <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest font-jakarta mt-2">Izin</span>
                </div>
            </div>
        </a>
    </div>
</x-siswa-layout>

