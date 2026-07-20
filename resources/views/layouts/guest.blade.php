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
            // Preferensi disimpan dengan key yang sama dengan portal siswa & panel admin.
            if (localStorage.getItem('theme') === 'dark'
                || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        </script>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-slate-50 dark:bg-slate-950 flex flex-col items-center justify-center px-4 py-10 relative overflow-hidden transition-colors duration-300">
            <!-- Glow background circles -->
            <div class="absolute w-96 h-96 rounded-full bg-indigo-600/10 dark:bg-indigo-600/5 blur-[120px] -top-20 -left-20"></div>
            <div class="absolute w-96 h-96 rounded-full bg-purple-600/10 dark:bg-purple-600/5 blur-[120px] -bottom-20 -right-20"></div>

            <button id="theme-toggle" type="button"
                    class="absolute top-4 right-4 w-9 h-9 flex items-center justify-center rounded-full text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-900 transition"
                    title="Ganti tema">
                <x-icon name="sun" class="w-5 h-5 hidden dark:block" />
                <x-icon name="moon" class="w-5 h-5 block dark:hidden" />
            </button>

            <a href="/" class="w-16 h-16 rounded-full bg-white flex items-center justify-center ring-4 ring-indigo-500/20 mb-4 overflow-hidden shadow-md">
                <img src="{{ asset('images/logo-sekolah.png') }}" alt="{{ config('app.name', 'Laravel') }}" class="w-14 h-14 object-contain">
            </a>
            <p class="font-outfit text-slate-800 dark:text-slate-200 font-semibold mb-6 text-lg tracking-wide">{{ config('app.name', 'Laravel') }}</p>

            <div class="w-full max-w-sm glass-card rounded-2xl shadow-xl p-7 relative z-10 transition-all duration-300 hover:shadow-2xl">
                {{ $slot }}
            </div>

            @if ($caption)
                <p class="text-slate-500 dark:text-slate-400 text-xs mt-6">{{ $caption }}</p>
            @endif
        </div>

        <script>
            document.getElementById('theme-toggle')?.addEventListener('click', () => {
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            });
        </script>
    </body>
</html>
