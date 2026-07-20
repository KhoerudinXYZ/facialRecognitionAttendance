<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} - Siswa</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo-sekolah.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script>
            // Diterapkan sebelum body dirender supaya tidak ada flash warna salah.
            if (localStorage.getItem('theme') === 'dark'
                || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        </script>
    </head>
    <body class="font-sans antialiased">
        @php $siswaUser = auth('siswa')->user(); @endphp

        <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors duration-300">
            <!-- Header (Sticky & Glassmorphism) -->
            <header class="sticky top-0 z-40 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200/50 dark:border-slate-800/50 shadow-sm">
                <div class="max-w-lg mx-auto px-4 py-3 flex justify-between items-center">
                    <a href="{{ route('siswa.profile.edit') }}" class="flex items-center gap-3">
                        @if ($siswaUser->foto)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($siswaUser->foto) }}"
                                 alt="{{ $siswaUser->nama }}"
                                 class="w-10 h-10 rounded-full object-cover ring-2 ring-indigo-500/20">
                        @else
                            <div class="w-10 h-10 rounded-full bg-indigo-500/10 dark:bg-indigo-500/20 ring-2 ring-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-semibold font-outfit">
                                {{ Illuminate\Support\Str::of($siswaUser->nama)->substr(0, 1)->upper() }}
                            </div>
                        @endif
                        <div>
                            <p class="font-semibold text-slate-800 dark:text-slate-100 text-sm leading-tight font-outfit">{{ $siswaUser->nama }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ $siswaUser->kelas->nama_kelas ?? '-' }}</p>
                        </div>
                    </a>
                    <div class="flex items-center gap-1.5">
                        <button id="theme-toggle" type="button"
                                class="w-8 h-8 flex items-center justify-center rounded-full text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                title="Ganti tema">
                            <x-icon name="sun" class="w-4 h-4 hidden dark:block" />
                            <x-icon name="moon" class="w-4 h-4 block dark:hidden" />
                        </button>
                        <form action="{{ route('siswa.logout') }}" method="POST">
                            @csrf
                            <button class="w-8 h-8 flex items-center justify-center rounded-full text-slate-500 hover:text-red-600 dark:text-slate-400 dark:hover:text-red-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                                    title="Keluar">
                                <x-icon name="logout" class="w-4 h-4" />
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            @include('layouts.flash')

            <!-- Main Content Container -->
            <main class="max-w-lg mx-auto px-4 py-6 pb-28">
                {{ $slot }}
            </main>

            <!-- Bottom Navigation Bar (Glassmorphism & Floating look) -->
            <nav class="fixed inset-x-0 bottom-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200/50 dark:border-slate-800/50 z-40 shadow-lg">
                <div class="max-w-lg mx-auto grid grid-cols-5 py-1">
                    @php
                        $todayNav = \App\Models\Pengaturan::sekarang()->startOfDay();
                        $absenHariIniNav = $siswaUser->absensi()->whereDate('tanggal', $todayNav)->first();
                        $absenNavNonaktif = \App\Models\HariLibur::isLibur($todayNav) || ($absenHariIniNav && $absenHariIniNav->jam_pulang);

                        $navItems = [
                            ['route' => 'siswa.dashboard', 'match' => 'siswa.dashboard', 'icon' => 'home', 'label' => 'Beranda'],
                            ['route' => 'siswa.absen', 'match' => 'siswa.absen*', 'icon' => 'camera', 'label' => 'Absen', 'disabled' => $absenNavNonaktif],
                            ['route' => 'siswa.riwayat', 'match' => 'siswa.riwayat', 'icon' => 'calendar', 'label' => 'Riwayat'],
                            ['route' => 'siswa.izin', 'match' => 'siswa.izin*', 'icon' => 'alert-circle', 'label' => 'Izin'],
                            ['route' => 'siswa.wajah', 'match' => ['siswa.wajah', 'siswa.enroll.*'], 'icon' => 'user-circle', 'label' => 'Wajah'],
                        ];
                    @endphp
                    @foreach ($navItems as $item)
                        @php $active = request()->routeIs($item['match']); @endphp
                        @if ($item['disabled'] ?? false)
                            <div class="flex flex-col items-center justify-center gap-0.5 py-1 text-[10px] font-medium text-slate-300 dark:text-slate-700 cursor-not-allowed select-none"
                                 title="Absensi tidak aktif saat ini">
                                <x-icon :name="$item['icon']" class="w-5.5 h-5.5 opacity-40" />
                                <span>{{ $item['label'] }}</span>
                            </div>
                        @else
                            <a href="{{ route($item['route']) }}"
                               class="flex flex-col items-center justify-center gap-0.5 py-1 text-[10px] font-semibold transition-all relative
                                      {{ $active ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300' }}">
                                <x-icon :name="$item['icon']" class="w-5.5 h-5.5" />
                                <span>{{ $item['label'] }}</span>
                                @if ($active)
                                    <span class="absolute bottom-0 w-1 h-1 rounded-full bg-indigo-600 dark:bg-indigo-400"></span>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </div>
                <div class="pb-[env(safe-area-inset-bottom)]"></div>
            </nav>
        </div>

        <script>
            document.getElementById('theme-toggle').addEventListener('click', () => {
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            });
        </script>
    </body>
</html>
