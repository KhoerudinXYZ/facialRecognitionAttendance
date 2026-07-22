<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Presensi Pintar') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Lexend:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-jakarta antialiased bg-slate-50 dark:bg-[#0B0F19] text-slate-800 dark:text-slate-200 min-h-screen overflow-x-hidden selection:bg-indigo-500/30 selection:text-indigo-900 dark:selection:text-indigo-100">
        
        <!-- Ambient Background Glows -->
        <div class="fixed top-0 left-1/4 w-[600px] h-[600px] bg-indigo-500/10 dark:bg-indigo-500/20 rounded-full blur-[120px] pointer-events-none -z-10 animate-pulse" style="animation-duration: 8s;"></div>
        <div class="fixed bottom-0 right-1/4 w-[500px] h-[500px] bg-violet-500/10 dark:bg-violet-500/20 rounded-full blur-[100px] pointer-events-none -z-10 animate-pulse" style="animation-duration: 10s; animation-delay: 2s;"></div>

        <div class="relative min-h-screen flex flex-col justify-between">
            
            <!-- Navbar -->
            <nav class="w-full px-6 py-6 sm:px-8 lg:px-12 flex items-center justify-between z-50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white flex items-center justify-center shadow-lg shadow-indigo-500/30 transform hover:scale-105 transition-transform duration-300">
                        <svg class="w-6 h-6 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
                        </svg>
                    </div>
                    <div>
                        <span class="font-outfit font-black text-xl text-slate-800 dark:text-white tracking-tight leading-none block">Presensi<span class="text-indigo-600 dark:text-indigo-400">Pintar</span></span>
                        <span class="text-[10px] font-bold font-lexend text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none block mt-0.5">Face Recognition</span>
                    </div>
                </div>

                @if (Route::has('login'))
                    <div class="flex items-center gap-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-black font-lexend text-xs uppercase tracking-wider shadow-lg shadow-indigo-500/20 transition-all duration-300 transform hover:-translate-y-0.5 active:scale-95">
                                Dashboard
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm font-bold font-lexend text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors duration-200">
                                Log in
                            </a>
                        @endauth
                    </div>
                @endif
            </nav>

            <!-- Main Content -->
            <main class="flex-grow flex items-center justify-center px-6 py-12 w-full z-10 relative">
                
                <div class="w-full max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-center">
                    
                    <!-- Text Section -->
                    <div class="space-y-8 text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800/50 shadow-sm">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-indigo-500"></span>
                            </span>
                            <span class="text-xs font-bold font-lexend text-indigo-600 dark:text-indigo-400 uppercase tracking-widest">Absensi Masa Depan</span>
                        </div>
                        
                        <h1 class="font-outfit font-black text-5xl sm:text-6xl lg:text-7xl tracking-tighter text-slate-900 dark:text-white leading-[1.1]">
                            Sistem Absensi <br class="hidden sm:block" />
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 via-violet-600 to-indigo-500 dark:from-indigo-400 dark:via-purple-400 dark:to-indigo-300">Biometrik Wajah</span>
                        </h1>
                        
                        <p class="text-base sm:text-lg text-slate-600 dark:text-slate-400 font-jakarta max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                            Mencatat kehadiran siswa secara otomatis, akurat, dan real-time menggunakan teknologi pengenalan wajah cerdas. Terintegrasi dengan fitur verifikasi lokasi GPS dan notifikasi orang tua.
                        </p>
                        
                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-4">
                            @auth
                                <a href="{{ url('/dashboard') }}" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-8 py-4 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-black font-lexend text-sm uppercase tracking-wider shadow-xl shadow-indigo-500/25 transition-all duration-300 transform hover:-translate-y-1">
                                    Buka Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-8 py-4 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-black font-lexend text-sm uppercase tracking-wider shadow-xl shadow-indigo-500/25 transition-all duration-300 transform hover:-translate-y-1">
                                    <svg class="w-5 h-5 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                    Masuk ke Sistem
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-8 py-4 rounded-2xl bg-white/50 dark:bg-slate-800/50 backdrop-blur-md text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 font-black font-lexend text-sm uppercase tracking-wider shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                                        Daftar Akun
                                    </a>
                                @endif
                            @endauth
                        </div>
                    </div>

                    <!-- Bento Visual Section -->
                    <div class="relative w-full aspect-square sm:aspect-[4/3] lg:aspect-square grid grid-cols-2 grid-rows-2 gap-4 sm:gap-6 p-4 sm:p-0 mt-8 lg:mt-0 perspective-1000">
                        <!-- Top Left: Face Scan -->
                        <div class="col-span-1 row-span-1 bento-card rounded-[2.5rem] bg-white/60 dark:bg-slate-900/40 border border-white/40 dark:border-slate-700/50 shadow-2xl backdrop-blur-xl flex flex-col items-center justify-center p-6 transform hover:scale-[1.02] transition-transform duration-500 group relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 to-purple-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            <div class="relative w-20 h-20 mb-4 rounded-full border-2 border-dashed border-indigo-400 dark:border-indigo-500/50 flex items-center justify-center animate-[spin_10s_linear_infinite]">
                                <div class="w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center">
                                    <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400 animate-[spin_10s_linear_infinite_reverse]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8V6a2 2 0 012-2h2M3 16v2a2 2 0 002 2h2M21 8V6a2 2 0 00-2-2h-2M21 16v2a2 2 0 01-2 2h-2M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 008 4.07M3 15.364c.64-1.319 1-2.8 1-4.364 0-1.457.39-2.823 1.07-4" />
                                    </svg>
                                </div>
                            </div>
                            <span class="font-outfit font-black text-lg text-slate-800 dark:text-white">Face Scan</span>
                            <span class="text-[10px] font-lexend font-semibold text-slate-500 uppercase tracking-widest mt-1">Akurat & Cepat</span>
                        </div>

                        <!-- Top Right: Location -->
                        <div class="col-span-1 row-span-1 bento-card rounded-[2.5rem] bg-gradient-to-br from-emerald-500 to-teal-600 border border-emerald-400/50 shadow-2xl shadow-emerald-500/20 flex flex-col items-center justify-center p-6 text-white transform hover:scale-[1.02] transition-transform duration-500">
                            <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <span class="font-outfit font-black text-lg">GPS Location</span>
                            <span class="text-[10px] font-lexend font-bold text-emerald-100 uppercase tracking-widest mt-1">Verifikasi Zona</span>
                        </div>

                        <!-- Bottom Left: Time/Status -->
                        <div class="col-span-1 row-span-1 bento-card rounded-[2.5rem] bg-gradient-to-br from-amber-400 to-orange-500 border border-amber-300/50 shadow-2xl shadow-amber-500/20 flex flex-col items-center justify-center p-6 text-white transform hover:scale-[1.02] transition-transform duration-500">
                            <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="font-outfit font-black text-lg">Real-time</span>
                            <span class="text-[10px] font-lexend font-bold text-amber-100 uppercase tracking-widest mt-1">Live Tracking</span>
                        </div>

                        <!-- Bottom Right: Notification -->
                        <div class="col-span-1 row-span-1 bento-card rounded-[2.5rem] bg-white/60 dark:bg-slate-900/40 border border-white/40 dark:border-slate-700/50 shadow-2xl backdrop-blur-xl flex flex-col items-center justify-center p-6 transform hover:scale-[1.02] transition-transform duration-500 group relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-cyan-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            <div class="w-16 h-16 rounded-2xl bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center mb-4 relative">
                                <div class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-rose-500 animate-ping opacity-75"></div>
                                <div class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-rose-500 border-2 border-white dark:border-slate-900"></div>
                                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            </div>
                            <span class="font-outfit font-black text-lg text-slate-800 dark:text-white">Notifikasi</span>
                            <span class="text-[10px] font-lexend font-semibold text-slate-500 uppercase tracking-widest mt-1">Ke Orang Tua</span>
                        </div>
                    </div>

                </div>
            </main>

            <!-- Footer -->
            <footer class="w-full text-center py-8 z-10">
                <p class="text-xs font-semibold font-jakarta text-slate-500 dark:text-slate-400">
                    &copy; {{ date('Y') }} PresensiPintar. All rights reserved.
                </p>
            </footer>
        </div>
    </body>
</html>
