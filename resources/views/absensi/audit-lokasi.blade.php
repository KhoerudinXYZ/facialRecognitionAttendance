<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Audit Lokasi</h2>
    </x-slot>

    <div class="py-8 max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Dua sinyal audit murni untuk direview manual — tidak ada satupun di halaman ini yang otomatis memblokir
            absen siapapun. Pertahanan sebenarnya terhadap kecurangan tetap face recognition + liveness, bukan GPS.
        </p>

        {{-- Percobaan gagal lokasi --}}
        <div class="space-y-3">
            <div>
                <h3 class="font-semibold text-gray-800 dark:text-gray-100">Percobaan Gagal Lokasi</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Riwayat terbaru percobaan absen yang ditolak pengecekan lokasi (GPS tidak terkirim, akurasi
                    kurang, atau di luar radius sekolah) — termasuk percobaan yang akhirnya berhasil belakangan.
                </p>
            </div>

            @php
                $alasanLabel = [
                    'tidak_terkirim' => 'GPS tidak terkirim',
                    'akurasi_kurang' => 'Akurasi kurang',
                    'luar_radius' => 'Di luar radius',
                ];
            @endphp

            {{-- Kartu di mobile -- tabel 6 kolom gak muat di layar sempit. --}}
            <div class="sm:hidden space-y-2">
                @forelse ($gagalLog as $entry)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 space-y-1.5 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-800 dark:text-gray-100">{{ $entry->siswa->nama ?? '-' }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $entry->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="text-gray-600 dark:text-gray-300">{{ $alasanLabel[$entry->alasan] ?? $entry->alasan }}</div>
                        <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                            <span>Jarak: {{ $entry->jarak_meter !== null ? number_format($entry->jarak_meter, 0) . ' m' : '-' }}</span>
                            <span>Akurasi: {{ $entry->accuracy !== null ? number_format($entry->accuracy, 0) . ' m' : '-' }}</span>
                        </div>
                        <div class="font-mono text-xs text-gray-400 dark:text-gray-500">{{ $entry->ip ?? '-' }}</div>
                    </div>
                @empty
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center text-gray-500 dark:text-gray-400 text-sm">Belum ada percobaan yang ditolak.</div>
                @endforelse
            </div>

            <div class="hidden sm:block bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">Waktu</th>
                                <th class="px-6 py-3">Siswa</th>
                                <th class="px-6 py-3">Alasan</th>
                                <th class="px-6 py-3">Jarak</th>
                                <th class="px-6 py-3">Akurasi</th>
                                <th class="px-6 py-3">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($gagalLog as $entry)
                                <tr>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $entry->siswa->nama ?? '-' }}</td>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $alasanLabel[$entry->alasan] ?? $entry->alasan }}</td>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $entry->jarak_meter !== null ? number_format($entry->jarak_meter, 0) . ' m' : '-' }}</td>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $entry->accuracy !== null ? number_format($entry->accuracy, 0) . ' m' : '-' }}</td>
                                    <td class="px-6 py-3 text-gray-500 dark:text-gray-400 font-mono text-xs">{{ $entry->ip ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">Belum ada percobaan yang ditolak.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($gagalLog->hasPages())
                <div>{{ $gagalLog->links() }}</div>
            @endif
        </div>

        {{-- Anomali kecepatan --}}
        <div id="anomali-kecepatan" class="space-y-3">
            <div>
                <h3 class="font-semibold text-gray-800 dark:text-gray-100">Anomali Kecepatan</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Dua bacaan GPS per absen (lokasi saat buka halaman vs saat submit) yang jaraknya jauh dalam waktu
                    yang mustahil ditempuh manusia beneran (≥{{ 500 }}m dan tersirat ≥80 km/jam). Bisa jadi indikasi
                    koordinat GPS diubah-ubah di tengah sesi.
                </p>
            </div>

            {{-- Kartu di mobile -- tabel 5 kolom gak muat di layar sempit. --}}
            <div class="sm:hidden space-y-2">
                @forelse ($anomaliKecepatan as $entry)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 space-y-1.5 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-800 dark:text-gray-100">{{ $entry->siswa->nama ?? '-' }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $entry->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 dark:text-gray-300 text-xs">{{ number_format($entry->jarak_meter, 0) }} m dalam {{ number_format($entry->jeda_ms / 1000, 1) }} detik</span>
                            <span class="font-semibold text-rose-600 dark:text-rose-400">{{ number_format($entry->kecepatan_kmh, 0) }} km/jam</span>
                        </div>
                    </div>
                @empty
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 text-center text-gray-500 dark:text-gray-400 text-sm">Belum ada anomali kecepatan terdeteksi.</div>
                @endforelse
            </div>

            <div class="hidden sm:block bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-gray-500 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">Waktu</th>
                                <th class="px-6 py-3">Siswa</th>
                                <th class="px-6 py-3">Jarak</th>
                                <th class="px-6 py-3">Jeda</th>
                                <th class="px-6 py-3">Kecepatan Tersirat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($anomaliKecepatan as $entry)
                                <tr>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">{{ $entry->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $entry->siswa->nama ?? '-' }}</td>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ number_format($entry->jarak_meter, 0) }} m</td>
                                    <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ number_format($entry->jeda_ms / 1000, 1) }} detik</td>
                                    <td class="px-6 py-3 font-semibold text-rose-600 dark:text-rose-400">{{ number_format($entry->kecepatan_kmh, 0) }} km/jam</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">Belum ada anomali kecepatan terdeteksi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($anomaliKecepatan->hasPages())
                <div>{{ $anomaliKecepatan->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
