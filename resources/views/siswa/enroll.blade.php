<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            Daftar Wajah — {{ $siswa->nama }}
        </h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div id="enroll-app"
             class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-5"
             data-store-url="{{ route('face.store', $siswa) }}"
             data-redirect-url="{{ route('siswa.show', $siswa) }}">

            <div class="text-sm text-gray-600 dark:text-gray-300">
                NIS {{ $siswa->nis }} &middot; {{ $siswa->kelas->nama_kelas ?? '-' }}
                @if ($siswa->faceDescriptors->isNotEmpty())
                    <span class="ml-2 inline-flex px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                        Sudah ada {{ $siswa->faceDescriptors->count() }} sampel — menambah akan menumpuk sampel baru
                    </span>
                @endif
            </div>

            @if ($siswa->faceDescriptors->isNotEmpty())
                <div class="border-t dark:border-gray-700 pt-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sampel tersimpan</p>
                    <ul class="space-y-1">
                        @foreach ($siswa->faceDescriptors as $i => $fd)
                            <li class="flex items-center justify-between text-sm bg-gray-50 dark:bg-gray-700/50 rounded px-3 py-2">
                                <span class="dark:text-gray-300">Sampel #{{ $i + 1 }} &middot; {{ $fd->created_at->format('d/m/Y H:i') }}</span>
                                <form action="{{ route('face.destroyOne', [$siswa, $fd]) }}" method="POST"
                                      onsubmit="return confirm('Hapus sampel ini? Sampel lain tidak terpengaruh.')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 dark:text-red-400 hover:underline">Hapus</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        Salah satu sampel keliru? Hapus sampel itu saja lalu rekam ulang di bawah — tidak perlu menghapus semuanya.
                    </p>
                </div>
            @endif

            <div class="relative bg-black rounded-lg overflow-hidden aspect-video flex items-center justify-center">
                <video id="enroll-video" autoplay muted playsinline class="w-full h-full object-cover"></video>
            </div>

            <div>
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-300 mb-1">
                    <span>Sampel wajah</span>
                    <span id="enroll-count">0 / 5</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div id="enroll-progress" class="bg-green-500 h-2 rounded-full transition-all" style="width:0%"></div>
                </div>
            </div>

            <p id="enroll-status" class="text-sm text-gray-700 dark:text-gray-300 min-h-5">Menyiapkan…</p>

            <div class="flex flex-wrap gap-3">
                <button id="enroll-capture" disabled
                        class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white px-5 py-2.5 rounded-lg font-medium">
                    Ambil Sampel
                </button>
                <button id="enroll-save" disabled
                        class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-5 py-2.5 rounded-lg font-medium">
                    Simpan Wajah
                </button>
                <a href="{{ route('siswa.index') }}" class="px-4 py-2.5 text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-gray-100">Selesai</a>
            </div>

            <div class="text-xs text-gray-500 dark:text-gray-400 border-t dark:border-gray-700 pt-3">
                Tips: rekam 5 sampel dengan sedikit variasi (menghadap lurus, sedikit menoleh, senyum) agar pengenalan lebih akurat.
                Pastikan wajah terang dan tidak tertutup masker.
            </div>
        </div>
    </div>

    @vite('resources/js/face-enroll.js')
</x-app-layout>
