<x-siswa-layout>
    <div id="kiosk-app"
         class="bento-card rounded-[2.5rem] shadow-2xl p-6 sm:p-10 space-y-8 relative overflow-hidden group"
         data-store-url="{{ route('siswa.absen.store') }}"
         data-dashboard-url="{{ route('siswa.dashboard') }}"
         data-labeled='@json($labeledDescriptors)'
         data-lokasi-aktif="{{ $pengaturan->lokasiAktif() ? '1' : '0' }}">
         
        <!-- Massive Watermark Text Background -->
        <div class="absolute -left-4 top-10 text-[140px] font-black text-indigo-900/[0.03] dark:text-indigo-100/[0.02] font-lexend pointer-events-none tracking-tighter leading-none select-none transition-transform duration-700 group-hover:scale-105">
            SCAN
        </div>

        <!-- Header Rules -->
        <div class="text-center relative z-10">
            <h2 class="font-outfit font-black text-2xl sm:text-3xl text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600 dark:from-indigo-400 dark:to-violet-400 tracking-tight">Pemindai Kehadiran</h2>
            <div class="mt-4 flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-[11px] uppercase tracking-widest font-jakarta font-bold text-slate-500 dark:text-slate-400">
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>Masuk: <strong class="text-slate-800 dark:text-slate-200 font-lexend">{{ \Illuminate\Support\Str::of($pengaturan->jam_masuk)->substr(0,5) }}</strong></span>
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span>Telat: <strong class="text-slate-800 dark:text-slate-200 font-lexend">{{ \Illuminate\Support\Str::of($pengaturan->batas_terlambat)->substr(0,5) }}</strong></span>
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-indigo-500"></span>Pulang: <strong class="text-slate-800 dark:text-slate-200 font-lexend">{{ \Illuminate\Support\Str::of($pengaturan->mulai_pulang)->substr(0,5) }}</strong></span>
            </div>
        </div>

        <!-- Futuristic Scanner Viewfinder (Massive Centerpiece) -->
        <div class="relative w-72 h-72 sm:w-80 sm:h-80 mx-auto corner-bracket z-10 group-hover:scale-[1.02] transition-transform duration-500">
            <!-- Glowing background orb behind camera -->
            <div class="absolute inset-0 bg-indigo-500/20 blur-3xl rounded-full pointer-events-none animate-pulse-scan"></div>

            <!-- Corner Brackets Inner Overlay wrapper -->
            <div class="absolute inset-0 corner-bracket-inner"></div>

            <!-- Outer pulsing ring (Idle & Scanning states) -->
            <div id="kiosk-ring-idle" class="absolute inset-0 rounded-[2rem] border-2 border-indigo-200/50 dark:border-indigo-500/30 transition-all duration-300"></div>
            <div id="kiosk-ring-scanning" class="absolute -inset-1.5 rounded-[2rem] border-4 border-indigo-500/20 border-t-indigo-500 animate-spin hidden shadow-[0_0_20px_rgba(99,102,241,0.5)]"></div>

            <!-- Video Container -->
            <div class="absolute inset-3 rounded-3xl overflow-hidden bg-slate-950 shadow-2xl group border border-slate-700/50">
                <video id="kiosk-video" autoplay muted playsinline class="w-full h-full object-cover scale-x-[-1] filter contrast-125 saturate-110"></video>
                <canvas id="kiosk-overlay" class="absolute inset-0 w-full h-full"></canvas>
                
                <!-- Laser line scanner overlay -->
                <div class="laser-line-indigo opacity-80 mix-blend-screen shadow-[0_0_15px_rgba(99,102,241,1)]"></div>
            </div>

            <!-- Success Overlay -->
            <div id="kiosk-success" class="absolute inset-3 rounded-3xl bg-emerald-500/90 backdrop-blur-md hidden items-center justify-center scale-0 transition-transform duration-500 z-30">
                <div class="w-20 h-20 rounded-2xl bg-white flex items-center justify-center shadow-2xl animate-bounce">
                    <x-icon name="check" class="w-12 h-12 text-emerald-500 stroke-[3]" />
                </div>
            </div>
        </div>

        <!-- Status Message -->
        <div class="space-y-2 relative z-10 px-4">
            <p id="kiosk-status" class="text-sm font-black font-lexend text-indigo-700 dark:text-indigo-300 text-center min-h-[3rem] bg-indigo-50/80 dark:bg-indigo-900/40 py-3 px-4 rounded-2xl border border-indigo-100 dark:border-indigo-800/50 shadow-inner backdrop-blur-sm transition-all duration-300">
                Menyiapkan kamera pemindai…
            </p>
        </div>

        @if ($siswa->faceDescriptors->isEmpty())
            <div class="flex items-start gap-3 bg-rose-50/90 dark:bg-rose-900/30 border-2 border-rose-200 dark:border-rose-800/50 rounded-2xl p-4 text-sm text-rose-800 dark:text-rose-300 shadow-lg relative z-10">
                <div class="w-8 h-8 rounded-full bg-rose-100 dark:bg-rose-800 flex items-center justify-center shrink-0">
                    <x-icon name="alert-circle" class="w-5 h-5 text-rose-600 dark:text-rose-400 stroke-[2.5]" />
                </div>
                <div>
                    <span class="font-black font-lexend block text-base mb-1">Sampel Wajah Kosong</span>
                    <span class="font-medium font-jakarta leading-relaxed text-rose-700/80 dark:text-rose-300/80">Wajah Anda belum terdaftar di sistem. Silakan <a href="{{ route('siswa.wajah') }}" class="underline font-bold text-rose-700 dark:text-rose-400 hover:text-rose-900">Daftar Wajah</a> terlebih dahulu.</span>
                </div>
            </div>
        @endif

        <div class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 text-center border-t border-slate-200/50 dark:border-slate-800/50 pt-6 leading-relaxed relative z-10 font-jakarta uppercase tracking-wider">
            Sistem mendeteksi kehadiran secara otomatis. Pastikan berada di area dengan pencahayaan terang dan tatap kamera dengan lurus.
        </div>

        <div class="text-center pt-2 relative z-10">
            <a href="{{ route('siswa.dashboard') }}" class="inline-flex items-center justify-center gap-2 text-sm font-black font-lexend text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 px-6 py-3 rounded-xl transition-all duration-300 group">
                <x-icon name="arrow-left" class="w-4 h-4 stroke-[2.5] group-hover:-translate-x-1 transition-transform" /> Kembali ke Beranda
            </a>
        </div>
    </div>

    <!-- Toast message overlay -->
    <div id="kiosk-toast" style="opacity:0"
         class="fixed bottom-12 left-1/2 -translate-x-1/2 z-50 px-8 py-4 rounded-2xl shadow-2xl text-white text-base font-black font-lexend tracking-wide transition-all duration-500 backdrop-blur-md border border-white/20"></div>

    @vite('resources/js/face-kiosk.js')
</x-siswa-layout>
