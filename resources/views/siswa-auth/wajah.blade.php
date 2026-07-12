<x-siswa-layout>
    @php
        $jumlahSampel = $siswa->faceDescriptors->count();
        $targetSampel = 5;
        $persenSampel = min(100, round($jumlahSampel / $targetSampel * 100));
    @endphp

    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Data Wajah</p>
                    <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-100">{{ $siswa->nama }}</h1>
                </div>
                @if ($siswa->faceDescriptors->isNotEmpty())
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                        <x-icon name="check" class="w-3 h-3" /> Terdaftar
                    </span>
                @else
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        Belum terdaftar
                    </span>
                @endif
            </div>

            {{-- Progress sampel, konsisten dengan progress bar di halaman rekam wajah --}}
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                    <span>Sampel wajah</span>
                    <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $jumlahSampel }} / {{ $targetSampel }}</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div @class([
                            'h-2 rounded-full transition-all',
                            'bg-green-500' => $jumlahSampel >= $targetSampel,
                            'bg-indigo-500' => $jumlahSampel < $targetSampel,
                        ])
                         style="width: {{ $persenSampel }}%"></div>
                </div>
                @if ($jumlahSampel > 0 && $jumlahSampel < $targetSampel)
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5">Tambah {{ $targetSampel - $jumlahSampel }} sampel lagi agar pengenalan lebih akurat.</p>
                @endif
            </div>

            @if ($siswa->faceDescriptors->isNotEmpty())
                <div class="mt-4">
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">Sampel tersimpan</div>
                    <div class="grid grid-cols-5 gap-2">
                        @foreach ($siswa->faceDescriptors as $fd)
                            <div class="aspect-square rounded-lg bg-indigo-50 dark:bg-indigo-900/30 border border-dashed border-indigo-200 dark:border-indigo-800 flex items-center justify-center text-indigo-400 dark:text-indigo-500">
                                <x-icon name="user-circle" class="w-5 h-5" />
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Terakhir diperbarui {{ $siswa->faceDescriptors->max('created_at')->format('d/m/Y H:i') }}</p>
                </div>
            @endif

            <a href="{{ route('siswa.enroll.create') }}"
               class="mt-4 flex items-center justify-center gap-2 w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg font-medium transition">
                <x-icon name="camera" class="w-5 h-5" />
                {{ $siswa->faceDescriptors->isNotEmpty() ? 'Perbarui Sampel Wajah' : 'Daftar Wajah' }}
            </a>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
            Sampel wajah dipakai untuk mengenali kamu otomatis saat absen. Semakin banyak & bervariasi sampelnya, semakin akurat pengenalannya.
        </p>
    </div>
</x-siswa-layout>
