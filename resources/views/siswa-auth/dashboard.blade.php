<x-siswa-layout>
    <div class="space-y-5">
        <!-- Student Identity Card -->
        <div class="glass-card rounded-2xl shadow-sm p-6 relative overflow-hidden transition-all duration-300 hover:shadow-md">
            <div class="absolute -right-10 -top-10 w-32 h-32 rounded-full bg-indigo-500/5 dark:bg-indigo-500/10 blur-xl"></div>
            
            <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-widest">NIS {{ $siswa->nis }}</p>
            <h1 class="font-outfit text-2xl font-bold text-slate-800 dark:text-slate-50 mt-1">{{ $siswa->nama }}</h1>

            <div class="mt-3.5 flex flex-wrap gap-2">
                @if ($siswa->faceDescriptors->isNotEmpty())
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400 border border-green-200/50 dark:border-green-900/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                        Wajah Terdaftar ({{ $siswa->faceDescriptors->count() }} sampel)
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border border-amber-200/50 dark:border-amber-900/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        Wajah Belum Terdaftar
                    </span>
                @endif

                @if ($isLibur)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-350 border border-slate-200 dark:border-slate-700/50">
                        <x-icon name="calendar" class="w-3.5 h-3.5" /> 
                        Hari Ini Libur
                    </span>
                @elseif ($absenHariIni)
                    <x-status-badge :status="$absenHariIni->status" class="px-3 py-1 text-xs font-semibold rounded-full" />
                @else
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-450 border border-rose-200/50 dark:border-rose-900/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                        Belum Absen Hari Ini
                    </span>
                @endif
            </div>

            <!-- Attendance Step Tracker -->
            <div class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-800">
                @php
                    $izinSakit = in_array($absenHariIni->status ?? null, ['izin', 'sakit'], true);
                    $sudahMasuk = (bool) ($absenHariIni->jam_masuk ?? null);
                    $sudahPulang = (bool) ($absenHariIni->jam_pulang ?? null);
                    $absenSelesai = ($sudahMasuk && $sudahPulang) || $izinSakit;
                    $absenNonaktif = $isLibur || $absenSelesai;
                @endphp
                <div class="flex items-center justify-between px-2">
                    <!-- Step 1: Masuk -->
                    <div class="flex flex-col items-center w-20">
                        <div @class([
                                'w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300 border-2',
                                'bg-green-500/10 text-green-600 border-green-500/30 dark:bg-green-500/20 dark:text-green-400 dark:border-green-500/40 glow-green' => $sudahMasuk,
                                'bg-slate-50 text-slate-400 border-slate-200 dark:bg-slate-800/40 dark:text-slate-500 dark:border-slate-700/50' => ! $sudahMasuk,
                            ])>
                            @if ($sudahMasuk)
                                <x-icon name="check" class="w-5 h-5" />
                            @else
                                <span class="text-sm font-semibold font-outfit">1</span>
                            @endif
                        </div>
                        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-2">Masuk</span>
                        <span @class([
                                'text-xs font-bold mt-0.5 font-outfit',
                                'text-slate-800 dark:text-slate-250' => $sudahMasuk,
                                'text-slate-400 dark:text-slate-600' => ! $sudahMasuk,
                            ])>
                            {{ $sudahMasuk ? \Illuminate\Support\Str::of($absenHariIni->jam_masuk)->substr(0,5) : '—:—' }}
                        </span>
                    </div>

                    <!-- Connection Line 1 -->
                    <div @class([
                            'flex-grow h-0.5 mx-2 -mt-5 transition-all duration-500 rounded-full',
                            'bg-gradient-to-r from-green-500/40 to-slate-200 dark:from-green-500/20 dark:to-slate-800' => $sudahMasuk && !$sudahPulang,
                            'bg-green-500/40 dark:bg-green-500/20' => $sudahPulang,
                            'bg-slate-200 dark:bg-slate-800' => !$sudahMasuk,
                        ])></div>

                    <!-- Kiosk Trigger Button -->
                    @if ($absenNonaktif)
                        <div class="flex flex-col items-center shrink-0 -mt-2" title="{{ $isLibur ? 'Absensi tidak aktif saat hari libur' : ($izinSakit ? 'Kamu tercatat izin/sakit hari ini' : 'Kamu sudah absen masuk & pulang hari ini') }}">
                            <div @class([
                                    'w-14 h-14 rounded-full flex items-center justify-center shadow-md cursor-not-allowed border',
                                    'bg-slate-100 text-slate-400 border-slate-200 dark:bg-slate-800 dark:text-slate-500 dark:border-slate-700' => $isLibur || $izinSakit,
                                    'bg-green-500/10 text-green-600 border-green-500/30 dark:bg-green-500/20 dark:text-green-400 dark:border-green-500/40' => !$isLibur && !$izinSakit,
                                ])>
                                <x-icon :name="$isLibur ? 'x-circle' : ($izinSakit ? 'clipboard' : 'sparkles')" class="w-6 h-6" />
                            </div>
                            <span @class([
                                    'text-[10px] font-bold mt-2 font-outfit uppercase tracking-wider',
                                    'text-slate-400 dark:text-slate-500' => $isLibur || $izinSakit,
                                    'text-green-600 dark:text-green-400' => !$isLibur && !$izinSakit,
                                ])>
                                {{ $isLibur ? 'Libur' : ($izinSakit ? ucfirst($absenHariIni->status) : 'Selesai') }}
                            </span>
                        </div>
                    @else
                        <a href="{{ route('siswa.absen') }}" class="flex flex-col items-center shrink-0 -mt-2 group">
                            <div class="w-14 h-14 rounded-full flex items-center justify-center shadow-lg shadow-indigo-500/20 dark:shadow-indigo-500/10 transition-all duration-300 bg-gradient-to-br from-indigo-600 to-indigo-500 text-white hover:scale-105 active:scale-95 glow-indigo relative">
                                <span class="absolute inset-0 rounded-full bg-indigo-500/30 animate-ping group-hover:hidden"></span>
                                <x-icon name="camera" class="w-6 h-6 relative z-10" />
                            </div>
                            <span class="text-[10px] font-bold mt-2 text-indigo-600 dark:text-indigo-400 uppercase tracking-wider group-hover:underline">
                                {{ $sudahMasuk ? 'Absen Pulang' : 'Absen Masuk' }}
                            </span>
                        </a>
                    @endif

                    <!-- Connection Line 2 -->
                    <div @class([
                            'flex-grow h-0.5 mx-2 -mt-5 transition-all duration-500 rounded-full',
                            'bg-gradient-to-r from-slate-200 to-green-500/40 dark:from-slate-800 dark:to-green-500/20' => $sudahPulang && !$sudahMasuk, /* Fallback */
                            'bg-green-500/40 dark:bg-green-500/20' => $sudahPulang,
                            'bg-slate-200 dark:bg-slate-800' => !$sudahPulang,
                        ])></div>

                    <!-- Step 2: Pulang -->
                    <div class="flex flex-col items-center w-20">
                        <div @class([
                                'w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300 border-2',
                                'bg-green-500/10 text-green-600 border-green-500/30 dark:bg-green-500/20 dark:text-green-400 dark:border-green-500/40 glow-green' => $sudahPulang,
                                'bg-indigo-500/5 text-indigo-600 border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-400 dark:border-indigo-500/30 glow-indigo' => $sudahMasuk && !$sudahPulang,
                                'bg-slate-50 text-slate-400 border-slate-200 dark:bg-slate-800/40 dark:text-slate-500 dark:border-slate-700/50' => !$sudahMasuk,
                            ])>
                            @if ($sudahPulang)
                                <x-icon name="check" class="w-5 h-5" />
                            @else
                                <span class="text-sm font-semibold font-outfit">2</span>
                            @endif
                        </div>
                        <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 mt-2">Pulang</span>
                        <span @class([
                                'text-xs font-bold mt-0.5 font-outfit',
                                'text-slate-800 dark:text-slate-250' => $sudahPulang,
                                'text-slate-400 dark:text-slate-600' => ! $sudahPulang,
                            ])>
                            {{ $sudahPulang ? \Illuminate\Support\Str::of($absenHariIni->jam_pulang)->substr(0,5) : '—:—' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Weekly Tracker Indicator -->
        <div class="glass-card rounded-2xl shadow-sm p-5 transition-all duration-300 hover:shadow-md">
            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-3">Linimasa Kehadiran Minggu Ini</div>
            @php
                $warnaHari = [
                    'hadir' => 'bg-green-500 shadow-sm shadow-green-500/20',
                    'terlambat' => 'bg-amber-500 shadow-sm shadow-amber-500/20',
                    'izin' => 'bg-blue-500 shadow-sm shadow-blue-500/20',
                    'sakit' => 'bg-purple-500 shadow-sm shadow-purple-500/20',
                    'alpha' => 'bg-rose-500 shadow-sm shadow-rose-500/20',
                ];
            @endphp
            <div class="flex gap-2">
                @foreach ($mingguIni as $hari)
                    <div class="flex-1 flex flex-col items-center gap-2">
                        <div @class([
                                'w-full h-8 rounded-lg transition-all duration-300',
                                $warnaHari[$hari['status']] ?? 'bg-slate-100 dark:bg-slate-800',
                                'ring-2 ring-indigo-500 dark:ring-indigo-400 scale-[1.03] ring-offset-2 dark:ring-offset-slate-950' => $hari['isToday'],
                            ])
                             title="{{ $hari['label'] }}: {{ ucfirst($hari['status'] ?: 'belum ada catatan') }}"></div>
                        <span @class([
                                'text-[10px] font-bold font-outfit',
                                'text-indigo-600 dark:text-indigo-400 font-extrabold' => $hari['isToday'],
                                'text-slate-300 dark:text-slate-700' => $hari['isFuture'],
                                'text-slate-500 dark:text-slate-400' => !$hari['isFuture'] && !$hari['isToday'],
                            ])>{{ $hari['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Attendance Stats Button Link -->
        <a href="{{ route('siswa.riwayat', ['bulan' => now()->format('Y-m')]) }}"
           class="block glass-card rounded-2xl shadow-sm p-5 hover:shadow-md hover:border-slate-300/50 dark:hover:border-slate-750 transition-all duration-300 group">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-outfit font-bold text-slate-800 dark:text-slate-100 text-sm tracking-wide">Statistik Kehadiran Bulan Ini</h3>
                <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 group-hover:translate-x-1 transition-transform flex items-center gap-1">
                    Detail Riwayat <x-icon name="arrow-right" class="w-3.5 h-3.5" />
                </span>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div class="text-center bg-slate-50/50 dark:bg-slate-800/20 py-2.5 rounded-xl border border-slate-100 dark:border-slate-800/50">
                    <div class="w-8 h-8 mx-auto rounded-full bg-green-100 text-green-600 dark:bg-green-950/30 dark:text-green-400 flex items-center justify-center shadow-inner">
                        <x-icon name="check-circle" class="w-4 h-4" />
                    </div>
                    <div class="text-lg font-bold text-slate-800 dark:text-slate-100 mt-1.5 font-outfit">{{ $statistikBulanIni['hadir'] }}</div>
                    <div class="text-[10px] font-semibold text-slate-400 dark:text-slate-550 uppercase tracking-wider">Hadir</div>
                </div>
                <div class="text-center bg-slate-50/50 dark:bg-slate-800/20 py-2.5 rounded-xl border border-slate-100 dark:border-slate-800/50">
                    <div class="w-8 h-8 mx-auto rounded-full bg-amber-100 text-amber-600 dark:bg-amber-950/30 dark:text-amber-400 flex items-center justify-center shadow-inner">
                        <x-icon name="clock" class="w-4 h-4" />
                    </div>
                    <div class="text-lg font-bold text-slate-800 dark:text-slate-100 mt-1.5 font-outfit">{{ $statistikBulanIni['terlambat'] }}</div>
                    <div class="text-[10px] font-semibold text-slate-400 dark:text-slate-550 uppercase tracking-wider">Terlambat</div>
                </div>
                <div class="text-center bg-slate-50/50 dark:bg-slate-800/20 py-2.5 rounded-xl border border-slate-100 dark:border-slate-800/50">
                    <div class="w-8 h-8 mx-auto rounded-full bg-purple-100 text-purple-600 dark:bg-purple-950/30 dark:text-purple-400 flex items-center justify-center shadow-inner">
                        <x-icon name="alert-circle" class="w-4 h-4" />
                    </div>
                    <div class="text-lg font-bold text-slate-800 dark:text-slate-100 mt-1.5 font-outfit">{{ $statistikBulanIni['izinSakit'] }}</div>
                    <div class="text-[10px] font-semibold text-slate-400 dark:text-slate-550 uppercase tracking-wider">Izin/Sakit</div>
                </div>
            </div>
        </a>

        <!-- Feature Grid (Enroll face & View History) -->
        <div class="grid grid-cols-2 gap-4">
            <a href="{{ route('siswa.wajah') }}"
               class="glass-card rounded-2xl shadow-sm p-4 hover:shadow-md hover:border-slate-300/50 dark:hover:border-slate-750 transition-all duration-300 flex flex-col justify-between group">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400 flex items-center justify-center shadow-inner transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                    <x-icon name="camera" class="w-5 h-5" />
                </div>
                <div class="mt-6">
                    <div class="font-outfit font-bold text-slate-800 dark:text-slate-100 text-sm">Sampel Wajah</div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-450 mt-1 leading-normal">Rekam & perbarui wajah untuk scan otomatis.</p>
                </div>
            </a>

            <a href="{{ route('siswa.riwayat') }}"
               class="glass-card rounded-2xl shadow-sm p-4 hover:shadow-md hover:border-slate-300/50 dark:hover:border-slate-750 transition-all duration-300 flex flex-col justify-between group">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400 flex items-center justify-center shadow-inner transition-colors group-hover:bg-indigo-600 group-hover:text-white">
                    <x-icon name="clock" class="w-5 h-5" />
                </div>
                <div class="mt-6">
                    <div class="font-outfit font-bold text-slate-800 dark:text-slate-100 text-sm">Riwayat Absen</div>
                    <p class="text-[11px] text-slate-500 dark:text-slate-450 mt-1 leading-normal">Pantau rekapitulasi presensi harian lengkap.</p>
                </div>
            </a>
        </div>
    </div>
</x-siswa-layout>
