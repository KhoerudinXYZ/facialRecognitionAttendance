<x-siswa-layout>
    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">NIS {{ $siswa->nis }}</p>
            <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">{{ $siswa->nama }}</h1>

            <div class="mt-3 flex flex-wrap gap-2">
                @if ($siswa->faceDescriptors->isNotEmpty())
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                        Wajah terdaftar ({{ $siswa->faceDescriptors->count() }} sampel)
                    </span>
                @else
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        Wajah belum terdaftar
                    </span>
                @endif

                @if ($isLibur)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <x-icon name="x-circle" class="w-3 h-3" /> Hari ini libur
                    </span>
                @elseif ($absenHariIni)
                    <x-status-badge :status="$absenHariIni->status" />
                @else
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        Belum absen hari ini
                    </span>
                @endif
            </div>

            {{-- Progress masuk/pulang hari ini --}}
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                @php
                    $sudahMasuk = (bool) $absenHariIni;
                    $sudahPulang = (bool) ($absenHariIni->jam_pulang ?? null);
                    $absenSelesai = $sudahMasuk && $sudahPulang;
                    $absenNonaktif = $isLibur || $absenSelesai;
                @endphp
                <div class="flex items-center">
                    <div class="flex flex-col items-center w-16">
                        <div @class([
                                'w-9 h-9 rounded-full flex items-center justify-center transition',
                                'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400' => $sudahMasuk,
                                'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' => ! $sudahMasuk,
                            ])>
                            @if ($sudahMasuk)
                                <x-icon name="check" class="w-4 h-4" />
                            @else
                                <span class="text-sm font-semibold">1</span>
                            @endif
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">Masuk</span>
                        <span @class([
                                'text-sm font-semibold',
                                'text-gray-800 dark:text-gray-100' => $sudahMasuk,
                                'text-gray-400 dark:text-gray-500' => ! $sudahMasuk,
                            ])>
                            {{ $sudahMasuk ? \Illuminate\Support\Str::of($absenHariIni->jam_masuk)->substr(0,5) : '—' }}
                        </span>
                    </div>

                    <div @class([
                            'flex-1 h-0.5 mx-1 mb-6 transition',
                            'bg-green-300 dark:bg-green-700' => $sudahMasuk,
                            'bg-gray-200 dark:bg-gray-700' => ! $sudahMasuk,
                        ])></div>

                    {{-- Tombol absen di tengah tracker --}}
                    @if ($absenNonaktif)
                        <div class="flex flex-col items-center shrink-0 -mt-3 mb-6" title="{{ $isLibur ? 'Absensi tidak aktif saat hari libur' : 'Kamu sudah absen masuk & pulang hari ini' }}">
                            <div @class([
                                    'w-14 h-14 rounded-full flex items-center justify-center shadow-lg cursor-not-allowed',
                                    'bg-gray-300 text-gray-500 dark:bg-gray-700 dark:text-gray-400' => $isLibur,
                                    'bg-green-500 text-white' => ! $isLibur,
                                ])>
                                <x-icon :name="$isLibur ? 'x-circle' : 'sparkles'" class="w-6 h-6" />
                            </div>
                            <span @class([
                                    'text-[11px] font-semibold mt-1 whitespace-nowrap',
                                    'text-gray-500 dark:text-gray-400' => $isLibur,
                                    'text-green-600 dark:text-green-400' => ! $isLibur,
                                ])>
                                {{ $isLibur ? 'Hari Libur' : 'Selesai' }}
                            </span>
                        </div>
                    @else
                        <a href="{{ route('siswa.absen') }}" class="flex flex-col items-center shrink-0 -mt-3 mb-6">
                            <div class="w-14 h-14 rounded-full flex items-center justify-center shadow-lg transition bg-indigo-600 text-white hover:bg-indigo-700 animate-pulse">
                                <x-icon name="camera" class="w-6 h-6" />
                            </div>
                            <span class="text-[11px] font-semibold mt-1 whitespace-nowrap text-indigo-600 dark:text-indigo-400">
                                {{ $sudahMasuk ? 'Absen Pulang' : 'Absen Masuk' }}
                            </span>
                        </a>
                    @endif

                    <div @class([
                            'flex-1 h-0.5 mx-1 mb-6 transition',
                            'bg-green-300 dark:bg-green-700' => $sudahPulang,
                            'bg-gray-200 dark:bg-gray-700' => ! $sudahPulang,
                        ])></div>

                    <div class="flex flex-col items-center w-16">
                        <div @class([
                                'w-9 h-9 rounded-full flex items-center justify-center transition',
                                'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400' => $sudahPulang,
                                'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400 animate-pulse' => $sudahMasuk && ! $sudahPulang,
                                'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' => ! $sudahMasuk,
                            ])>
                            @if ($sudahPulang)
                                <x-icon name="check" class="w-4 h-4" />
                            @else
                                <span class="text-sm font-semibold">2</span>
                            @endif
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">Pulang</span>
                        <span @class([
                                'text-sm font-semibold',
                                'text-gray-800 dark:text-gray-100' => $sudahPulang,
                                'text-gray-400 dark:text-gray-500' => ! $sudahPulang,
                            ])>
                            {{ $sudahPulang ? \Illuminate\Support\Str::of($absenHariIni->jam_pulang)->substr(0,5) : '—' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Minggu ini --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Minggu Ini</div>
            @php
                $warnaHari = [
                    'hadir' => 'bg-green-500',
                    'terlambat' => 'bg-yellow-500',
                    'izin' => 'bg-blue-500',
                    'sakit' => 'bg-purple-500',
                    'alpha' => 'bg-red-500',
                ];
            @endphp
            <div class="flex gap-1.5">
                @foreach ($mingguIni as $hari)
                    <div class="flex-1 flex flex-col items-center gap-1">
                        <div @class([
                                'w-full h-7 rounded-md transition',
                                $warnaHari[$hari['status']] ?? 'bg-gray-100 dark:bg-gray-700',
                                'ring-2 ring-indigo-400 dark:ring-indigo-500' => $hari['isToday'],
                            ])></div>
                        <span @class([
                                'text-[10px]',
                                'text-gray-300 dark:text-gray-600' => $hari['isFuture'],
                                'text-gray-500 dark:text-gray-400' => ! $hari['isFuture'],
                            ])>{{ $hari['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <a href="{{ route('siswa.riwayat', ['bulan' => now()->format('Y-m')]) }}"
           class="block bg-white dark:bg-gray-800 rounded-lg shadow p-5 hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-medium text-gray-800 dark:text-gray-100">Statistik Bulan Ini</h3>
                <span class="text-xs text-indigo-600 dark:text-indigo-400">Lihat riwayat &rarr;</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
                <div class="text-center">
                    <div class="w-9 h-9 mx-auto rounded-full bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400 flex items-center justify-center">
                        <x-icon name="check-circle" class="w-4 h-4" />
                    </div>
                    <div class="text-lg font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $statistikBulanIni['hadir'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Hadir</div>
                </div>
                <div class="text-center">
                    <div class="w-9 h-9 mx-auto rounded-full bg-yellow-100 text-yellow-600 dark:bg-yellow-900/40 dark:text-yellow-400 flex items-center justify-center">
                        <x-icon name="clock" class="w-4 h-4" />
                    </div>
                    <div class="text-lg font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $statistikBulanIni['terlambat'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Terlambat</div>
                </div>
                <div class="text-center">
                    <div class="w-9 h-9 mx-auto rounded-full bg-purple-100 text-purple-600 dark:bg-purple-900/40 dark:text-purple-400 flex items-center justify-center">
                        <x-icon name="clipboard" class="w-4 h-4" />
                    </div>
                    <div class="text-lg font-semibold text-gray-800 dark:text-gray-100 mt-1">{{ $statistikBulanIni['izinSakit'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Izin/Sakit</div>
                </div>
            </div>
        </a>

        <a href="{{ route('siswa.wajah') }}"
           class="flex items-center gap-4 bg-white dark:bg-gray-800 rounded-lg shadow p-5 hover:shadow-md transition">
            <div class="w-11 h-11 shrink-0 rounded-full bg-indigo-50 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400 flex items-center justify-center">
                <x-icon name="camera" class="w-5 h-5" />
            </div>
            <div>
                <div class="font-medium text-gray-800 dark:text-gray-100">Daftar / Perbarui Wajah</div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Rekam sampel wajah untuk absen otomatis.</p>
            </div>
        </a>

        <a href="{{ route('siswa.riwayat') }}"
           class="flex items-center gap-4 bg-white dark:bg-gray-800 rounded-lg shadow p-5 hover:shadow-md transition">
            <div class="w-11 h-11 shrink-0 rounded-full bg-indigo-50 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400 flex items-center justify-center">
                <x-icon name="clock" class="w-5 h-5" />
            </div>
            <div>
                <div class="font-medium text-gray-800 dark:text-gray-100">Riwayat Absensi</div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Lihat catatan kehadiran kamu.</p>
            </div>
        </a>
    </div>
</x-siswa-layout>
