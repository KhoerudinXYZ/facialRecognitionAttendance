<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Rekap Absensi Harian</h2>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4"
         x-data="{ open: false, siswaId: null, siswaNama: '', tanggal: '{{ $tanggal->toDateString() }}' }">

        {{-- Filter --}}
        <form method="GET" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex flex-wrap gap-3 items-end">
            <div>
                <x-input-label for="tanggal" value="Tanggal" />
                <x-text-input id="tanggal" name="tanggal" type="date" class="mt-1 block" :value="$tanggal->toDateString()" />
            </div>
            @if (auth()->user()->isAdmin() || $kelasList->count() > 1)
                <div class="min-w-48">
                    <x-input-label for="kelas_id" value="Kelas" />
                    <select id="kelas_id" name="kelas_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                        <option value="">Semua Kelas</option>
                        @foreach ($kelasList as $k)
                            <option value="{{ $k->id }}" @selected($kelasId == $k->id)>{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>
            @elseif ($kelasList->isNotEmpty())
                <div>
                    <x-input-label value="Kelas" />
                    <p class="mt-1 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $kelasList->first()->nama_kelas }}</p>
                </div>
            @endif
            <x-primary-button>Tampilkan</x-primary-button>
        </form>

        @if ($isLibur)
            <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-700 rounded-lg px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                <x-icon name="x-circle" class="w-4 h-4 shrink-0" />
                Tanggal ini terdaftar sebagai <strong>hari libur</strong> — siswa tidak diwajibkan absen, jadi tidak dihitung alpha.
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3">Nama</th>
                        <th class="px-6 py-3">Kelas</th>
                        <th class="px-6 py-3">Masuk</th>
                        <th class="px-6 py-3">Pulang</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Metode</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($rekap as $row)
                        @php $a = $row['absensi']; $s = $row['siswa']; @endphp
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $s->nama }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $s->kelas->nama_kelas ?? '-' }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $a ? \Illuminate\Support\Str::of($a->jam_masuk)->substr(0,5) : '-' }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $a && $a->jam_pulang ? \Illuminate\Support\Str::of($a->jam_pulang)->substr(0,5) : '-' }}</td>
                            <td class="px-6 py-3">
                                @if ($a)
                                    <x-status-badge :status="$a->status" />
                                @elseif ($isLibur)
                                    <x-status-badge status="libur" />
                                @else
                                    <x-status-badge status="alpha" />
                                @endif
                            </td>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $a ? ucfirst($a->metode) : '-' }}</td>
                            <td class="px-6 py-3 text-right space-x-2 whitespace-nowrap">
                                <button type="button"
                                        @click="open = true; siswaId = {{ $s->id }}; siswaNama = '{{ addslashes($s->nama) }}'"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline">Set Manual</button>
                                @if ($a)
                                    <x-confirm-form :action="route('absensi.destroy', $a)" title="Hapus absensi {{ $s->nama }} pada tanggal ini?"
                                                     message="Status akan kembali kosong."
                                                     trigger-class="text-red-600 dark:text-red-400 hover:underline">
                                        Hapus
                                    </x-confirm-form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">Tidak ada siswa untuk filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Modal input manual --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40" @click.self="open = false">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6">
                <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">Absensi Manual — <span x-text="siswaNama"></span></h3>
                <form action="{{ route('absensi.manual') }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="siswa_id" :value="siswaId">
                    <input type="hidden" name="tanggal" :value="tanggal">
                    <div>
                        <x-input-label for="status" value="Status" />
                        <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                            @foreach (['hadir', 'terlambat', 'izin', 'sakit', 'alpha'] as $st)
                                <option value="{{ $st }}">{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="keterangan" value="Keterangan (opsional)" />
                        <x-text-input id="keterangan" name="keterangan" type="text" class="mt-1 block w-full" />
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="open = false" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">Batal</button>
                        <x-primary-button>Simpan</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
