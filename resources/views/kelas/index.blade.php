<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Data Kelas</h2>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('kelas.create') }}" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg">
                    <x-icon name="plus" class="w-4 h-4" /> Tambah Kelas
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if ($kelasTanpaWali > 0)
            <div class="flex items-center gap-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg px-4 py-3 text-sm text-yellow-800 dark:text-yellow-300">
                <x-icon name="x-circle" class="w-4 h-4 shrink-0" />
                {{ $kelasTanpaWali }} kelas di bawah belum punya wali kelas.
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3">Nama Kelas</th>
                        <th class="px-6 py-3">Tingkat</th>
                        <th class="px-6 py-3">Jurusan</th>
                        <th class="px-6 py-3">Wali Kelas</th>
                        <th class="px-6 py-3 text-center">Jml Siswa</th>
                        <th class="px-6 py-3 text-center">Hadir Hari Ini</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($kelas as $k)
                        <tr class="dark:hover:bg-gray-700/50">
                            <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $k->nama_kelas }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $k->tingkat }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $k->jurusan ?? '-' }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">
                                @if ($k->waliKelas)
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-icon name="user-circle" class="w-4 h-4 text-gray-400 dark:text-gray-500" /> {{ $k->waliKelas->name }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-3 text-center text-gray-600 dark:text-gray-300">{{ $k->siswa_count }}</td>
                            <td class="px-6 py-3 text-center">
                                @if ($isLibur)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        <x-icon name="x-circle" class="w-3 h-3" /> Libur
                                    </span>
                                @else
                                    <span @class([
                                            'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                            'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' => $k->siswa_count > 0 && $k->hadir_hari_ini_count >= $k->siswa_count,
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300' => $k->hadir_hari_ini_count > 0 && $k->hadir_hari_ini_count < $k->siswa_count,
                                            'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => $k->hadir_hari_ini_count === 0,
                                        ])>
                                        {{ $k->hadir_hari_ini_count }} / {{ $k->siswa_count }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right space-x-3 whitespace-nowrap">
                                <a href="{{ route('siswa.index', ['kelas_id' => $k->id]) }}" class="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400 hover:underline">
                                    <x-icon name="users" class="w-3.5 h-3.5" /> Lihat Siswa
                                </a>
                                @if (auth()->user()->isAdmin())
                                    <a href="{{ route('kelas.edit', $k) }}" class="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400 hover:underline">
                                        <x-icon name="pencil" class="w-3.5 h-3.5" /> Edit
                                    </a>
                                    <form action="{{ route('kelas.destroy', $k) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Hapus kelas ini? Semua siswa di kelas ini juga akan terhapus.')">
                                        @csrf @method('DELETE')
                                        <button class="inline-flex items-center gap-1 text-red-600 dark:text-red-400 hover:underline">
                                            <x-icon name="trash" class="w-3.5 h-3.5" /> Hapus
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-11 h-11 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 flex items-center justify-center">
                                        <x-icon name="user-circle" class="w-5 h-5" />
                                    </div>
                                    Belum ada data kelas.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $kelas->links() }}</div>
    </div>
</x-app-layout>
