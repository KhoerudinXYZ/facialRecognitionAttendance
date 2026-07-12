<x-guest-layout icon="user-circle" caption="Portal Absensi Siswa">
    <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4">Reset Password</h1>

    <form method="POST" action="{{ route('siswa.password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="nis" value="NIS" />
            <x-text-input id="nis" class="block mt-1 w-full" type="text" name="nis" :value="old('nis', $request->nis)" required autofocus />
            <x-input-error :messages="$errors->get('nis')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email_orang_tua" value="Email Orang Tua" />
            <x-text-input id="email_orang_tua" class="block mt-1 w-full" type="email" name="email_orang_tua" :value="old('email_orang_tua', $request->email_orang_tua)" required />
            <x-input-error :messages="$errors->get('email_orang_tua')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Password Baru" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Konfirmasi Password Baru" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button type="submit" class="mt-5 w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg font-medium transition">
            Reset Password
        </button>
    </form>
</x-guest-layout>
