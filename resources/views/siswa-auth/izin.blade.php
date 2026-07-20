<x-siswa-layout>
    @php
        $badgeMap = [
            'menunggu' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border border-amber-200/30',
            'disetujui' => 'bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400 border border-green-200/30',
            'ditolak' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400 border border-rose-200/30',
        ];
        $labelMap = [
            'menunggu' => 'Menunggu Persetujuan',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
        ];
        $tampilkanForm = ! $pengajuanHariIni || $pengajuanHariIni->status === 'ditolak';
    @endphp

    <div class="space-y-6">
        <div class="glass-card rounded-2xl shadow-sm p-6 border border-slate-200/50 dark:border-slate-800/55 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <h1 class="font-outfit font-bold text-lg text-slate-800 dark:text-slate-50">Pengajuan Izin / Sakit</h1>
                <x-icon name="alert-circle" class="w-5 h-5 text-indigo-500" />
            </div>

            <!-- Today's Request Status -->
            @if ($pengajuanHariIni)
                <div class="bg-slate-50/50 dark:bg-slate-900/30 rounded-xl p-4 border border-slate-100 dark:border-slate-800/50 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold text-slate-450 dark:text-slate-500 uppercase tracking-wider block">Pengajuan Hari Ini</span>
                            <span class="font-bold text-slate-805 dark:text-slate-100 font-outfit text-sm capitalize">{{ $pengajuanHariIni->jenis }}</span>
                        </div>
                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-bold {{ $badgeMap[$pengajuanHariIni->status] }}">
                            {{ $labelMap[$pengajuanHariIni->status] }}
                        </span>
                    </div>
                    
                    <p class="text-xs font-medium text-slate-600 dark:text-slate-450 mt-1 leading-relaxed bg-white dark:bg-slate-950/40 p-2.5 rounded-lg border border-slate-100 dark:border-slate-850">{{ $pengajuanHariIni->keterangan }}</p>

                    @if ($pengajuanHariIni->status === 'ditolak' && $pengajuanHariIni->catatan_admin)
                        <div class="flex items-start gap-2 bg-rose-50 dark:bg-rose-950/25 border border-rose-200/50 dark:border-rose-900/35 rounded-xl p-3 text-xs text-rose-800 dark:text-rose-400 mt-2">
                            <x-icon name="alert-circle" class="w-4 h-4 shrink-0 mt-0.5 text-rose-600 dark:text-rose-450" />
                            <div>
                                <span class="font-bold block mb-0.5">Catatan Penolakan:</span>
                                {{ $pengajuanHariIni->catatan_admin }}
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Request Submission Form -->
            @if ($tampilkanForm)
                @if ($pengajuanHariIni)
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Pengajuan sebelumnya ditolak. Anda dapat mengajukan kembali dengan melengkapi formulir di bawah ini.</p>
                @endif

                <form method="POST" action="{{ route('siswa.izin.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="jenis" value="Jenis Pengajuan" class="font-semibold text-slate-700 dark:text-slate-350" />
                        <select id="jenis" name="jenis" class="mt-1.5 block w-full bg-slate-50/50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-800 rounded-xl focus:border-indigo-500 focus:ring focus:ring-indigo-500/20 py-2.5 text-sm dark:text-slate-100">
                            <option value="izin" @selected(old('jenis') === 'izin')>Izin</option>
                            <option value="sakit" @selected(old('jenis') === 'sakit')>Sakit</option>
                        </select>
                        <x-input-error :messages="$errors->get('jenis')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="keterangan" value="Keterangan / Alasan" class="font-semibold text-slate-700 dark:text-slate-350" />
                        <x-text-input id="keterangan" class="block mt-1.5 w-full rounded-xl border-slate-300 dark:border-slate-700 focus:border-indigo-500 focus:ring focus:ring-indigo-500/20 py-2.5 text-sm" type="text" name="keterangan" :value="old('keterangan')" required placeholder="Tulis alasan izin atau sakit secara singkat" />
                        <x-input-error :messages="$errors->get('keterangan')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="bukti" value="Bukti Surat Keterangan / Foto" class="font-semibold text-slate-700 dark:text-slate-350" />
                        <input id="bukti" type="file" name="bukti" accept="image/*" required
                               class="block mt-1.5 w-full text-xs text-slate-600 dark:text-slate-400 file:mr-3 file:py-2 file:px-3.5 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-950/40 file:text-indigo-650 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/40 border border-slate-200 dark:border-slate-800 p-1.5 rounded-xl bg-slate-50/50 dark:bg-slate-900/50" />
                        <x-input-error :messages="$errors->get('bukti')" class="mt-2" />
                    </div>

                    <button type="submit" class="mt-4 w-full bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white py-3 rounded-xl font-semibold shadow-md shadow-indigo-500/10 hover:shadow-lg hover:shadow-indigo-500/20 transition-all duration-300 transform active:scale-[0.98]">
                        Kirim Pengajuan
                    </button>
                </form>
            @endif

            <div class="text-center pt-2">
                <a href="{{ route('siswa.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                    <x-icon name="arrow-left" class="w-3.5 h-3.5" /> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</x-siswa-layout>
