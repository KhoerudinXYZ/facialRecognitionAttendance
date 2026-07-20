<x-guest-layout icon="user-circle" caption="Portal Absensi Siswa">
    <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Lupa Password</h1>

    <div class="mb-4 text-sm text-gray-600 dark:text-gray-300">
        Masukkan NIS dan email orang tua yang terdaftar. Kalau cocok, tautan reset password akan dikirim ke email
        tersebut. Belum ada email orang tua terdaftar? Hubungi wali kelas/admin untuk reset akun.
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('siswa.password.email') }}">
        @csrf

        <div>
            <x-input-label for="nis" value="NIS" />
            <x-text-input id="nis" class="block mt-1 w-full" type="text" name="nis" :value="old('nis')" required autofocus />
            <x-input-error :messages="$errors->get('nis')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email_orang_tua" value="Email Orang Tua" />
            <x-text-input id="email_orang_tua" class="block mt-1 w-full" type="email" name="email_orang_tua" :value="old('email_orang_tua')" required />
            <x-input-error :messages="$errors->get('email_orang_tua')" class="mt-2" />
        </div>

        <button type="submit" class="mt-5 w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg font-medium transition">
            Kirim Tautan Reset Password
        </button>

        <a class="block mt-4 text-center text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" href="{{ route('siswa.login') }}">
            Kembali ke login
        </a>
    </form>
</x-guest-layout>
