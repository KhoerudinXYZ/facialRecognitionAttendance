<x-guest-layout icon="user-circle" caption="Sistem Informasi Absensi SMKN 1 Sindang">
    @if ($isLibur)
        <div class="flex items-start gap-2.5 bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-200 dark:border-yellow-900/40 rounded-xl px-4 py-3 text-xs text-yellow-800 dark:text-yellow-300 mb-5">
            <x-icon name="calendar" class="w-4 h-4 shrink-0 mt-0.5" />
            <div>
                <span class="font-semibold block mb-0.5">Hari ini Libur</span>
                Sistem absensi wajah dinonaktifkan sementara.
            </div>
        </div>
    @endif

    <div class="mb-6">
        <h1 class="font-outfit text-2xl font-bold text-slate-800 dark:text-slate-100 tracking-tight">Registrasi Siswa</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Buat akun mandiri dengan NIS terdaftar Anda.</p>
    </div>

    <form method="POST" action="{{ route('siswa.register') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="nis" value="Nomor Induk Siswa (NIS)" class="font-medium text-slate-700 dark:text-slate-300" />
            <x-text-input id="nis" class="block mt-1.5 w-full rounded-xl border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20 py-2.5" type="text" name="nis" :value="old('nis')" required autofocus placeholder="Masukkan NIS terdaftar" />
            <x-input-error :messages="$errors->get('nis')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="username" value="Username" class="font-medium text-slate-700 dark:text-slate-300" />
            <x-text-input id="username" class="block mt-1.5 w-full rounded-xl border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20 py-2.5" type="text" name="username" :value="old('username')" required autocomplete="username" placeholder="Pilih username baru" />
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Password" class="font-medium text-slate-700 dark:text-slate-300" />
            <x-text-input id="password" class="block mt-1.5 w-full rounded-xl border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20 py-2.5" type="password" name="password" required autocomplete="new-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Konfirmasi Password" class="font-medium text-slate-700 dark:text-slate-300" />
            <x-text-input id="password_confirmation" class="block mt-1.5 w-full rounded-xl border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20 py-2.5" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button type="submit" class="mt-2 w-full bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white py-3 rounded-xl font-semibold shadow-lg shadow-indigo-500/20 dark:shadow-indigo-500/10 hover:shadow-xl hover:shadow-indigo-500/30 transition-all duration-300 transform active:scale-95 flex items-center justify-center gap-2">
            <span>Daftar Akun</span>
            <x-icon name="arrow-right" class="w-4 h-4" />
        </button>

        <div class="text-center pt-2">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Sudah punya akun? 
                <a class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline" href="{{ route('siswa.login') }}">
                    Login di sini
                </a>
            </p>
        </div>
    </form>

    <div class="mt-6 pt-5 border-t border-slate-200 dark:border-slate-800 text-center text-xs text-slate-400 dark:text-slate-500">
        Staff/Guru? <a href="{{ route('login') }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">Login sebagai Admin</a>
    </div>
</x-guest-layout>
