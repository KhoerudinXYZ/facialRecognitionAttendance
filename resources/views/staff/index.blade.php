<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Kelola Staff</h2>
            <a href="{{ route('staff.create') }}" class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg">
                <x-icon name="plus" class="w-4 h-4" /> Tambah Staff
            </a>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if ($waliTanpaKelas > 0)
            <div class="flex items-center gap-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg px-4 py-3 text-sm text-yellow-800 dark:text-yellow-300">
                <x-icon name="alert-circle" class="w-4 h-4 shrink-0" />
                {{ $waliTanpaKelas }} wali kelas di bawah belum ditugaskan ke kelas manapun.
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3">Nama</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Peran</th>
                        <th class="px-6 py-3">Kelas Binaan</th>
                        <th class="px-6 py-3 text-center">Hadir Hari Ini</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($staff as $s)
                        <tr class="dark:hover:bg-gray-700/50">
                            <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $s->name }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $s->email }}</td>
                            <td class="px-6 py-3">
                                @if ($s->role === 'admin')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                        <x-icon name="cog" class="w-3 h-3" /> Admin
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                        <x-icon name="user-circle" class="w-3 h-3" /> Wali Kelas
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">
                                @if ($s->role === 'wali_kelas' && $s->kelasBinaan->isNotEmpty())
                                    {{ $s->kelasBinaan->pluck('nama_kelas')->join(', ') }}
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-center">
                                @if ($s->role !== 'wali_kelas')
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">-</span>
                                @elseif ($isLibur)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        <x-icon name="calendar" class="w-3 h-3" /> Libur
                                    </span>
                                @elseif ($s->total_siswa_binaan === 0)
                                    <span class="text-gray-400 dark:text-gray-500 text-xs">Belum ada kelas</span>
                                @else
                                    <span @class([
                                            'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                            'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' => $s->hadir_hari_ini_binaan >= $s->total_siswa_binaan,
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300' => $s->hadir_hari_ini_binaan > 0 && $s->hadir_hari_ini_binaan < $s->total_siswa_binaan,
                                            'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => $s->hadir_hari_ini_binaan === 0,
                                        ])>
                                        {{ $s->hadir_hari_ini_binaan }} / {{ $s->total_siswa_binaan }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right space-x-3 whitespace-nowrap">
                                <a href="{{ route('staff.edit', $s) }}" class="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-400 hover:underline">
                                    <x-icon name="pencil" class="w-3.5 h-3.5" /> Edit
                                </a>
                                @if ($s->id !== auth()->id())
                                    <x-confirm-form :action="route('staff.destroy', $s)" title="Hapus akun staff ini?">
                                        <x-icon name="trash" class="w-3.5 h-3.5" /> Hapus
                                    </x-confirm-form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-11 h-11 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-400 dark:text-gray-500 flex items-center justify-center">
                                        <x-icon name="users" class="w-5 h-5" />
                                    </div>
                                    Belum ada akun staff.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $staff->links() }}</div>
    </div>
</x-app-layout>
