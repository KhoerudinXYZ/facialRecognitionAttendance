<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Data Siswa</h2>
            <div class="flex gap-2">
                <a href="{{ route('siswa.import.form') }}" class="flex items-center gap-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-sm px-4 py-2 rounded-lg">
                    <x-icon name="upload" class="w-4 h-4" /> Import Excel
                </a>
                <a href="{{ route('siswa.create') }}" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg">
                    <x-icon name="plus" class="w-4 h-4" /> Tambah Siswa
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        {{-- Filter --}}
        <form method="GET" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <x-input-label for="q" value="Cari (nama / NIS)" />
                <x-text-input id="q" name="q" type="text" class="mt-1 block w-full" :value="request('q')" />
            </div>
            @if (auth()->user()->isAdmin() || $kelasList->count() > 1)
                <div class="min-w-48">
                    <x-input-label for="kelas_id" value="Kelas" />
                    <select id="kelas_id" name="kelas_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                        <option value="">Semua Kelas</option>
                        @foreach ($kelasList as $k)
                            <option value="{{ $k->id }}" @selected(request('kelas_id') == $k->id)>{{ $k->nama_kelas }}</option>
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
                <x-icon name="search" class="w-4 h-4" /> Filter
            </x-primary-button>
            <a href="{{ route('siswa.index') }}" class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-gray-100 text-sm">Reset</a>
        </form>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3">NIS</th>
                        <th class="px-6 py-3">Nama</th>
                        <th class="px-6 py-3">L/P</th>
                        <th class="px-6 py-3">Kelas</th>
                        <th class="px-6 py-3 text-center">Wajah</th>
                        <th class="px-6 py-3 text-center">Hari Ini</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($siswa as $s)
                        <tr class="dark:hover:bg-gray-700/50">
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $s->nis }}</td>
                            <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">
                                <a href="{{ route('siswa.show', $s) }}" class="hover:underline">{{ $s->nama }}</a>
                            </td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $s->jenis_kelamin }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">
                                {{ $s->kelas->nama_kelas ?? '-' }}
                                @if ($s->kelas?->waliKelas)
                                    <div class="text-xs text-gray-400 dark:text-gray-500">{{ $s->kelas->waliKelas->name }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-center">
                                @if ($s->face_descriptors_count > 0)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                                        <x-icon name="camera" class="w-3 h-3" /> {{ $s->face_descriptors_count }} sampel
                                    </span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">Belum</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-center">
                                @if ($isLibur)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        <x-icon name="x-circle" class="w-3 h-3" /> Libur
                                    </span>
                                @elseif ($s->absensi->first())
                                    <x-status-badge :status="$s->absensi->first()->status" />
                                @else
                                    <x-status-badge status="alpha" />
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right space-x-3 whitespace-nowrap">
                                <a href="{{ route('siswa.enroll', $s) }}" class="inline-flex items-center gap-1 text-green-600 dark:text-green-400 hover:underline">
                                    <x-icon name="camera" class="w-3.5 h-3.5" /> Daftar Wajah
                                </a>
                                <a href="{{ route('siswa.edit', $s) }}" class="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400 hover:underline">
                                    <x-icon name="pencil" class="w-3.5 h-3.5" /> Edit
                                </a>
                                <form action="{{ route('siswa.destroy', $s) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Hapus siswa ini?')">
                                    @csrf @method('DELETE')
                                    <button class="inline-flex items-center gap-1 text-red-600 dark:text-red-400 hover:underline">
                                        <x-icon name="trash" class="w-3.5 h-3.5" /> Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-11 h-11 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 flex items-center justify-center">
                                        <x-icon name="user-circle" class="w-5 h-5" />
                                    </div>
                                    Belum ada data siswa.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>{{ $siswa->links() }}</div>
    </div>
</x-app-layout>
