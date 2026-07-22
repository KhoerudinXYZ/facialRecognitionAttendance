<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-outfit font-black text-2xl sm:text-3xl text-transparent bg-clip-text bg-gradient-to-r from-slate-900 via-indigo-900 to-indigo-600 dark:from-white dark:via-indigo-100 dark:to-indigo-400 tracking-tight">Pengaturan</h2>
                <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta mt-0.5">Konfigurasi Sistem Absensi</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
        
        {{-- Simulasi Waktu Card (Yellow Warning Style) --}}
        <div class="bento-card rounded-[2rem] p-6 border-amber-200/50 dark:border-amber-800/40 relative overflow-hidden bg-gradient-to-r from-amber-500/10 via-orange-500/10 to-amber-500/5 backdrop-blur-md">
            <div class="absolute -right-4 -bottom-4 text-[70px] font-black text-amber-900/[0.05] dark:text-amber-100/[0.03] font-lexend pointer-events-none tracking-tighter leading-none select-none">TEST</div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white flex items-center justify-center shadow-lg shadow-amber-500/30">
                        <x-icon name="beaker" class="w-5 h-5 stroke-[2.5]" />
                    </div>
                    <div>
                        <h3 class="font-outfit font-black text-lg text-amber-900 dark:text-amber-100 tracking-tight">Simulasi Waktu (Testing)</h3>
                    </div>
                </div>
                <p class="text-[11px] font-semibold text-amber-800 dark:text-amber-300 font-jakarta mb-5">
                    Set jam di sini untuk menguji absen masuk/pulang tanpa nunggu jam asli — selama simulasi aktif, seluruh portal siswa ikut memakai jam ini.
                </p>

                <div class="flex flex-wrap items-center gap-2 mb-5">
                    @if ($pengaturan->simulasi_waktu)
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-200 dark:bg-amber-900 text-amber-800 dark:text-amber-200 font-lexend font-bold text-xs shadow-sm">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-500 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-600"></span>
                            </span>
                            Simulasi aktif: {{ $pengaturan->simulasi_waktu->format('d/m/Y H:i') }}
                        </span>
                    @endif

                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-lexend font-bold text-xs shadow-sm
                                 {{ $isLibur ? 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300' }}">
                        <x-icon :name="$isLibur ? 'x-circle' : 'check'" class="w-3.5 h-3.5 stroke-[2.5]" />
                        {{ $efektifSekarang->format('d/m/Y H:i') }}: {{ $isLibur ? 'Libur' : 'Aktif' }}
                    </span>
                </div>

                <div class="flex flex-wrap gap-4 items-end">
                    <form action="{{ route('pengaturan.simulasi') }}" method="POST" class="flex flex-wrap gap-4 items-end">
                        @csrf @method('PUT')
                        <div>
                            <label for="simulasi_waktu" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-amber-700 dark:text-amber-400 mb-1.5">Tanggal & Jam Simulasi</label>
                            <input id="simulasi_waktu" name="simulasi_waktu" type="datetime-local" 
                                   class="block w-full rounded-xl border-amber-200 dark:border-amber-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500/30 backdrop-blur-sm px-4 py-2.5"
                                   value="{{ old('simulasi_waktu', $pengaturan->simulasi_waktu?->format('Y-m-d\TH:i')) }}" />
                        </div>
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white font-black font-lexend text-xs uppercase tracking-wider shadow-lg shadow-amber-500/20 transition-all duration-300 transform active:scale-95">
                            Aktifkan Simulasi
                        </button>
                    </form>

                    <form action="{{ route('pengaturan.simulasi') }}" method="POST">
                        @csrf @method('PUT')
                        <input type="hidden" name="simulasi_waktu" value="">
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 hover:bg-amber-200 dark:hover:bg-amber-800/60 font-black font-lexend text-xs uppercase tracking-wider transition-colors duration-200 border border-amber-200/50 dark:border-amber-800/50">
                            Reset ke Waktu Asli
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Bento Card: Waktu Absensi --}}
        <div class="bento-card rounded-[2.5rem] p-6 sm:p-8 shadow-xl relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 text-[100px] font-black text-slate-900/[0.02] dark:text-white/[0.015] font-lexend pointer-events-none tracking-tighter leading-none select-none">WAKTU</div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white flex items-center justify-center shadow-lg shadow-indigo-500/30">
                        <x-icon name="clock" class="w-5 h-5 stroke-[2.5]" />
                    </div>
                    <div>
                        <h3 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 tracking-tight">Aturan Waktu Absensi</h3>
                    </div>
                </div>

                <form action="{{ route('pengaturan.update') }}" method="POST" class="space-y-6">
                    @csrf @method('PUT')
                    
                    <div>
                        <label for="nama_sekolah" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Nama Sekolah</label>
                        <input id="nama_sekolah" name="nama_sekolah" type="text" required
                               class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500/30 backdrop-blur-sm px-4 py-2.5"
                               value="{{ old('nama_sekolah', $pengaturan->nama_sekolah) }}" />
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div>
                            <label for="jam_masuk" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Jam Masuk</label>
                            <input id="jam_masuk" name="jam_masuk" type="time" required
                                   class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500/30 backdrop-blur-sm px-4 py-2.5"
                                   value="{{ old('jam_masuk', \Illuminate\Support\Str::of($pengaturan->jam_masuk)->substr(0,5)) }}" />
                        </div>
                        <div>
                            <label for="batas_terlambat" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Batas Terlambat</label>
                            <input id="batas_terlambat" name="batas_terlambat" type="time" required
                                   class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500/30 backdrop-blur-sm px-4 py-2.5"
                                   value="{{ old('batas_terlambat', \Illuminate\Support\Str::of($pengaturan->batas_terlambat)->substr(0,5)) }}" />
                        </div>
                        <div>
                            <label for="mulai_pulang" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Mulai Jam Pulang</label>
                            <input id="mulai_pulang" name="mulai_pulang" type="time" required
                                   class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500/30 backdrop-blur-sm px-4 py-2.5"
                                   value="{{ old('mulai_pulang', \Illuminate\Support\Str::of($pengaturan->mulai_pulang)->substr(0,5)) }}" />
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3 p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700">
                        <x-icon name="information-circle" class="w-5 h-5 text-indigo-500 shrink-0 mt-0.5" />
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-jakarta leading-relaxed">
                            Siswa yang absen setelah <strong>Batas Terlambat</strong> otomatis berstatus <span class="font-bold text-amber-500">terlambat</span>.
                            Scan wajah kedua di hari yang sama baru dihitung sebagai <span class="font-bold text-indigo-500">absen pulang</span> setelah <strong>Mulai Jam Pulang</strong>.
                        </p>
                    </div>
                    
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-black font-lexend text-xs uppercase tracking-wider shadow-lg shadow-indigo-500/20 transition-all duration-300 transform active:scale-95">
                            <x-icon name="save" class="w-4 h-4 stroke-[2.5]" /> Simpan Waktu
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Bento Card: Lokasi GPS --}}
        <div class="bento-card rounded-[2.5rem] p-6 sm:p-8 shadow-xl relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 text-[100px] font-black text-slate-900/[0.02] dark:text-white/[0.015] font-lexend pointer-events-none tracking-tighter leading-none select-none">LOKASI</div>
            
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-lg shadow-emerald-500/30">
                            <x-icon name="location-marker" class="w-5 h-5 stroke-[2.5]" />
                        </div>
                        <div>
                            <h3 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 tracking-tight">Verifikasi Lokasi Absen (GPS)</h3>
                        </div>
                    </div>
                    
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full font-lexend font-bold text-[10px] uppercase tracking-widest shadow-sm
                                 {{ $pengaturan->lokasiAktif() ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-400' }}">
                        <x-icon :name="$pengaturan->lokasiAktif() ? 'check' : 'x-circle'" class="w-3.5 h-3.5 stroke-[2.5]" />
                        {{ $pengaturan->lokasiAktif() ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
                
                <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 font-jakarta mb-5">
                    Kalau diisi, siswa harus berada dalam radius ini dari titik sekolah saat absen mandiri lewat <code>/portal/absen</code>. Kosongkan / nonaktifkan untuk kembali seperti semula.
                </p>

                <form id="form-lokasi" action="{{ route('pengaturan.lokasi') }}" method="POST" class="space-y-6">
                    @csrf @method('PUT')

                    <div id="map-lokasi" class="w-full h-80 rounded-2xl border-2 border-slate-200 dark:border-slate-700 shadow-inner overflow-hidden"
                         data-lat="{{ $pengaturan->lokasi_lat }}" data-lng="{{ $pengaturan->lokasi_lng }}"
                         data-radius="{{ $pengaturan->lokasi_radius_meter }}"></div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-jakarta text-center">
                        Klik peta atau geser penanda untuk mengatur titik sekolah — kolom di bawah ikut terisi otomatis.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div>
                            <label for="lokasi_lat" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Latitude</label>
                            <input id="lokasi_lat" name="lokasi_lat" type="text" inputmode="decimal" 
                                   class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500/30 backdrop-blur-sm px-4 py-2.5"
                                   value="{{ old('lokasi_lat', $pengaturan->lokasi_lat) }}" />
                        </div>
                        <div>
                            <label for="lokasi_lng" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Longitude</label>
                            <input id="lokasi_lng" name="lokasi_lng" type="text" inputmode="decimal" 
                                   class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500/30 backdrop-blur-sm px-4 py-2.5"
                                   value="{{ old('lokasi_lng', $pengaturan->lokasi_lng) }}" />
                        </div>
                        <div>
                            <label for="lokasi_radius_meter" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Radius (meter)</label>
                            <input id="lokasi_radius_meter" name="lokasi_radius_meter" type="number" min="10" max="5000" 
                                   class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500/30 backdrop-blur-sm px-4 py-2.5"
                                   value="{{ old('lokasi_radius_meter', $pengaturan->lokasi_radius_meter) }}" />
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap justify-between items-center gap-4 pt-2 border-t border-slate-200/50 dark:border-slate-700/50">
                        <div class="flex gap-4">
                            <button type="button" id="btn-lokasi-sekarang" class="text-xs font-bold font-lexend text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 transition-colors duration-200 uppercase tracking-widest flex items-center gap-1">
                                <x-icon name="location-marker" class="w-3.5 h-3.5 stroke-[2.5]" /> Gunakan Lokasi Saat Ini
                            </button>
                        </div>
                        <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-black font-lexend text-xs uppercase tracking-wider shadow-lg shadow-emerald-500/20 transition-all duration-300 transform active:scale-95">
                            <x-icon name="save" class="w-4 h-4 stroke-[2.5]" /> Simpan Lokasi
                        </button>
                    </div>
                </form>

                @if ($pengaturan->lokasiAktif())
                    <div class="mt-4 pt-4">
                        <x-confirm-form :action="route('pengaturan.lokasi')" method="PUT"
                                         title="Nonaktifkan verifikasi lokasi?"
                                         message="Titik sekolah, radius, dan pengecekan lokasi absen akan dihapus."
                                         confirm-label="Nonaktifkan"
                                         trigger-class="text-[10px] font-black font-lexend text-rose-500 hover:text-rose-400 transition-colors duration-200 uppercase tracking-widest flex items-center gap-1">
                            <x-slot:fields>
                                <input type="hidden" name="lokasi_lat" value="">
                                <input type="hidden" name="lokasi_lng" value="">
                                <input type="hidden" name="lokasi_radius_meter" value="">
                            </x-slot:fields>
                            <x-icon name="x-circle" class="w-3.5 h-3.5 stroke-[2.5]" /> Nonaktifkan Verifikasi
                        </x-confirm-form>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @vite('resources/js/pengaturan-lokasi.js')
</x-app-layout>
