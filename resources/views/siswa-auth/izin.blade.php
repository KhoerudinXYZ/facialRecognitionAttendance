<x-siswa-layout>
    @php
        $badgeMap = [
            'menunggu' => 'bg-amber-500 text-white shadow-amber-500/30 border-amber-400',
            'disetujui' => 'bg-emerald-500 text-white shadow-emerald-500/30 border-emerald-400',
            'ditolak' => 'bg-rose-500 text-white shadow-rose-500/30 border-rose-400',
        ];
        $labelMap = [
            'menunggu' => 'Menunggu',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
        ];
        $jenisLabelMap = [
            'izin' => '📋 Izin',
            'sakit' => '🏥 Sakit',
            'pulang_cepat' => '🚪 Pulang Cepat',
        ];
        $tampilkanForm = ! $pengajuanHariIni || $pengajuanHariIni->status === 'ditolak';
    @endphp

    <div class="space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="font-outfit font-black text-2xl sm:text-3xl text-transparent bg-clip-text bg-gradient-to-r from-purple-700 to-indigo-600 dark:from-purple-300 dark:to-indigo-400 tracking-tight">Pengajuan Izin</h1>
            <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta mt-1">Formulir Izin, Sakit & Pulang Cepat</p>
        </div>

        {{-- Status card untuk pengajuan Izin/Sakit biasa --}}
        @if ($pengajuanHariIni && $pengajuanHariIni->jenis !== 'pulang_cepat')
            <div class="bento-card rounded-[2rem] shadow-xl p-6 sm:p-8 relative overflow-hidden group">
                <!-- Watermark -->
                <div class="absolute -right-6 -top-2 text-[100px] sm:text-[130px] font-black text-slate-900/[0.03] dark:text-white/[0.02] font-lexend pointer-events-none tracking-tighter leading-none select-none transition-transform duration-700 group-hover:scale-105">
                    STATUS
                </div>

                <div class="relative z-10 space-y-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <span class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta block mb-1">Pengajuan Hari Ini</span>
                            <span class="font-black text-xl text-slate-800 dark:text-slate-100 font-outfit capitalize">{{ $jenisLabelMap[$pengajuanHariIni->jenis] ?? $pengajuanHariIni->jenis }}</span>
                        </div>
                        <span class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest shadow-lg border {{ $badgeMap[$pengajuanHariIni->status] }}">
                            {{ $labelMap[$pengajuanHariIni->status] }}
                        </span>
                    </div>
                    
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 leading-relaxed bg-white/60 dark:bg-slate-900/50 p-4 rounded-2xl border border-white/80 dark:border-slate-700/50 shadow-inner backdrop-blur-sm font-jakarta">{{ $pengajuanHariIni->keterangan }}</p>

                    @if ($pengajuanHariIni->status === 'ditolak' && $pengajuanHariIni->catatan_admin)
                        <div class="flex items-start gap-3 bg-rose-50/90 dark:bg-rose-900/30 border-2 border-rose-200 dark:border-rose-800/50 rounded-2xl p-4 text-sm text-rose-800 dark:text-rose-300 shadow-lg">
                            <div class="w-8 h-8 rounded-full bg-rose-100 dark:bg-rose-800 flex items-center justify-center shrink-0">
                                <x-icon name="alert-circle" class="w-5 h-5 text-rose-600 dark:text-rose-400 stroke-[2.5]" />
                            </div>
                            <div>
                                <span class="font-black font-lexend block text-base mb-1">Catatan Penolakan</span>
                                <span class="font-medium font-jakarta leading-relaxed text-rose-700/80 dark:text-rose-300/80">{{ $pengajuanHariIni->catatan_admin }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Status card untuk Izin Pulang Cepat --}}
        @if ($izinPulangCepat ?? false)
            <div class="bento-card rounded-[2rem] shadow-xl p-6 sm:p-8 relative overflow-hidden group border-2 {{ $izinPulangCepat->status === 'disetujui' ? 'border-emerald-400/50' : 'border-amber-400/30' }}">
                <div class="absolute -right-6 -top-2 text-[100px] sm:text-[130px] font-black text-orange-900/[0.03] dark:text-orange-100/[0.02] font-lexend pointer-events-none tracking-tighter leading-none select-none group-hover:scale-105 transition-transform duration-700">
                    CEPAT
                </div>
                <div class="relative z-10 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <span class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta block mb-1">Izin Pulang Cepat Hari Ini</span>
                            <span class="font-black text-xl text-slate-800 dark:text-slate-100 font-outfit">🚪 Pulang Cepat</span>
                        </div>
                        <span class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest shadow-lg border {{ $badgeMap[$izinPulangCepat->status] }}">
                            {{ $labelMap[$izinPulangCepat->status] }}
                        </span>
                    </div>

                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 leading-relaxed bg-white/60 dark:bg-slate-900/50 p-4 rounded-2xl border border-white/80 dark:border-slate-700/50 shadow-inner backdrop-blur-sm font-jakarta">{{ $izinPulangCepat->keterangan }}</p>

                    @if ($izinPulangCepat->status === 'disetujui')
                        <div class="flex items-center gap-3 bg-emerald-50/80 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800/50 rounded-2xl p-4 text-sm text-emerald-800 dark:text-emerald-300">
                            <x-icon name="check-circle" class="w-5 h-5 shrink-0 stroke-[2.5]" />
                            <span class="font-bold font-jakarta">Izin disetujui! Silakan buka menu <strong>Absen</strong> untuk melakukan absen pulang.</span>
                        </div>
                    @elseif ($izinPulangCepat->status === 'menunggu')
                        <div class="flex items-center gap-3 bg-amber-50/80 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 rounded-2xl p-4 text-sm text-amber-800 dark:text-amber-300">
                            <x-icon name="clock" class="w-5 h-5 shrink-0 stroke-[2.5]" />
                            <span class="font-bold font-jakarta">Menunggu persetujuan dari wali kelas. Pantau terus halaman ini.</span>
                        </div>
                    @endif

                    @if ($izinPulangCepat->status === 'ditolak' && $izinPulangCepat->catatan_admin)
                        <div class="flex items-start gap-3 bg-rose-50/90 dark:bg-rose-900/30 border-2 border-rose-200 dark:border-rose-800/50 rounded-2xl p-4 text-sm text-rose-800 dark:text-rose-300 shadow-lg">
                            <x-icon name="alert-circle" class="w-5 h-5 shrink-0 mt-0.5 stroke-[2.5]" />
                            <div>
                                <span class="font-black font-lexend block text-base mb-1">Catatan Penolakan</span>
                                <span class="font-medium font-jakarta leading-relaxed">{{ $izinPulangCepat->catatan_admin }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Request Submission Form -->
        @if ($tampilkanForm)
            <div class="bento-card rounded-[2rem] shadow-xl p-6 sm:p-8 relative overflow-hidden group">
                <!-- Watermark -->
                <div class="absolute -left-4 top-8 text-[100px] sm:text-[130px] font-black text-purple-900/[0.03] dark:text-purple-100/[0.02] font-lexend pointer-events-none tracking-tighter leading-none select-none transition-transform duration-700 group-hover:scale-105">
                    FORM
                </div>

                <div class="relative z-10 space-y-6">
                    @if ($pengajuanHariIni)
                        <div class="flex items-start gap-3 bg-amber-50/80 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 rounded-2xl p-4 text-sm text-amber-800 dark:text-amber-300 font-jakarta font-semibold backdrop-blur-sm">
                            <x-icon name="info" class="w-5 h-5 shrink-0 mt-0.5 stroke-[2.5]" />
                            Pengajuan sebelumnya ditolak. Silakan ajukan kembali di bawah.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('siswa.izin.store') }}" enctype="multipart/form-data" class="space-y-5">
                        @csrf

                        <div>
                            <x-input-label for="jenis" value="Jenis Pengajuan" class="font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px] font-jakarta" />
                            <select id="jenis" name="jenis" class="mt-2 block w-full bg-white/60 dark:bg-slate-900/50 border-2 border-slate-200 dark:border-slate-700 rounded-2xl focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 py-3.5 px-4 text-sm font-lexend font-bold dark:text-slate-100 shadow-inner backdrop-blur-sm transition-all duration-300">
                                <option value="izin" @selected(old('jenis') === 'izin')>📋 Izin Tidak Masuk</option>
                                <option value="sakit" @selected(old('jenis') === 'sakit')>🏥 Sakit</option>
                                <option value="pulang_cepat" @selected(old('jenis') === 'pulang_cepat')>🚪 Izin Pulang Cepat (Urgensi)</option>
                            </select>
                            <x-input-error :messages="$errors->get('jenis')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="keterangan" value="Keterangan / Alasan" class="font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px] font-jakarta" />
                            <x-text-input id="keterangan" class="block mt-2 w-full rounded-2xl border-2 border-slate-200 dark:border-slate-700 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 py-3.5 px-4 text-sm font-medium bg-white/60 dark:bg-slate-900/50 shadow-inner backdrop-blur-sm" type="text" name="keterangan" :value="old('keterangan')" required placeholder="Tulis alasan izin atau sakit secara singkat" />
                            <x-input-error :messages="$errors->get('keterangan')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="bukti" value="Bukti Surat / Foto" class="font-black text-slate-700 dark:text-slate-300 uppercase tracking-wider text-[11px] font-jakarta" />
                            <p class="text-[10px] text-slate-400 dark:text-slate-500 font-jakarta mt-0.5 mb-1">Untuk izin pulang cepat, unggah foto surat izin orang tua atau bukti keperluan.</p>
                            <input id="bukti" type="file" name="bukti" accept="image/*" required
                                   class="block mt-1 w-full text-sm text-slate-600 dark:text-slate-400 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:font-lexend file:bg-indigo-500 file:text-white hover:file:bg-indigo-600 file:shadow-md file:shadow-indigo-500/20 file:transition-all file:duration-300 border-2 border-slate-200 dark:border-slate-700 p-2 rounded-2xl bg-white/60 dark:bg-slate-900/50 shadow-inner backdrop-blur-sm" />
                            <x-input-error :messages="$errors->get('bukti')" class="mt-2" />
                        </div>

                        <button type="submit" class="relative mt-2 w-full overflow-hidden bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white py-4 rounded-2xl font-black font-lexend shadow-xl shadow-purple-500/30 transition-all duration-300 transform active:scale-95 border border-white/20 uppercase tracking-widest text-sm group">
                            <div class="absolute inset-0 bg-white/20 blur-md opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                            <span class="relative z-10 drop-shadow-sm flex items-center justify-center gap-2">
                                <x-icon name="send" class="w-5 h-5 stroke-[2.5]" /> Kirim Pengajuan
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <div class="text-center pt-2">
            <a href="{{ route('siswa.dashboard') }}" class="inline-flex items-center gap-2 text-sm font-black font-lexend text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 bg-white/50 dark:bg-slate-800/50 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 px-6 py-3 rounded-xl border border-white dark:border-slate-700/50 shadow-sm transition-all duration-300 group">
                <x-icon name="arrow-left" class="w-4 h-4 stroke-[2.5] group-hover:-translate-x-1 transition-transform" /> Kembali ke Beranda
            </a>
        </div>
    </div>
</x-siswa-layout>

