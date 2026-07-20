<x-app-layout>
    <x-slot name="header">
        <h2 class="font-outfit font-bold text-2xl text-slate-800 dark:text-slate-100 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Holiday Banner -->
        @if ($isLibur)
            <div class="flex items-start gap-3 bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-150 dark:border-indigo-900/40 rounded-2xl px-5 py-4 text-sm text-indigo-800 dark:text-indigo-300 mx-4 sm:mx-0">
                <x-icon name="calendar" class="w-5 h-5 shrink-0 mt-0.5 text-indigo-600 dark:text-indigo-400" />
                <div>
                    <span class="font-outfit font-bold block text-base mb-0.5">Hari Ini Libur</span>
                    Hari ini terdaftar sebagai <strong>hari libur</strong>. Proses absensi siswa tidak diwajibkan.
                </div>
            </div>
        @endif

        <!-- Missing Homeroom Teacher Banner -->
        @if ($kelasTanpaWali > 0)
            <div class="flex items-start gap-3 bg-amber-50/50 dark:bg-amber-950/20 border border-amber-250 dark:border-amber-900/40 rounded-2xl px-5 py-4 text-sm text-amber-850 dark:text-amber-300 mx-4 sm:mx-0">
                <x-icon name="alert-circle" class="w-5 h-5 shrink-0 mt-0.5 text-amber-600 dark:text-amber-500" />
                <div class="flex-grow">
                    <span class="font-outfit font-bold block text-base mb-0.5">Konfigurasi Wali Kelas Belum Lengkap</span>
                    Ada <strong>{{ $kelasTanpaWali }} kelas</strong> yang belum ditugaskan wali kelas.
                </div>
                <a href="{{ route('kelas.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-amber-700 dark:text-amber-400 hover:underline">
                    Atur Kelas <x-icon name="arrow-right" class="w-4 h-4" />
                </a>
            </div>
        @endif

        <!-- Summary Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 px-4 sm:px-0">
            @php
                $cards = [
                    ['label' => 'Total Siswa', 'value' => $totalSiswa, 'icon' => 'users', 'color' => 'from-blue-500/10 to-blue-500/5 text-blue-600 dark:text-blue-400 border-blue-500/20', 'route' => route('siswa.index')],
                    ['label' => 'Hadir Hari Ini', 'value' => $hadir, 'icon' => 'check-circle', 'color' => 'from-green-500/10 to-green-500/5 text-green-600 dark:text-green-400 border-green-500/20', 'route' => route('absensi.index')],
                    ['label' => 'Terlambat', 'value' => $terlambat, 'icon' => 'clock', 'color' => 'from-amber-500/10 to-amber-500/5 text-amber-600 dark:text-amber-400 border-amber-500/20', 'route' => route('absensi.index')],
                    ['label' => 'Izin / Sakit', 'value' => $izinSakit, 'icon' => 'alert-circle', 'color' => 'from-purple-500/10 to-purple-500/5 text-purple-600 dark:text-purple-400 border-purple-500/20', 'route' => route('absensi.index')],
                ];
            @endphp
            @foreach ($cards as $c)
                <a href="{{ $c['route'] }}" class="glass-card rounded-2xl p-5 flex items-center gap-4 hover:scale-[1.02] hover:shadow-lg transition-all duration-300 border border-slate-200/50 dark:border-slate-800/55 shadow-sm group">
                    <div class="w-12 h-12 shrink-0 rounded-xl bg-gradient-to-br {{ $c['color'] }} flex items-center justify-center shadow-inner border">
                        <x-icon :name="$c['icon']" class="w-6 h-6" />
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider">{{ $c['label'] }}</div>
                        <div class="text-3xl font-bold font-outfit text-slate-850 dark:text-slate-50 mt-1">{{ $c['value'] }}</div>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- 7-Day Attendance Trend Chart -->
        <div class="px-4 sm:px-0">
            <div class="glass-card rounded-2xl shadow-sm p-6 border border-slate-200/50 dark:border-slate-800/55">
                <h3 class="font-outfit font-bold text-lg text-slate-800 dark:text-slate-50 mb-6">Tren Kehadiran 7 Hari Terakhir</h3>
                @php $maxJumlah = max(1, $tren7Hari->max('jumlah')); @endphp
                <div class="flex items-end gap-4 h-36 px-2">
                    @foreach ($tren7Hari as $hari)
                        <div class="flex-1 flex flex-col items-center justify-end h-full gap-2 group">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity font-outfit">{{ $hari['jumlah'] }}</span>
                            <div @class([
                                     'w-full rounded-t-lg transition-all duration-500 shadow-sm',
                                     'bg-gradient-to-t from-indigo-600 to-indigo-500 shadow-indigo-500/20 dark:shadow-indigo-500/10' => $hari['isToday'],
                                     'bg-slate-200 dark:bg-slate-800 hover:bg-indigo-300 dark:hover:bg-indigo-950/40' => ! $hari['isToday'],
                                 ])
                                 style="height: {{ $hari['jumlah'] > 0 ? max(8, round($hari['jumlah'] / $maxJumlah * 100)) : 4 }}%"></div>
                            <span @class([
                                    'text-xs font-bold font-outfit',
                                    'text-indigo-600 dark:text-indigo-400 font-extrabold' => $hari['isToday'],
                                    'text-slate-400 dark:text-slate-500' => ! $hari['isToday'],
                                ])>{{ $hari['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Bottom Grid: Homeroom vs Administrator Dashboards -->
        @if (auth()->user()->isWaliKelas())
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 px-4 sm:px-0">
                <!-- Wali Kelas Quick Info -->
                <div class="glass-card rounded-2xl shadow-sm p-6 border border-slate-200/50 dark:border-slate-800/55 space-y-5">
                    <h3 class="font-outfit font-bold text-lg text-slate-800 dark:text-slate-50 border-b dark:border-slate-800 pb-3">Kelas Binaan</h3>
                    
                    <div class="space-y-3.5">
                        @forelse ($kelasBinaan as $k)
                            <div class="flex items-center justify-between text-sm py-2 px-3 rounded-xl bg-slate-50/50 dark:bg-slate-800/20 border border-slate-100 dark:border-slate-800/50">
                                <span class="font-bold text-slate-800 dark:text-slate-100 font-outfit">{{ $k->nama_kelas }}</span>
                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $k->siswa_count }} siswa</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 dark:text-slate-400">Belum ditugaskan sebagai wali kelas manapun.</p>
                        @endforelse
                    </div>

                    <div class="pt-2 space-y-3">
                        <a href="{{ route('siswa.create') }}" class="flex items-center justify-center gap-2 w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-xl font-semibold shadow-md shadow-indigo-500/10 transition-all duration-300 transform active:scale-[0.98]">
                            <x-icon name="plus" class="w-4 h-4" />
                            Tambah Siswa
                        </a>
                        <a href="{{ route('absensi.index') }}" class="flex items-center justify-center gap-2 w-full bg-slate-100 dark:bg-slate-800/80 hover:bg-slate-200 dark:hover:bg-slate-700/80 text-slate-700 dark:text-slate-200 py-3 rounded-xl font-semibold transition-all duration-300 transform active:scale-[0.98]">
                            <x-icon name="clock" class="w-4 h-4" />
                            Rekap Absensi
                        </a>
                    </div>
                    
                    <div class="text-xs text-slate-500 dark:text-slate-400 pt-3 border-t dark:border-slate-800 space-y-2">
                        <div class="flex justify-between">
                            <span>Siswa terdaftar wajah:</span>
                            <span class="font-bold text-slate-850 dark:text-slate-100 font-outfit">{{ $sudahEnroll }} / {{ $totalSiswa }}</span>
                        </div>
                    </div>
                </div>

                <!-- Daily Class Roster -->
                <div class="glass-card rounded-2xl shadow-sm p-6 border border-slate-200/50 dark:border-slate-800/55 lg:col-span-2 space-y-4">
                    <h3 class="font-outfit font-bold text-lg text-slate-800 dark:text-slate-50">Roster Kelas Hari Ini</h3>
                    @if ($rosterHariIni->isEmpty())
                        <p class="text-sm text-slate-500 dark:text-slate-400">Belum ada siswa di kelas binaan.</p>
                    @else
                        <div class="overflow-x-auto rounded-xl border border-slate-200/50 dark:border-slate-800">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="bg-slate-50/50 dark:bg-slate-800/30 text-left text-slate-400 dark:text-slate-500 font-semibold font-outfit border-b dark:border-slate-800">
                                        <th class="py-3 px-4">Nama</th>
                                        <th class="py-3 px-4 text-center">Data Wajah</th>
                                        <th class="py-3 px-4">Jam Masuk</th>
                                        <th class="py-3 px-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-150 dark:divide-slate-800">
                                    @foreach ($rosterHariIni as $s)
                                        @php $absenSiswa = $s->absensi->first(); @endphp
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                                            <td class="py-3 px-4 font-semibold text-slate-800 dark:text-slate-100">
                                                <a href="{{ route('siswa.show', $s) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">{{ $s->nama }}</a>
                                            </td>
                                            <td class="py-3 px-4 text-center">
                                                @if ($s->face_descriptors_count > 0)
                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400 border border-green-200/30">Aktif</span>
                                                @else
                                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-600">Belum</span>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-slate-600 dark:text-slate-400 font-outfit font-medium">{{ $absenSiswa ? \Illuminate\Support\Str::of($absenSiswa->jam_masuk)->substr(0,5) : '--:--' }}</td>
                                            <td class="py-3 px-4">
                                                @if ($isLibur)
                                                    <x-status-badge status="libur" class="px-2 py-0.5 rounded-md text-[10px] font-bold" />
                                                @elseif ($absenSiswa)
                                                    <x-status-badge :status="$absenSiswa->status" class="px-2 py-0.5 rounded-md text-[10px] font-bold" />
                                                @else
                                                    <x-status-badge status="alpha" class="px-2 py-0.5 rounded-md text-[10px] font-bold" />
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 px-4 sm:px-0">
                <!-- Admin Quick Actions -->
                <div class="glass-card rounded-2xl shadow-sm p-6 border border-slate-200/50 dark:border-slate-800/55 space-y-5">
                    <h3 class="font-outfit font-bold text-lg text-slate-800 dark:text-slate-50 border-b dark:border-slate-800 pb-3">Aksi Cepat</h3>
                    
                    <div class="space-y-3">
                        <a href="{{ route('siswa.create') }}" class="flex items-center justify-center gap-2 w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-xl font-semibold shadow-md shadow-indigo-500/10 transition-all duration-300 transform active:scale-[0.98]">
                            <x-icon name="plus" class="w-4 h-4" />
                            Tambah Siswa
                        </a>
                        <a href="{{ route('absensi.index') }}" class="flex items-center justify-center gap-2 w-full bg-slate-100 dark:bg-slate-800/80 hover:bg-slate-200 dark:hover:bg-slate-700/80 text-slate-700 dark:text-slate-200 py-3 rounded-xl font-semibold transition-all duration-300 transform active:scale-[0.98]">
                            <x-icon name="clock" class="w-4 h-4" />
                            Rekap Absensi
                        </a>
                    </div>

                    <div class="text-xs text-slate-500 dark:text-slate-400 pt-3 border-t dark:border-slate-800 space-y-2">
                        <div class="flex justify-between">
                            <span>Total Kelas:</span>
                            <span class="font-bold text-slate-850 dark:text-slate-100 font-outfit">{{ $totalKelas }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Siswa terdaftar wajah:</span>
                            <span class="font-bold text-slate-850 dark:text-slate-100 font-outfit">{{ $sudahEnroll }} / {{ $totalSiswa }}</span>
                        </div>
                    </div>
                </div>

                <!-- Recent Attendance List -->
                <div class="glass-card rounded-2xl shadow-sm p-6 border border-slate-200/50 dark:border-slate-800/55 lg:col-span-2 space-y-4">
                    <h3 class="font-outfit font-bold text-lg text-slate-800 dark:text-slate-50">Absensi Terbaru Hari Ini</h3>
                    @if ($absenTerbaru->isEmpty())
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ $isLibur ? 'Hari ini libur, tidak ada absensi yang diharapkan.' : 'Belum ada absensi hari ini.' }}
                        </p>
                    @else
                        <div class="overflow-x-auto rounded-xl border border-slate-200/50 dark:border-slate-800">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="bg-slate-50/50 dark:bg-slate-800/30 text-left text-slate-400 dark:text-slate-500 font-semibold font-outfit border-b dark:border-slate-800">
                                        <th class="py-3 px-4">Nama</th>
                                        <th class="py-3 px-4">Kelas</th>
                                        <th class="py-3 px-4">Jam Masuk</th>
                                        <th class="py-3 px-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-150 dark:divide-slate-800">
                                    @foreach ($absenTerbaru as $a)
                                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/10">
                                            <td class="py-3 px-4 font-semibold text-slate-800 dark:text-slate-100">{{ $a->siswa->nama }}</td>
                                            <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                                                {{ $a->kelas->nama_kelas ?? '-' }}
                                                @if ($a->kelas?->waliKelas)
                                                    <div class="text-[10px] font-semibold text-slate-400 dark:text-slate-500">{{ $a->kelas->waliKelas->name }}</div>
                                                @endif
                                            </td>
                                            <td class="py-3 px-4 text-slate-600 dark:text-slate-400 font-outfit font-medium">{{ \Illuminate\Support\Str::of($a->jam_masuk)->substr(0,5) }}</td>
                                            <td class="py-3 px-4">
                                                <x-status-badge :status="$a->status" class="px-2 py-0.5 rounded-md text-[10px] font-bold" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
