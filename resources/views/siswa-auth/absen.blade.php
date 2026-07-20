<x-siswa-layout>
    <div id="kiosk-app"
         class="glass-card rounded-2xl shadow-xl p-6 space-y-6 relative overflow-hidden"
         data-store-url="{{ route('siswa.absen.store') }}"
         data-dashboard-url="{{ route('siswa.dashboard') }}"
         data-labeled='@json($labeledDescriptors)'
         data-lokasi-aktif="{{ $pengaturan->lokasiAktif() ? '1' : '0' }}">
        
        <!-- Header Rules -->
        <div class="text-center">
            <h2 class="font-outfit font-bold text-lg text-slate-800 dark:text-slate-100">Pemindai Kehadiran Wajah</h2>
            <div class="mt-2 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-xs text-slate-500 dark:text-slate-400">
                <span>Masuk: <strong class="text-slate-800 dark:text-slate-200">{{ \Illuminate\Support\Str::of($pengaturan->jam_masuk)->substr(0,5) }}</strong></span>
                <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                <span>Terlambat: <strong class="text-slate-800 dark:text-slate-200">{{ \Illuminate\Support\Str::of($pengaturan->batas_terlambat)->substr(0,5) }}</strong></span>
                <span class="text-slate-300 dark:text-slate-700">&bull;</span>
                <span>Pulang: <strong class="text-slate-800 dark:text-slate-200">{{ \Illuminate\Support\Str::of($pengaturan->mulai_pulang)->substr(0,5) }}</strong></span>
            </div>
        </div>

        <!-- Futuristic Scanner Viewfinder -->
        <div class="relative w-64 h-64 mx-auto corner-bracket">
            <!-- Corner Brackets Inner Overlay wrapper -->
            <div class="absolute inset-0 corner-bracket-inner"></div>

            <!-- Outer pulsing ring (Idle & Scanning states) -->
            <div id="kiosk-ring-idle" class="absolute inset-0 rounded-3xl border border-slate-300/40 dark:border-slate-700/40 transition-all duration-300"></div>
            <div id="kiosk-ring-scanning" class="absolute -inset-1 rounded-3xl border-2 border-indigo-500/20 border-t-indigo-500 animate-spin hidden"></div>

            <!-- Video Container -->
            <div class="absolute inset-2.5 rounded-2xl overflow-hidden bg-slate-950 shadow-inner group border border-slate-800">
                <video id="kiosk-video" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1]"></video>
                <canvas id="kiosk-overlay" class="absolute inset-0 w-full h-full"></canvas>
                
                <!-- Laser line scanner overlay -->
                <div class="laser-line-indigo"></div>
            </div>

            <!-- Success Overlay -->
            <div id="kiosk-success" class="absolute inset-2.5 rounded-2xl bg-green-500/90 backdrop-blur-sm hidden items-center justify-center scale-0 transition-transform duration-300 z-30">
                <div class="w-16 h-16 rounded-full bg-white flex items-center justify-center shadow-lg animate-bounce">
                    <x-icon name="check" class="w-10 h-10 text-green-600" />
                </div>
            </div>
        </div>

        <!-- Status Message -->
        <div class="space-y-2">
            <p id="kiosk-status" class="text-xs font-semibold text-slate-600 dark:text-slate-350 text-center min-h-[1.5rem] bg-slate-50 dark:bg-slate-900/40 py-2 px-3 rounded-xl border border-slate-100 dark:border-slate-850">
                Menyiapkan kamera pemindai…
            </p>
        </div>

        @if ($siswa->faceDescriptors->isEmpty())
            <div class="flex items-start gap-2.5 bg-rose-50 dark:bg-rose-950/20 border border-rose-200/50 dark:border-rose-900/30 rounded-xl p-3.5 text-xs text-rose-800 dark:text-rose-400">
                <x-icon name="alert-circle" class="w-4 h-4 shrink-0 mt-0.5" />
                <div>
                    <span class="font-bold block">Sampel Wajah Kosong</span>
                    Wajah Anda belum terdaftar di sistem. Silakan <a href="{{ route('siswa.wajah') }}" class="underline font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700">Daftar Wajah</a> terlebih dahulu.
                </div>
            </div>
        @endif

        <div class="text-[10px] font-medium text-slate-400 dark:text-slate-500 text-center border-t border-slate-100 dark:border-slate-800 pt-4 leading-relaxed">
            Sistem mendeteksi kehadiran secara otomatis. Pastikan berada di area dengan pencahayaan yang cukup dan tatap kamera dengan tegak.
        </div>

        <div class="text-center pt-2">
            <a href="{{ route('siswa.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                <x-icon name="arrow-left" class="w-3.5 h-3.5" /> Kembali ke Beranda
            </a>
        </div>
    </div>

    <!-- Toast message overlay -->
    <div id="kiosk-toast" style="opacity:0"
         class="fixed bottom-24 left-1/2 -translate-x-1/2 z-50 px-6 py-3.5 rounded-xl shadow-2xl text-white text-sm font-bold transition-all duration-300"></div>

    @vite('resources/js/face-kiosk.js')
</x-siswa-layout>
