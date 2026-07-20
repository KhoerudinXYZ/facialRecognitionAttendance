<x-siswa-layout>
    <div id="enroll-app"
         class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-5"
         data-store-url="{{ route('siswa.enroll.store') }}"
         data-redirect-url="{{ route('siswa.wajah') }}">

        <div class="text-sm text-gray-600 dark:text-gray-400">
            NIS {{ $siswa->nis }}
            @if ($siswa->faceDescriptors->isNotEmpty())
                <span class="ml-2 inline-flex px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                    Sudah ada {{ $siswa->faceDescriptors->count() }} sampel — menambah akan menumpuk sampel baru
                </span>
            @endif
        </div>

        <div class="relative bg-black rounded-lg overflow-hidden aspect-video flex items-center justify-center">
            <video id="enroll-video" autoplay muted playsinline class="w-full h-full object-cover"></video>
        </div>

        <div>
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
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
            <a href="{{ route('siswa.wajah') }}" class="px-4 py-2.5 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">Batal</a>
        </div>

        <div class="text-xs text-gray-500 dark:text-gray-400 border-t dark:border-gray-700 pt-3">
            Tips: rekam 5 sampel dengan sedikit variasi (menghadap lurus, sedikit menoleh, senyum) agar pengenalan lebih akurat.
            Pastikan wajah terang dan tidak tertutup masker.
        </div>
    </div>

    @vite('resources/js/face-enroll.js')
</x-siswa-layout>
