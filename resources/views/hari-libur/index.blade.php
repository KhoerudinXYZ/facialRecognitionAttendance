<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Hari Libur</h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-1">Libur Mingguan Otomatis</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                Centang hari yang selalu libur setiap minggu (mis. Sabtu & Minggu) supaya tidak perlu ditambahkan satu
                per satu di tabel bawah. Absensi otomatis diblokir setiap hari itu, tanpa batas waktu.
            </p>
            <form action="{{ route('pengaturan.libur-mingguan') }}" method="POST" class="flex flex-wrap items-end gap-4">
                @csrf @method('PUT')
                @php
                    $liburMingguan = $pengaturan->liburMingguan();
                    $hariOptions = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu'];
                @endphp
                <div class="flex flex-wrap gap-3">
                    @foreach ($hariOptions as $value => $label)
                        <label class="inline-flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="hari_libur_mingguan[]" value="{{ $value }}"
                                   class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                   @checked(in_array($value, $liburMingguan, true)) />
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <x-primary-button>Simpan</x-primary-button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">Tambah Tanggal Libur</h3>
            <form action="{{ route('hari-libur.store') }}" method="POST" class="flex flex-wrap gap-3 items-end"
                  x-data="{ dari: '', sampai: '' }">
                @csrf
                <div>
                    <x-input-label for="dari" value="Dari Tanggal" />
                    <x-text-input id="dari" name="dari" type="date" class="mt-1 block" required
                                  x-model="dari" @change="if (!sampai) sampai = dari" />
                </div>
                <div>
                    <x-input-label for="sampai" value="Sampai Tanggal" />
                    <x-text-input id="sampai" name="sampai" type="date" class="mt-1 block" required x-model="sampai" />
                </div>
                <div class="flex-1 min-w-48">
                    <x-input-label for="keterangan" value="Keterangan (opsional)" />
                    <x-text-input id="keterangan" name="keterangan" type="text" class="mt-1 block w-full" placeholder="Misal: Libur Semester Ganjil" />
                </div>
                <x-primary-button>Tambah</x-primary-button>
            </form>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
                Buat libur satu hari, isi "Dari" dan "Sampai" dengan tanggal yang sama. Absensi otomatis (scan wajah) akan diblokir pada tanggal-tanggal yang terdaftar di sini.
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-left text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Keterangan</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($hariLibur as $libur)
                        <tr>
                            <td class="px-6 py-3 font-medium text-gray-800 dark:text-gray-100">{{ $libur->tanggal->format('d/m/Y') }}</td>
                            <td class="px-6 py-3 text-gray-600 dark:text-gray-300">{{ $libur->keterangan ?? '-' }}</td>
                            <td class="px-6 py-3 text-right">
                                <x-confirm-form :action="route('hari-libur.destroy', $libur)"
                                                 title="Hapus tanggal libur {{ $libur->tanggal->format('d/m/Y') }}?"
                                                 trigger-class="text-red-600 dark:text-red-400 hover:underline">
                                    Hapus
                                </x-confirm-form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">Belum ada tanggal libur terdaftar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
