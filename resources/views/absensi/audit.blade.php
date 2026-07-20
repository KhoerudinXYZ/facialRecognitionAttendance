<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Riwayat Hapus Absensi</h2>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Setiap kali record absensi dihapus dari rekap, datanya disalin ke sini dulu — jadi tetap bisa dilacak
            siapa yang menghapus, kapan, dan absensi seperti apa yang hilang, walau baris aslinya sudah tidak ada.
        </p>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3">Dihapus Pada</th>
                            <th class="px-6 py-3">Siswa</th>
                            <th class="px-6 py-3">Tanggal Absensi</th>
                            <th class="px-6 py-3">Masuk</th>
                            <th class="px-6 py-3">Pulang</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Metode</th>
                            <th class="px-6 py-3">Dihapus Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($log as $entry)
                            <tr>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $entry->siswa_nama }}</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $entry->tanggal->format('d/m/Y') }}</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $entry->jam_masuk ? \Illuminate\Support\Str::of($entry->jam_masuk)->substr(0,5) : '-' }}</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $entry->jam_pulang ? \Illuminate\Support\Str::of($entry->jam_pulang)->substr(0,5) : '-' }}</td>
                                <td class="px-6 py-3"><x-status-badge :status="$entry->status" /></td>
                                <td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ ucfirst($entry->metode) }}</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $entry->dihapus_oleh_nama }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">Belum ada absensi yang dihapus.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($log->hasPages())
            <div>{{ $log->links() }}</div>
        @endif
    </div>
</x-app-layout>
