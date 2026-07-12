<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Dashboard</h2>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if ($isLibur)
            <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3 text-sm text-gray-600 dark:text-gray-300 mx-4 sm:mx-0">
                <x-icon name="x-circle" class="w-4 h-4 shrink-0" />
                Hari ini terdaftar sebagai <strong>hari libur</strong> — siswa tidak diwajibkan absen.
            </div>
        @endif

        @if ($kelasTanpaWali > 0)
            <div class="flex items-center gap-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg px-4 py-3 text-sm text-yellow-800 dark:text-yellow-300 mx-4 sm:mx-0">
                <x-icon name="x-circle" class="w-4 h-4 shrink-0" />
                {{ $kelasTanpaWali }} kelas belum punya wali kelas.
                <a href="{{ route('kelas.index') }}" class="underline font-medium hover:text-yellow-900 dark:hover:text-yellow-200">Atur di halaman Kelas</a>.
            </div>
        @endif

        {{-- Kartu statistik --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 px-4 sm:px-0">
            @php
                $cards = [
                    ['label' => 'Total Siswa', 'value' => $totalSiswa, 'icon' => 'users', 'badge' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400', 'route' => route('siswa.index')],
                    ['label' => 'Hadir Hari Ini', 'value' => $hadir, 'icon' => 'check-circle', 'badge' => 'bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400', 'route' => route('absensi.index')],
                    ['label' => 'Terlambat', 'value' => $terlambat, 'icon' => 'clock', 'badge' => 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/40 dark:text-yellow-400', 'route' => route('absensi.index')],
                    ['label' => 'Izin / Sakit', 'value' => $izinSakit, 'icon' => 'clipboard', 'badge' => 'bg-purple-100 text-purple-600 dark:bg-purple-900/40 dark:text-purple-400', 'route' => route('absensi.index')],
                ];
            @endphp
            @foreach ($cards as $c)
                <a href="{{ $c['route'] }}" class="bg-white dark:bg-gray-800 rounded-lg shadow p-5 flex items-center gap-4 hover:shadow-md transition">
                    <div class="w-12 h-12 shrink-0 rounded-full {{ $c['badge'] }} flex items-center justify-center">
                        <x-icon :name="$c['icon']" class="w-6 h-6" />
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $c['label'] }}</div>
                        <div class="text-2xl font-semibold text-gray-800 dark:text-gray-100">{{ $c['value'] }}</div>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- Tren 7 hari terakhir --}}
        <div class="px-4 sm:px-0">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
                <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">Tren Kehadiran 7 Hari Terakhir</h3>
                @php $maxJumlah = max(1, $tren7Hari->max('jumlah')); @endphp
                <div class="flex items-end gap-3 h-28">
                    @foreach ($tren7Hari as $hari)
                        <div class="flex-1 flex flex-col items-center justify-end h-full gap-1.5">
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $hari['jumlah'] }}</span>
                            <div class="w-full rounded-t-md transition-all {{ $hari['isToday'] ? 'bg-indigo-600' : 'bg-indigo-200 dark:bg-indigo-900/60' }}"
                                 style="height: {{ $hari['jumlah'] > 0 ? max(8, round($hari['jumlah'] / $maxJumlah * 100)) : 4 }}%"></div>
                            <span @class([
                                    'text-xs',
                                    'font-semibold text-indigo-600 dark:text-indigo-400' => $hari['isToday'],
                                    'text-gray-500 dark:text-gray-400' => ! $hari['isToday'],
                                ])>{{ $hari['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        @if (auth()->user()->isWaliKelas())
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 px-4 sm:px-0">
                {{-- Kelas binaan --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">Kelas Binaan</h3>
                    @forelse ($kelasBinaan as $k)
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-800 dark:text-gray-100">{{ $k->nama_kelas }}</span>
                            <span class="text-gray-500 dark:text-gray-400">{{ $k->siswa_count }} siswa</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ditugaskan sebagai wali kelas manapun.</p>
                    @endforelse

                    <a href="{{ route('siswa.create') }}" class="flex items-center justify-center gap-2 w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg font-medium">
                        <x-icon name="users" class="w-5 h-5" />
                        Tambah Siswa
                    </a>
                    <a href="{{ route('absensi.index') }}" class="flex items-center justify-center gap-2 w-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 py-3 rounded-lg font-medium">
                        <x-icon name="clock" class="w-5 h-5" />
                        Lihat Rekap Absensi
                    </a>
                    <div class="text-sm text-gray-500 dark:text-gray-400 pt-2 border-t dark:border-gray-700">
                        <div class="flex justify-between py-1"><span>Siswa sudah enroll wajah</span><span class="font-medium text-gray-800 dark:text-gray-100">{{ $sudahEnroll }} / {{ $totalSiswa }}</span></div>
                    </div>
                </div>

                {{-- Roster kelas hari ini --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 lg:col-span-2">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">Roster Kelas Hari Ini</h3>
                    @if ($rosterHariIni->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada siswa di kelas binaan.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                                        <th class="py-2 pr-4">Nama</th>
                                        <th class="py-2 pr-4 text-center">Wajah</th>
                                        <th class="py-2 pr-4">Jam Masuk</th>
                                        <th class="py-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rosterHariIni as $s)
                                        @php $absenSiswa = $s->absensi->first(); @endphp
                                        <tr class="border-b dark:border-gray-700 last:border-0">
                                            <td class="py-2 pr-4 font-medium text-gray-800 dark:text-gray-100">
                                                <a href="{{ route('siswa.show', $s) }}" class="hover:underline hover:text-indigo-600 dark:hover:text-indigo-400">{{ $s->nama }}</a>
                                            </td>
                                            <td class="py-2 pr-4 text-center">
                                                @if ($s->face_descriptors_count > 0)
                                                    <x-icon name="check" class="w-4 h-4 text-green-600 dark:text-green-400 inline" />
                                                @else
                                                    <x-icon name="x-circle" class="w-4 h-4 text-gray-300 dark:text-gray-600 inline" />
                                                @endif
                                            </td>
                                            <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ $absenSiswa ? \Illuminate\Support\Str::of($absenSiswa->jam_masuk)->substr(0,5) : '-' }}</td>
                                            <td class="py-2">
                                                @if ($isLibur)
                                                    <x-status-badge status="libur" />
                                                @elseif ($absenSiswa)
                                                    <x-status-badge :status="$absenSiswa->status" />
                                                @else
                                                    <x-status-badge status="alpha" />
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
                {{-- Info & aksi cepat --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100">Aksi Cepat</h3>
                    <a href="{{ route('siswa.create') }}" class="flex items-center justify-center gap-2 w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg font-medium">
                        <x-icon name="users" class="w-5 h-5" />
                        Tambah Siswa
                    </a>
                    <a href="{{ route('absensi.index') }}" class="flex items-center justify-center gap-2 w-full bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-100 py-3 rounded-lg font-medium">
                        <x-icon name="clock" class="w-5 h-5" />
                        Lihat Rekap Absensi
                    </a>
                    <div class="text-sm text-gray-500 dark:text-gray-400 pt-2 border-t dark:border-gray-700">
                        <div class="flex justify-between py-1"><span>Total Kelas</span><span class="font-medium text-gray-800 dark:text-gray-100">{{ $totalKelas }}</span></div>
                        <div class="flex justify-between py-1"><span>Siswa sudah enroll wajah</span><span class="font-medium text-gray-800 dark:text-gray-100">{{ $sudahEnroll }} / {{ $totalSiswa }}</span></div>
                    </div>
                </div>

                {{-- Absen terbaru hari ini --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 lg:col-span-2">
                    <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">Absensi Terbaru Hari Ini</h3>
                    @if ($absenTerbaru->isEmpty())
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $isLibur ? 'Hari ini libur, tidak ada absensi yang diharapkan.' : 'Belum ada absensi hari ini.' }}
                        </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                                        <th class="py-2 pr-4">Nama</th>
                                        <th class="py-2 pr-4">Kelas</th>
                                        <th class="py-2 pr-4">Jam</th>
                                        <th class="py-2">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($absenTerbaru as $a)
                                        <tr class="border-b dark:border-gray-700 last:border-0">
                                            <td class="py-2 pr-4 font-medium text-gray-800 dark:text-gray-100">{{ $a->siswa->nama }}</td>
                                            <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">
                                                {{ $a->siswa->kelas->nama_kelas ?? '-' }}
                                                @if ($a->siswa->kelas?->waliKelas)
                                                    <div class="text-xs text-gray-400 dark:text-gray-500">{{ $a->siswa->kelas->waliKelas->name }}</div>
                                                @endif
                                            </td>
                                            <td class="py-2 pr-4 text-gray-600 dark:text-gray-300">{{ \Illuminate\Support\Str::of($a->jam_masuk)->substr(0,5) }}</td>
                                            <td class="py-2">
                                                <x-status-badge :status="$a->status" />
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
