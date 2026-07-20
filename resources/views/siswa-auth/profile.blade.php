<x-siswa-layout>
    <div class="space-y-6">
        <!-- Personal Info Card -->
        <div class="glass-card rounded-2xl shadow-sm p-6 border border-slate-200/50 dark:border-slate-800/55 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h1 class="font-outfit font-bold text-lg text-slate-800 dark:text-slate-50">Data Diri Siswa</h1>
                <x-icon name="user-circle" class="w-5 h-5 text-indigo-500" />
            </div>

            <dl class="grid grid-cols-2 gap-y-3.5 text-sm">
                <dt class="text-slate-400 dark:text-slate-500 font-semibold">Nama Lengkap</dt>
                <dd class="text-slate-800 dark:text-slate-100 font-bold font-outfit">{{ $siswa->nama }}</dd>

                <dt class="text-slate-400 dark:text-slate-500 font-semibold">NIS</dt>
                <dd class="text-slate-800 dark:text-slate-100 font-bold font-outfit">{{ $siswa->nis }}</dd>

                <dt class="text-slate-400 dark:text-slate-500 font-semibold">NISN</dt>
                <dd class="text-slate-800 dark:text-slate-100 font-bold font-outfit">{{ $siswa->nisn ?? '-' }}</dd>

                <dt class="text-slate-400 dark:text-slate-500 font-semibold">Jenis Kelamin</dt>
                <dd class="text-slate-800 dark:text-slate-100 font-bold font-outfit">{{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd>

                <dt class="text-slate-400 dark:text-slate-500 font-semibold">Kelas</dt>
                <dd class="text-slate-800 dark:text-slate-100 font-bold font-outfit">{{ $siswa->kelas->nama_kelas ?? '-' }}</dd>
            </dl>

            <p class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 pt-4 border-t border-slate-100 dark:border-slate-800/80 leading-relaxed">
                Data diri di atas disinkronisasi oleh wali kelas / administrator. Jika terdapat kekeliruan data, silakan hubungi bagian tata usaha sekolah.
            </p>
        </div>

        <!-- Parents Info & Profile Pic Form -->
        <div class="glass-card rounded-2xl shadow-sm p-6 border border-slate-200/50 dark:border-slate-800/55 space-y-4">
            <h2 class="font-outfit font-bold text-base text-slate-800 dark:text-slate-50 border-b border-slate-100 dark:border-slate-800 pb-3">Akun & Kontak Wali</h2>

            <form method="POST" action="{{ route('siswa.profile.update') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PATCH')

                <div class="flex items-center gap-4 bg-slate-50/50 dark:bg-slate-900/30 p-3.5 rounded-xl border border-slate-100 dark:border-slate-800/40">
                    @if ($siswa->foto)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($siswa->foto) }}"
                             alt="{{ $siswa->nama }}"
                             class="w-16 h-16 rounded-full object-cover ring-4 ring-indigo-500/20 shadow-md">
                    @else
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-indigo-500/10 to-indigo-500/5 dark:from-indigo-950/30 dark:to-indigo-900/10 ring-4 ring-indigo-500/20 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-extrabold text-2xl font-outfit shadow-inner">
                            {{ Illuminate\Support\Str::of($siswa->nama)->substr(0, 1)->upper() }}
                        </div>
                    @endif
                    <div class="flex-grow">
                        <x-input-label for="foto" value="Foto Profil" class="font-semibold text-slate-700 dark:text-slate-350" />
                        <input id="foto" type="file" name="foto" accept="image/*"
                               class="block mt-1.5 w-full text-xs text-slate-650 dark:text-slate-400 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-950/45 file:text-indigo-650 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/45" />
                        <x-input-error :messages="$errors->get('foto')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="username" value="Username" class="font-semibold text-slate-700 dark:text-slate-350" />
                    <x-text-input id="username" class="block mt-1.5 w-full rounded-xl border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20 py-2.5 text-sm font-medium" type="text" name="username" :value="old('username', $siswa->username)" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="no_hp_orang_tua" value="No. HP Orang Tua" class="font-semibold text-slate-700 dark:text-slate-350" />
                    <x-text-input id="no_hp_orang_tua" class="block mt-1.5 w-full rounded-xl border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20 py-2.5 text-sm font-medium" type="text" name="no_hp_orang_tua" :value="old('no_hp_orang_tua', $siswa->no_hp_orang_tua)" autocomplete="tel" placeholder="Contoh: 081234567890" />
                    <x-input-error :messages="$errors->get('no_hp_orang_tua')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email_orang_tua" value="Email Orang Tua" class="font-semibold text-slate-700 dark:text-slate-350" />
                    <x-text-input id="email_orang_tua" class="block mt-1.5 w-full rounded-xl border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20 py-2.5 text-sm font-medium" type="email" name="email_orang_tua" :value="old('email_orang_tua', $siswa->email_orang_tua)" autocomplete="email" placeholder="Contoh: orangtua@email.com" />
                    <x-input-error :messages="$errors->get('email_orang_tua')" class="mt-2" />
                    <p class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 mt-1.5 leading-normal">Email ini digunakan untuk mengirimkan laporan kehadiran harian siswa secara otomatis dan memulihkan password akun.</p>
                </div>

                <button type="submit" class="mt-4 w-full bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white py-3 rounded-xl font-semibold shadow-md shadow-indigo-500/10 hover:shadow-lg hover:shadow-indigo-500/20 transition-all duration-300 transform active:scale-[0.98]">
                    Simpan Perubahan
                </button>
            </form>
        </div>

        <!-- Password Update Form -->
        <div class="glass-card rounded-2xl shadow-sm p-6 border border-slate-200/50 dark:border-slate-800/55 space-y-4">
            <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                <x-icon name="key" class="w-5 h-5 text-indigo-500" />
                <h2 class="font-outfit font-bold text-base text-slate-800 dark:text-slate-50">Perbarui Kata Sandi</h2>
            </div>

            <form method="POST" action="{{ route('siswa.password.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="current_password" value="Kata Sandi Lama" class="font-semibold text-slate-700 dark:text-slate-350" />
                    <x-text-input id="current_password" class="block mt-1.5 w-full rounded-xl border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20 py-2.5 text-sm font-medium" type="password" name="current_password" required autocomplete="current-password" placeholder="••••••••" />
                    <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" value="Kata Sandi Baru" class="font-semibold text-slate-700 dark:text-slate-350" />
                    <x-text-input id="password" class="block mt-1.5 w-full rounded-xl border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20 py-2.5 text-sm font-medium" type="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" />
                    <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" value="Konfirmasi Kata Sandi Baru" class="font-semibold text-slate-700 dark:text-slate-350" />
                    <x-text-input id="password_confirmation" class="block mt-1.5 w-full rounded-xl border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20 py-2.5 text-sm font-medium" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Ketik ulang kata sandi baru" />
                    <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
                </div>

                <button type="submit" class="mt-4 w-full bg-slate-800 hover:bg-slate-900 dark:bg-slate-700 dark:hover:bg-slate-600 text-white py-3 rounded-xl font-semibold shadow-md transition-all duration-300 transform active:scale-[0.98]">
                    Ganti Kata Sandi
                </button>
            </form>
        </div>

        <div class="text-center pt-2">
            <a href="{{ route('siswa.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                <x-icon name="arrow-left" class="w-3.5 h-3.5" /> Kembali ke Beranda
            </a>
        </div>
    </div>
</x-siswa-layout>
