<x-siswa-layout>
    <div id="kiosk-app"
         class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4"
         data-store-url="{{ route('siswa.absen.store') }}"
         data-dashboard-url="{{ route('siswa.dashboard') }}"
         data-labeled='@json($labeledDescriptors)'
         data-lokasi-aktif="{{ $pengaturan->lokasiAktif() ? '1' : '0' }}">

        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600 dark:text-gray-400">
                Jam masuk: <strong class="text-gray-800 dark:text-gray-200">{{ \Illuminate\Support\Str::of($pengaturan->jam_masuk)->substr(0,5) }}</strong>
                &middot; Batas terlambat: <strong class="text-gray-800 dark:text-gray-200">{{ \Illuminate\Support\Str::of($pengaturan->batas_terlambat)->substr(0,5) }}</strong>
                &middot; Mulai jam pulang: <strong class="text-gray-800 dark:text-gray-200">{{ \Illuminate\Support\Str::of($pengaturan->mulai_pulang)->substr(0,5) }}</strong>
            </span>
        </div>

        <div class="relative w-64 h-64 mx-auto">
            <div id="kiosk-ring-idle" class="absolute inset-0 rounded-full border-2 border-dashed border-gray-300 dark:border-gray-600"></div>
            <div id="kiosk-ring-scanning" class="absolute inset-0 rounded-full border-4 border-indigo-100 dark:border-indigo-900 border-t-indigo-600 animate-spin hidden"></div>

            <div class="absolute inset-3 rounded-full overflow-hidden bg-black">
                <video id="kiosk-video" autoplay muted playsinline class="w-full h-full object-cover"></video>
                <canvas id="kiosk-overlay" class="absolute inset-0 w-full h-full"></canvas>
            </div>

            <div id="kiosk-success" class="absolute inset-3 rounded-full bg-green-500 hidden items-center justify-center scale-0 transition-transform duration-300">
                <x-icon name="check" class="w-16 h-16 text-white" />
            </div>
        </div>

        <p id="kiosk-status" class="text-sm text-gray-700 dark:text-gray-300 text-center min-h-5">Menyiapkan…</p>

        @if ($siswa->faceDescriptors->isEmpty())
            <div class="text-xs text-yellow-700 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded p-3 text-center">
                Kamu belum mendaftarkan wajah. <a href="{{ route('siswa.wajah') }}" class="underline font-medium">Daftar wajah dulu</a> sebelum bisa absen.
            </div>
        @endif

        <div class="text-xs text-gray-500 dark:text-gray-400 text-center border-t dark:border-gray-700 pt-3">
            Absen tercatat otomatis saat wajahmu dikenali. Scan pertama = absen masuk, scan kedua setelah "Mulai jam pulang" = absen pulang.
        </div>

        <a href="{{ route('siswa.dashboard') }}" class="block text-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">Kembali</a>
    </div>

    <div id="kiosk-toast" style="opacity:0"
         class="fixed bottom-24 left-1/2 -translate-x-1/2 z-50 px-6 py-3 rounded-lg shadow-lg text-white text-lg font-medium transition"></div>

    @vite('resources/js/face-kiosk.js')
</x-siswa-layout>
