<x-siswa-layout>
    <div class="space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="font-outfit font-black text-2xl sm:text-3xl text-transparent bg-clip-text bg-gradient-to-r from-slate-900 to-indigo-600 dark:from-white dark:to-indigo-400 tracking-tight">Profil Saya</h1>
            <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta mt-1">Data Diri & Pengaturan Akun</p>
        </div>

        <!-- Personal Info Card -->
        <div class="bento-card rounded-[2.5rem] shadow-xl p-6 sm:p-10 relative overflow-hidden group">
            <!-- Watermark -->
            <div class="absolute -right-6 -top-4 text-[120px] sm:text-[160px] font-black text-slate-900/[0.03] dark:text-white/[0.02] font-lexend pointer-events-none tracking-tighter leading-none select-none transition-transform duration-700 group-hover:scale-105">
                PROFIL
            </div>

            <div class="relative z-10 space-y-6">
                <!-- Avatar & Name Hero -->
                <div class="flex flex-col items-center text-center pb-6 border-b border-slate-200/50 dark:border-slate-700/50">
                    <div class="relative mb-4">
                        <!-- Glow ring behind avatar -->
                        <div class="absolute inset-0 bg-indigo-500/30 blur-2xl rounded-full scale-150 pointer-events-none animate-pulse"></div>
                        @if ($siswa->foto)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($siswa->foto) }}"
                                 alt="{{ $siswa->nama }}"
                                 class="relative w-24 h-24 rounded-full object-cover ring-4 ring-white dark:ring-slate-800 shadow-2xl shadow-indigo-500/20 border-2 border-indigo-200 dark:border-indigo-700">
                        @else
                            <div class="relative w-24 h-24 rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 ring-4 ring-white dark:ring-slate-800 flex items-center justify-center text-white font-black text-3xl font-lexend shadow-2xl shadow-indigo-500/30 border-2 border-indigo-300 dark:border-indigo-600">
                                {{ Illuminate\Support\Str::of($siswa->nama)->substr(0, 1)->upper() }}
                            </div>
                        @endif
                    </div>
                    <h2 class="font-outfit font-black text-xl text-slate-800 dark:text-slate-100">{{ $siswa->nama }}</h2>
                    <span class="text-[11px] font-black text-indigo-600 dark:text-indigo-400 font-lexend uppercase tracking-widest mt-1">{{ $siswa->kelas->nama_kelas ?? '-' }}</span>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @php
                        $infoItems = [
                            ['label' => 'NIS', 'value' => $siswa->nis, 'icon' => 'hash'],
                            ['label' => 'NISN', 'value' => $siswa->nisn ?? '-', 'icon' => 'badge-check'],
                            ['label' => 'Jenis Kelamin', 'value' => $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan', 'icon' => 'user'],
                            ['label' => 'Kelas', 'value' => $siswa->kelas->nama_kelas ?? '-', 'icon' => 'school'],
                        ];
                    @endphp
                    @foreach ($infoItems as $info)
                        <div class="bg-white/50 dark:bg-slate-900/40 rounded-2xl p-4 border border-white/60 dark:border-slate-700/50 shadow-inner backdrop-blur-sm flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0">
                                <x-icon name="{{ $info['icon'] }}" class="w-5 h-5 text-indigo-500 stroke-[2]" />
                            </div>
                            <div>
                                <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta block">{{ $info['label'] }}</span>
                                <span class="font-lexend font-black text-slate-800 dark:text-slate-100 text-base">{{ $info['value'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 pt-4 border-t border-slate-200/50 dark:border-slate-700/50 leading-relaxed font-jakarta uppercase tracking-wider text-center">
                    Data diri disinkronisasi oleh wali kelas. Hubungi tata usaha jika ada kekeliruan.
                </p>
            </div>
        </div>

        <!-- Account & Parent Contact Form -->
        <div class="bento-card rounded-[2.5rem] shadow-xl p-6 sm:p-10 relative overflow-hidden group">
            <!-- Watermark -->
            <div class="absolute -left-4 top-8 text-[100px] sm:text-[130px] font-black text-indigo-900/[0.03] dark:text-indigo-100/[0.02] font-lexend pointer-events-none tracking-tighter leading-none select-none transition-transform duration-700 group-hover:scale-105">
                AKUN
            </div>

            <div class="relative z-10 space-y-6">
                <div class="flex items-center gap-3 pb-5 border-b border-slate-200/50 dark:border-slate-700/50">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                        <x-icon name="settings" class="w-5 h-5 text-white stroke-[2.5]" />
                    </div>
                    <div>
                        <h2 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100">Akun & Kontak Wali</h2>
                        <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta">Pengaturan Profil</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('siswa.profile.update') }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @method('PATCH')

                    <!-- Photo Upload -->
                    <div class="flex items-center gap-5 bg-white/50 dark:bg-slate-900/40 p-5 rounded-2xl border border-white/60 dark:border-slate-700/50 shadow-inner backdrop-blur-sm">
                        @if ($siswa->foto)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($siswa->foto) }}"
                                 alt="{{ $siswa->nama }}"
                                 class="w-16 h-16 rounded-2xl object-cover ring-2 ring-indigo-200 dark:ring-indigo-700 shadow-lg">
                        @else
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 ring-2 ring-indigo-200 dark:ring-indigo-700 flex items-center justify-center text-white font-black text-2xl font-lexend shadow-lg">
                                {{ Illuminate\Support\Str::of($siswa->nama)->substr(0, 1)->upper() }}
                            </div>
                        @endif
                        <div class="flex-grow">
                            <x-input-label for="foto" value="Foto Profil" class="font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px] font-jakarta" />
                            <input id="foto" type="file" name="foto" accept="image/*"
                                   class="block mt-2 w-full text-sm text-slate-600 dark:text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:font-lexend file:bg-indigo-500 file:text-white hover:file:bg-indigo-600 file:shadow-md file:shadow-indigo-500/20 file:transition-all file:duration-300" />
                            <x-input-error :messages="$errors->get('foto')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="username" value="Username" class="font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px] font-jakarta" />
                        <x-text-input id="username" class="block mt-2 w-full rounded-2xl border-2 border-slate-200 dark:border-slate-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 py-3.5 px-4 text-sm font-lexend font-bold bg-white/60 dark:bg-slate-900/50 shadow-inner backdrop-blur-sm" type="text" name="username" :value="old('username', $siswa->username)" required autocomplete="username" />
                        <x-input-error :messages="$errors->get('username')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="no_hp_orang_tua" value="No. HP Orang Tua" class="font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px] font-jakarta" />
                        <x-text-input id="no_hp_orang_tua" class="block mt-2 w-full rounded-2xl border-2 border-slate-200 dark:border-slate-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 py-3.5 px-4 text-sm font-medium bg-white/60 dark:bg-slate-900/50 shadow-inner backdrop-blur-sm" type="text" name="no_hp_orang_tua" :value="old('no_hp_orang_tua', $siswa->no_hp_orang_tua)" autocomplete="tel" placeholder="Contoh: 081234567890" />
                        <x-input-error :messages="$errors->get('no_hp_orang_tua')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="email_orang_tua" value="Email Orang Tua" class="font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px] font-jakarta" />
                        <x-text-input id="email_orang_tua" class="block mt-2 w-full rounded-2xl border-2 border-slate-200 dark:border-slate-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 py-3.5 px-4 text-sm font-medium bg-white/60 dark:bg-slate-900/50 shadow-inner backdrop-blur-sm" type="email" name="email_orang_tua" :value="old('email_orang_tua', $siswa->email_orang_tua)" autocomplete="email" placeholder="Contoh: orangtua@email.com" />
                        <x-input-error :messages="$errors->get('email_orang_tua')" class="mt-2" />
                        <p class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 mt-2 leading-normal font-jakarta">Email ini digunakan untuk mengirimkan laporan kehadiran harian siswa secara otomatis.</p>
                    </div>

                    <button type="submit" class="relative mt-2 w-full overflow-hidden bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white py-4 rounded-2xl font-black font-lexend shadow-xl shadow-indigo-500/30 transition-all duration-300 transform active:scale-95 border border-white/20 uppercase tracking-widest text-sm group">
                        <div class="absolute inset-0 bg-white/20 blur-md opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <span class="relative z-10 drop-shadow-sm">Simpan Perubahan</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Password Update Form -->
        <div class="bento-card rounded-[2.5rem] shadow-xl p-6 sm:p-10 relative overflow-hidden group">
            <!-- Watermark -->
            <div class="absolute -right-6 top-6 text-[100px] sm:text-[130px] font-black text-slate-900/[0.03] dark:text-white/[0.02] font-lexend pointer-events-none tracking-tighter leading-none select-none transition-transform duration-700 group-hover:scale-105">
                KEY
            </div>

            <div class="relative z-10 space-y-6">
                <div class="flex items-center gap-3 pb-5 border-b border-slate-200/50 dark:border-slate-700/50">
                    <div class="w-10 h-10 rounded-xl bg-slate-800 dark:bg-slate-700 flex items-center justify-center shadow-lg shadow-slate-800/30">
                        <x-icon name="key-round" class="w-5 h-5 text-white stroke-[2.5]" />
                    </div>
                    <div>
                        <h2 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100">Perbarui Kata Sandi</h2>
                        <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta">Keamanan Akun</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('siswa.password.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="current_password" value="Kata Sandi Lama" class="font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px] font-jakarta" />
                        <x-text-input id="current_password" class="block mt-2 w-full rounded-2xl border-2 border-slate-200 dark:border-slate-700 focus:border-slate-500 focus:ring-4 focus:ring-slate-500/20 py-3.5 px-4 text-sm font-medium bg-white/60 dark:bg-slate-900/50 shadow-inner backdrop-blur-sm" type="password" name="current_password" required autocomplete="current-password" placeholder="••••••••" />
                        <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password" value="Kata Sandi Baru" class="font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px] font-jakarta" />
                        <x-text-input id="password" class="block mt-2 w-full rounded-2xl border-2 border-slate-200 dark:border-slate-700 focus:border-slate-500 focus:ring-4 focus:ring-slate-500/20 py-3.5 px-4 text-sm font-medium bg-white/60 dark:bg-slate-900/50 shadow-inner backdrop-blur-sm" type="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" />
                        <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="password_confirmation" value="Konfirmasi Kata Sandi Baru" class="font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px] font-jakarta" />
                        <x-text-input id="password_confirmation" class="block mt-2 w-full rounded-2xl border-2 border-slate-200 dark:border-slate-700 focus:border-slate-500 focus:ring-4 focus:ring-slate-500/20 py-3.5 px-4 text-sm font-medium bg-white/60 dark:bg-slate-900/50 shadow-inner backdrop-blur-sm" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Ketik ulang kata sandi baru" />
                        <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
                    </div>

                    <button type="submit" class="relative mt-2 w-full overflow-hidden bg-gradient-to-r from-slate-800 to-slate-700 hover:from-slate-700 hover:to-slate-600 dark:from-slate-700 dark:to-slate-600 dark:hover:from-slate-600 dark:hover:to-slate-500 text-white py-4 rounded-2xl font-black font-lexend shadow-xl shadow-slate-800/30 transition-all duration-300 transform active:scale-95 border border-white/10 uppercase tracking-widest text-sm group">
                        <div class="absolute inset-0 bg-white/10 blur-md opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <span class="relative z-10 drop-shadow-sm flex items-center justify-center gap-2">
                            <x-icon name="shield-check" class="w-5 h-5 stroke-[2.5]" /> Ganti Kata Sandi
                        </span>
                    </button>
                </form>
            </div>
        </div>

        <div class="text-center pt-2">
            <a href="{{ route('siswa.dashboard') }}" class="inline-flex items-center gap-2 text-sm font-black font-lexend text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 bg-white/50 dark:bg-slate-800/50 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 px-6 py-3 rounded-xl border border-white dark:border-slate-700/50 shadow-sm transition-all duration-300 group">
                <x-icon name="arrow-left" class="w-4 h-4 stroke-[2.5] group-hover:-translate-x-1 transition-transform" /> Kembali ke Beranda
            </a>
        </div>
    </div>
</x-siswa-layout>

