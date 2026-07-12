<x-guest-layout icon="user-circle" caption="Portal Absensi Siswa">
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($isLibur)
        <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 text-xs text-gray-600 dark:text-gray-300 mb-4">
            <x-icon name="x-circle" class="w-3.5 h-3.5 shrink-0" />
            Hari ini libur — absensi tidak aktif.
        </div>
    @endif

    <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Login Siswa</h1>

    <form method="POST" action="{{ route('siswa.login') }}">
        @csrf

        <div>
            <x-input-label for="username" value="Username" />
            <x-text-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-300">Ingat saya</span>
            </label>
        </div>

        <button type="submit" class="mt-5 w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg font-medium transition">
            Login
        </button>

        <a class="block mt-4 text-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" href="{{ route('siswa.register') }}">
            Belum punya akun? <span class="text-indigo-600 dark:text-indigo-400 font-medium">Registrasi</span>
        </a>
    </form>

    <div class="mt-6 pt-4 border-t dark:border-gray-700 text-center text-sm text-gray-500 dark:text-gray-400">
        Staff? <a href="{{ route('login') }}" class="underline text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">Login di sini</a>
    </div>
</x-guest-layout>
