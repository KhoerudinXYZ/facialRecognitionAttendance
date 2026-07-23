<x-guest-layout icon="cog" caption="Panel Admin & Staff">
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6 text-center">
        <h1 class="font-outfit text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-violet-600 dark:from-indigo-400 dark:to-violet-400 tracking-tight">Login Admin</h1>
        <p class="text-sm font-jakarta text-slate-500 dark:text-slate-400 mt-1">Silakan masuk ke panel manajemen.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" class="font-bold font-lexend text-slate-700 dark:text-slate-300" />
            <x-text-input id="email" class="block mt-1.5 w-full rounded-xl border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20 py-2.5 transition-all duration-300 bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm hover:border-indigo-400/50" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="admin@example.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div x-data="{ show: false }">
            <div class="flex justify-between items-center">
                <x-input-label for="password" :value="__('Password')" class="font-bold font-lexend text-slate-700 dark:text-slate-300" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-jakarta text-indigo-600 dark:text-indigo-400 hover:underline hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors" href="{{ route('password.request') }}">
                        Lupa password?
                    </a>
                @endif
            </div>

            <div class="relative mt-1.5 group">
                <x-text-input id="password" class="block w-full rounded-xl border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20 py-2.5 pr-12 transition-all duration-300 bg-white/50 dark:bg-slate-900/50 backdrop-blur-sm group-hover:border-indigo-400/50"
                                x-bind:type="show ? 'text' : 'password'"
                                name="password"
                                required autocomplete="current-password" placeholder="••••••••" />
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-indigo-600 dark:text-slate-500 dark:hover:text-indigo-400 transition-colors focus:outline-none">
                    <x-icon name="eye" class="w-5 h-5" x-show="!show" />
                    <x-icon name="eye-off" class="w-5 h-5" x-show="show" style="display: none;" />
                </button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between pt-1">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800 text-indigo-600 shadow-sm focus:ring-indigo-500/20 w-4 h-4" name="remember">
                <span class="ms-2.5 text-sm text-slate-600 dark:text-slate-400 select-none">{{ __('Remember me') }}</span>
            </label>
        </div>

        <button type="submit" class="mt-4 w-full bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white py-3 rounded-xl font-bold font-lexend shadow-lg shadow-indigo-500/25 dark:shadow-indigo-500/15 hover:shadow-xl hover:shadow-indigo-500/40 transition-all duration-300 transform active:scale-95 flex items-center justify-center gap-2 group">
            <span>{{ __('Log in') }}</span>
            <x-icon name="arrow-right" class="w-4 h-4 stroke-[3] group-hover:translate-x-1 transition-transform" />
        </button>
    </form>

    <div class="mt-6 pt-5 border-t border-slate-200 dark:border-slate-800 text-center text-xs text-slate-400 dark:text-slate-500">
        Siswa? <a href="{{ route('siswa.login') }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">Login / daftar di portal siswa</a>
    </div>
</x-guest-layout>
