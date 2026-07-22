<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/logo-sekolah.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script>
            // Diterapkan sebelum body dirender supaya tidak ada flash warna salah.
            // Preferensi disimpan dengan key yang sama dengan portal siswa.
            if (localStorage.getItem('theme') === 'dark'
                || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        </script>
    </head>
    <body class="font-sans antialiased">
        <div class="page-bg min-h-screen text-slate-800 dark:text-slate-100 transition-colors duration-300 relative overflow-x-hidden">
            <!-- Ambient blurred background glows for admin area -->
            <div class="absolute top-0 left-1/4 w-[600px] h-[600px] bg-indigo-500/8 dark:bg-indigo-500/14 rounded-full blur-[140px] pointer-events-none -z-10"></div>
            <div class="absolute top-1/3 right-0 w-[400px] h-[400px] bg-violet-500/6 dark:bg-violet-500/12 rounded-full blur-[120px] pointer-events-none -z-10"></div>
            <div class="absolute bottom-0 left-0 w-[500px] h-[400px] bg-cyan-500/4 dark:bg-cyan-500/8 rounded-full blur-[120px] pointer-events-none -z-10"></div>

            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200/50 dark:border-slate-800/50">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main class="relative z-10">
                @include('layouts.flash')
                {{ $slot }}
            </main>
        </div>

        <script>
            document.querySelectorAll('#theme-toggle, #theme-toggle-mobile').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const isDark = document.documentElement.classList.toggle('dark');
                    localStorage.setItem('theme', isDark ? 'dark' : 'light');
                });
            });
        </script>
    </body>
</html>
