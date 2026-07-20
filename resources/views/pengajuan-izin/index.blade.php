<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Pengajuan Izin/Sakit</h2>
    </x-slot>

    @php
        $badgeMap = [
            'menunggu' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
            'disetujui' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
            'ditolak' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        ];
    @endphp

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        {{-- Filter --}}
        <form method="GET" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex flex-wrap gap-3 items-end">
            <div class="min-w-40">
                <x-input-label for="status" value="Status" />
                <select id="status" name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                    @foreach (['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'semua' => 'Semua'] as $val => $label)
                        <option value="{{ $val }}" @selected($status === $val)>{{ $label }}</option>
                    @endforeach
                </select>
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
            @endif
            <x-primary-button>Tampilkan</x-primary-button>
        </form>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3">Siswa</th>
                        <th class="px-6 py-3">Kelas</th>
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Jenis</th>
                        <th class="px-6 py-3">Keterangan</th>
                        <th class="px-6 py-3">Bukti</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($pengajuanList as $p)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $p->siswa->nama }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $p->siswa->kelas->nama_kelas ?? '-' }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $p->tanggal->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ ucfirst($p->jenis) }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $p->keterangan }}</td>
                            <td class="px-6 py-3">
                                <a href="{{ \Illuminate\Support\Facades\Storage::url($p->bukti) }}" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline">Lihat</a>
                            </td>
                            <td class="px-6 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeMap[$p->status] }}">
                                    {{ ucfirst($p->status) }}
                                </span>
                                @if ($p->status === 'ditolak' && $p->catatan_admin)
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $p->catatan_admin }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-right space-x-2 whitespace-nowrap">
                                @if ($p->status === 'menunggu')
                                    <x-confirm-form :action="route('pengajuan-izin.approve', $p)" method="POST"
                                                     title="Setujui pengajuan {{ $p->siswa->nama }}?"
                                                     message="Absensi tanggal {{ $p->tanggal->format('d/m/Y') }} akan dicatat sebagai {{ $p->jenis }}. Pengajuan yang sudah disetujui tidak bisa ditinjau ulang."
                                                     confirm-label="Setujui" :danger="false"
                                                     trigger-class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        Approve
                                    </x-confirm-form>
                                    <x-confirm-form :action="route('pengajuan-izin.reject', $p)" method="POST"
                                                     title="Tolak pengajuan {{ $p->siswa->nama }}?"
                                                     confirm-label="Tolak"
                                                     trigger-class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                        <x-slot:fields>
                                            <div>
                                                <x-input-label for="catatan_admin_{{ $p->id }}" value="Catatan (opsional)" />
                                                <x-text-input id="catatan_admin_{{ $p->id }}" name="catatan_admin" type="text" class="mt-1 block w-full" />
                                            </div>
                                        </x-slot:fields>
                                        Reject
                                    </x-confirm-form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">Tidak ada pengajuan untuk filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
