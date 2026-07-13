<x-siswa-layout>
    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Data Diri</h1>
                <x-icon name="user-circle" class="w-5 h-5 text-gray-400 dark:text-gray-500" />
            </div>

            <dl class="grid grid-cols-2 gap-y-3 text-sm">
                <dt class="text-gray-500 dark:text-gray-400">Nama</dt>
                <dd class="text-gray-800 dark:text-gray-100 font-medium">{{ $siswa->nama }}</dd>

                <dt class="text-gray-500 dark:text-gray-400">NIS</dt>
                <dd class="text-gray-800 dark:text-gray-100 font-medium">{{ $siswa->nis }}</dd>

                <dt class="text-gray-500 dark:text-gray-400">NISN</dt>
                <dd class="text-gray-800 dark:text-gray-100 font-medium">{{ $siswa->nisn ?? '-' }}</dd>

                <dt class="text-gray-500 dark:text-gray-400">Jenis Kelamin</dt>
                <dd class="text-gray-800 dark:text-gray-100 font-medium">{{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd>

                <dt class="text-gray-500 dark:text-gray-400">Kelas</dt>
                <dd class="text-gray-800 dark:text-gray-100 font-medium">{{ $siswa->kelas->nama_kelas ?? '-' }}</dd>
            </dl>

            <p class="text-xs text-gray-400 dark:text-gray-500 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                Data di atas dikelola oleh wali kelas/admin. Hubungi mereka jika ada yang perlu dikoreksi.
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100 mb-4">Akun &amp; Kontak Orang Tua</h2>

            <form method="POST" action="{{ route('siswa.profile.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <div class="flex items-center gap-4">
                    @if ($siswa->foto)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($siswa->foto) }}"
                             alt="{{ $siswa->nama }}"
                             class="w-16 h-16 rounded-full object-cover ring-2 ring-gray-200 dark:ring-gray-700">
                    @else
                        <div class="w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-900/30 ring-2 ring-gray-200 dark:ring-gray-700 flex items-center justify-center text-indigo-400 dark:text-indigo-500 font-semibold text-xl">
                            {{ Illuminate\Support\Str::of($siswa->nama)->substr(0, 1)->upper() }}
                        </div>
                    @endif
                    <div class="flex-1">
                        <x-input-label for="foto" value="Foto Profil" />
                        <input id="foto" type="file" name="foto" accept="image/*"
                               class="block mt-1 w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 dark:file:bg-indigo-900/40 file:text-indigo-600 dark:file:text-indigo-300 hover:file:bg-indigo-100" />
                        <x-input-error :messages="$errors->get('foto')" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4">
                    <x-input-label for="username" value="Username" />
                    <x-text-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username', $siswa->username)" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="no_hp_orang_tua" value="No. HP Orang Tua" />
                    <x-text-input id="no_hp_orang_tua" class="block mt-1 w-full" type="text" name="no_hp_orang_tua" :value="old('no_hp_orang_tua', $siswa->no_hp_orang_tua)" autocomplete="tel" />
                    <x-input-error :messages="$errors->get('no_hp_orang_tua')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="email_orang_tua" value="Email Orang Tua" />
                    <x-text-input id="email_orang_tua" class="block mt-1 w-full" type="email" name="email_orang_tua" :value="old('email_orang_tua', $siswa->email_orang_tua)" autocomplete="email" />
                    <x-input-error :messages="$errors->get('email_orang_tua')" class="mt-2" />
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Dipakai untuk notifikasi kehadiran &amp; reset password.</p>
                </div>

                <button type="submit" class="mt-5 w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg font-medium transition">
                    Simpan Perubahan
                </button>
            </form>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <div class="flex items-center gap-2 mb-4">
                <x-icon name="key" class="w-5 h-5 text-gray-400 dark:text-gray-500" />
                <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Ganti Password</h2>
            </div>

            <form method="POST" action="{{ route('siswa.password.update') }}">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="current_password" value="Password Saat Ini" />
                    <x-text-input id="current_password" class="block mt-1 w-full" type="password" name="current_password" required autocomplete="current-password" />
                    <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="password" value="Password Baru" />
                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="password_confirmation" value="Konfirmasi Password Baru" />
                    <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
                </div>

                <button type="submit" class="mt-5 w-full bg-gray-800 hover:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 text-white py-2.5 rounded-lg font-medium transition">
                    Ganti Password
                </button>
            </form>
        </div>
    </div>
</x-siswa-layout>
