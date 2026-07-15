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

        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <header class="bg-gradient-to-r from-indigo-600 to-indigo-500 shadow">
                <div class="max-w-lg mx-auto px-4 py-4 flex justify-between items-center">
                    <a href="{{ route('siswa.profile.edit') }}" class="flex items-center gap-3">
                        @if ($siswaUser->foto)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($siswaUser->foto) }}"
                                 alt="{{ $siswaUser->nama }}"
                                 class="w-11 h-11 rounded-full object-cover ring-2 ring-white/40">
                        @else
                            <div class="w-11 h-11 rounded-full bg-white/20 ring-2 ring-white/40 flex items-center justify-center text-white font-semibold">
                                {{ Illuminate\Support\Str::of($siswaUser->nama)->substr(0, 1)->upper() }}
                            </div>
                        @endif
                        <div>
                            <p class="font-semibold text-white leading-tight">{{ $siswaUser->nama }}</p>
                            <p class="text-xs text-indigo-100">{{ $siswaUser->kelas->nama_kelas ?? '-' }}</p>
                        </div>
                    </a>
                    <div class="flex items-center gap-1">
                        <button id="theme-toggle" type="button"
                                class="w-9 h-9 flex items-center justify-center rounded-full text-indigo-100 hover:bg-white/10 hover:text-white transition"
                                title="Ganti tema">
                            <x-icon name="sun" class="w-5 h-5 hidden dark:block" />
                            <x-icon name="moon" class="w-5 h-5 block dark:hidden" />
                        </button>
                        <form action="{{ route('siswa.logout') }}" method="POST">
                            @csrf
                            <button class="w-9 h-9 flex items-center justify-center rounded-full text-indigo-100 hover:bg-white/10 hover:text-white transition"
                                    title="Keluar">
                                <x-icon name="logout" class="w-5 h-5" />
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            @include('layouts.flash')

            <main class="max-w-lg mx-auto px-4 py-6 pb-24">
                {{ $slot }}
            </main>

            <nav class="fixed inset-x-0 bottom-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 z-40">
                <div class="max-w-lg mx-auto grid grid-cols-4">
                    @php
                        $todayNav = \App\Models\Pengaturan::sekarang()->startOfDay();
                        $absenHariIniNav = $siswaUser->absensi()->whereDate('tanggal', $todayNav)->first();
                        $absenNavNonaktif = \App\Models\HariLibur::isLibur($todayNav) || ($absenHariIniNav && $absenHariIniNav->jam_pulang);

                        $navItems = [
                            ['route' => 'siswa.dashboard', 'match' => 'siswa.dashboard', 'icon' => 'home', 'label' => 'Beranda'],
                            ['route' => 'siswa.absen', 'match' => 'siswa.absen*', 'icon' => 'camera', 'label' => 'Absen', 'disabled' => $absenNavNonaktif],
                            ['route' => 'siswa.riwayat', 'match' => 'siswa.riwayat', 'icon' => 'clock', 'label' => 'Riwayat'],
                            ['route' => 'siswa.wajah', 'match' => ['siswa.wajah', 'siswa.enroll.*'], 'icon' => 'user-circle', 'label' => 'Wajah'],
                        ];
                    @endphp
                    @foreach ($navItems as $item)
                        @php $active = request()->routeIs($item['match']); @endphp
                        @if ($item['disabled'] ?? false)
                            <div class="flex flex-col items-center justify-center gap-0.5 py-2.5 text-xs font-medium text-gray-300 dark:text-gray-600 cursor-not-allowed"
                                 title="Absensi tidak aktif saat ini">
                                <x-icon :name="$item['icon']" class="w-6 h-6" />
                                {{ $item['label'] }}
                            </div>
                        @else
                            <a href="{{ route($item['route']) }}"
                               class="flex flex-col items-center justify-center gap-0.5 py-2.5 text-xs font-medium transition
                                      {{ $active ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300' }}">
                                <x-icon :name="$item['icon']" class="w-6 h-6" />
                                {{ $item['label'] }}
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
