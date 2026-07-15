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
        <div class="min-h-screen bg-gradient-to-br from-indigo-600 to-indigo-500 dark:from-gray-900 dark:to-indigo-950 flex flex-col items-center justify-center px-4 py-10 relative">
            <button id="theme-toggle" type="button"
                    class="absolute top-4 right-4 w-9 h-9 flex items-center justify-center rounded-full text-white/80 hover:bg-white/10 hover:text-white transition"
                    title="Ganti tema">
                <x-icon name="sun" class="w-5 h-5 hidden dark:block" />
                <x-icon name="moon" class="w-5 h-5 block dark:hidden" />
            </button>

            <a href="/" class="w-16 h-16 rounded-full bg-white flex items-center justify-center ring-2 ring-white/30 mb-4 overflow-hidden">
                <img src="{{ asset('images/logo-sekolah.png') }}" alt="{{ config('app.name', 'Laravel') }}" class="w-14 h-14 object-contain">
            </a>
            <p class="text-white font-semibold mb-6">{{ config('app.name', 'Laravel') }}</p>

            <div class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-6">
                {{ $slot }}
            </div>

            @if ($caption)
                <p class="text-indigo-100 dark:text-indigo-200/70 text-xs mt-6">{{ $caption }}</p>
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
