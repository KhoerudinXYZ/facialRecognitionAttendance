<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Laporan Absensi</h2>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        {{-- Filter periode --}}
        <form method="GET" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex flex-wrap gap-3 items-end">
            <div>
                <x-input-label for="dari" value="Dari" />
                <x-text-input id="dari" name="dari" type="date" class="mt-1 block" :value="$dari->toDateString()" />
            </div>
            <div>
                <x-input-label for="sampai" value="Sampai" />
                <x-text-input id="sampai" name="sampai" type="date" class="mt-1 block" :value="$sampai->toDateString()" />
            </div>
            @if (auth()->user()->isAdmin() || $kelasList->count() > 1)
                <div class="min-w-48">
                    <x-input-label for="kelas_id" value="Kelas" />
                    <select id="kelas_id" name="kelas_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                        <option value="">Semua Kelas</option>
                        @foreach ($kelasList as $k)
                            <option value="{{ $k->id }}" @selected($kelasId == $k->id)>
                                {{ $k->nama_kelas }}{{ $k->waliKelas ? ' — wali ' . $k->waliKelas->name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @elseif ($kelasList->isNotEmpty())
                <div>
                    <x-input-label value="Kelas" />
                    <p class="mt-1 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $kelasList->first()->nama_kelas }}</p>
                </div>
            @endif
            <x-primary-button class="inline-flex items-center gap-2">
                <x-icon name="search" class="w-4 h-4" /> Tampilkan
            </x-primary-button>
            @php $params = ['dari' => $dari->toDateString(), 'sampai' => $sampai->toDateString(), 'kelas_id' => $kelasId]; @endphp
            <a href="{{ route('laporan.excel', $params) }}" class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded-lg">
                <x-icon name="download" class="w-4 h-4" /> Export Excel
            </a>
            <a href="{{ route('laporan.pdf', $params) }}" class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm px-4 py-2 rounded-lg">
                <x-icon name="download" class="w-4 h-4" /> Export PDF
            </a>

            <div class="w-full flex flex-wrap gap-2 pt-1">
                @foreach ($presets as $label => [$presetDari, $presetSampai])
                    <a href="{{ route('laporan.index', ['dari' => $presetDari->toDateString(), 'sampai' => $presetSampai->toDateString(), 'kelas_id' => $kelasId]) }}"
                       class="text-xs px-3 py-1.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </form>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">NIS</th>
                        <th class="px-6 py-3">Nama</th>
                        <th class="px-6 py-3">Kelas</th>
                        <th class="px-6 py-3">Masuk</th>
                        <th class="px-6 py-3">Pulang</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($data as $a)
                        <tr>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $a->tanggal->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $a->siswa->nis ?? '-' }}</td>
                            <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $a->siswa->nama ?? '-' }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">
                                {{ $a->siswa->kelas->nama_kelas ?? '-' }}
                                @if ($a->siswa->kelas?->waliKelas)
                                    <div class="text-xs text-gray-400 dark:text-gray-500">{{ $a->siswa->kelas->waliKelas->name }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ \Illuminate\Support\Str::of($a->jam_masuk)->substr(0,5) ?: '-' }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ \Illuminate\Support\Str::of($a->jam_pulang)->substr(0,5) ?: '-' }}</td>
                            <td class="px-6 py-3"><x-status-badge :status="$a->status" /></td>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $a->keterangan ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-11 h-11 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 flex items-center justify-center">
                                        <x-icon name="clipboard" class="w-5 h-5" />
                                    </div>
                                    Tidak ada data pada periode ini.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-3">
            <span>Total {{ $data->count() }} baris.</span>
            @if ($liburDalamPeriode > 0)
                <span class="inline-flex items-center gap-1">
                    <x-icon name="x-circle" class="w-3.5 h-3.5" />
                    Termasuk {{ $liburDalamPeriode }} hari libur dalam periode ini.
                </span>
            @endif
            @if ($barisTanpaWali > 0)
                <span class="inline-flex items-center gap-1 text-yellow-700 dark:text-yellow-400">
                    <x-icon name="x-circle" class="w-3.5 h-3.5" />
                    {{ $barisTanpaWali }} baris dari kelas yang belum punya wali kelas.
                </span>
            @endif
        </p>
    </div>
</x-app-layout>
