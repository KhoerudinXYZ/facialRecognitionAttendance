<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Notifikasi Orang Tua</h2>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Riwayat notifikasi email ke orang tua saat siswa tercatat alpha (tidak absen tanpa keterangan), dikirim
            otomatis tiap malam. Kalau <code>MAIL_MAILER</code> di server masih <code>log</code> (belum diisi SMTP asli),
            status "Terkirim" berarti email berhasil diproses ke log aplikasi, bukan benar-benar sampai ke kotak masuk.
        </p>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3">Tanggal Alpha</th>
                            <th class="px-6 py-3">Siswa</th>
                            <th class="px-6 py-3">Email</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3">Dikirim Pada</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($log as $entry)
                            <tr>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $entry->tanggal->format('d/m/Y') }}</td>
                                <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $entry->siswa_nama }}</td>
                                <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $entry->kontak ?? '-' }}</td>
                                <td class="px-6 py-3">
                                    @if ($entry->status === 'terkirim')
                                        <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">Terkirim</span>
                                    @elseif ($entry->status === 'tidak_ada_kontak')
                                        <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300">Tanpa Email</span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300">Gagal</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">Belum ada notifikasi yang dikirim.</td></tr>
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
