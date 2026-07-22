<x-siswa-layout>
    @php
        $jumlahSampel = $siswa->faceDescriptors->count();
        $targetSampel = 5;
        $persenSampel = min(100, round($jumlahSampel / $targetSampel * 100));
    @endphp

    <div class="space-y-6">
        <div class="bento-card rounded-[2.5rem] shadow-xl p-6 sm:p-10 relative overflow-hidden group">
            
            <!-- Massive Watermark Text Background -->
            <div class="absolute -right-8 -top-4 text-[120px] sm:text-[160px] font-black text-slate-900/[0.03] dark:text-white/[0.02] font-lexend pointer-events-none tracking-tighter leading-none select-none transition-transform duration-700 group-hover:scale-105">
                FACE
            </div>
            
            <div class="relative z-10 flex flex-col space-y-8">
                <!-- Header Section -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-200/50 dark:border-slate-700/50">
                    <div>
                        <span class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta block mb-1">Data Biometrik</span>
                        <h1 class="font-outfit font-black text-2xl sm:text-3xl text-transparent bg-clip-text bg-gradient-to-r from-slate-900 to-slate-600 dark:from-white dark:to-slate-400 tracking-tight">{{ $siswa->nama }}</h1>
                    </div>
                    @if ($siswa->faceDescriptors->isNotEmpty())
                        <span class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-full text-xs font-black font-lexend bg-emerald-500 text-white border border-emerald-400 shadow-md shadow-emerald-500/30 uppercase tracking-wider">
                            <x-icon name="check-circle" class="w-4 h-4 stroke-[3]" /> Wajah Terdaftar
                        </span>
                    @else
                        <span class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-full text-xs font-black font-lexend bg-rose-50 text-rose-600 border border-rose-200 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20 uppercase tracking-wider shadow-sm">
                            <span class="relative flex h-2.5 w-2.5 mr-1">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-rose-500"></span>
                            </span>
                            Belum Terdaftar
                        </span>
                    @endif
                </div>

                <!-- Progress Bar Section -->
                <div class="bg-white/50 dark:bg-slate-900/40 rounded-2xl p-5 border border-white/60 dark:border-slate-700/50 shadow-inner backdrop-blur-sm">
                    <div class="flex justify-between items-end mb-3">
                        <span class="text-[11px] font-black text-slate-500 dark:text-slate-450 uppercase tracking-widest font-jakarta">Progres Pengambilan Sampel</span>
                        <span class="font-lexend font-black text-2xl text-indigo-600 dark:text-indigo-400 leading-none tabular-nums tracking-tight">{{ $jumlahSampel }}<span class="text-slate-400 dark:text-slate-600 text-lg">/{{ $targetSampel }}</span></span>
                    </div>
                    <div class="w-full bg-slate-200/80 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden shadow-inner">
                        <div @class([
                                 'h-full rounded-full transition-all duration-1000 shadow-[0_0_10px_rgba(0,0,0,0.2)]',
                                 'bg-gradient-to-r from-emerald-400 to-emerald-500 shadow-emerald-500/50 glow-green' => $jumlahSampel >= $targetSampel,
                                 'bg-gradient-to-r from-indigo-400 to-indigo-600 shadow-indigo-500/50' => $jumlahSampel < $targetSampel,
                             ])
                             style="width: {{ $persenSampel }}%"></div>
                    </div>
                    @if ($jumlahSampel > 0 && $jumlahSampel < $targetSampel)
                        <p class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 mt-3 font-jakarta">Dibutuhkan <strong class="font-black">{{ $targetSampel - $jumlahSampel }} sampel lagi</strong> untuk performa AI optimal.</p>
                    @endif
                </div>

                <!-- Face Samples Grid -->
                @if ($siswa->faceDescriptors->isNotEmpty())
                    <div class="space-y-4">
                        <span class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta block">Koleksi Sampel Aktif</span>
                        <div class="grid grid-cols-5 gap-3">
                            @foreach ($siswa->faceDescriptors as $index => $fd)
                                <div class="aspect-square rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-600 dark:from-indigo-600 dark:to-indigo-800 flex flex-col items-center justify-center relative overflow-hidden group shadow-lg shadow-indigo-500/20 transform transition-transform hover:scale-105 border border-indigo-400/50">
                                    <!-- Inner tech ring -->
                                    <div class="absolute inset-2 border border-white/20 rounded-xl"></div>
                                    <x-icon name="camera" class="w-6 h-6 text-white stroke-[2] drop-shadow-md relative z-10" />
                                    <span class="text-[10px] font-black text-indigo-100 mt-1.5 font-lexend relative z-10 drop-shadow-sm">#{{ $index + 1 }}</span>
                                </div>
                            @endforeach
                            
                            <!-- Empty slots to reach target -->
                            @for ($i = $jumlahSampel; $i < $targetSampel; $i++)
                                <div class="aspect-square rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-700 flex flex-col items-center justify-center text-slate-400 dark:text-slate-600 bg-slate-50/50 dark:bg-slate-900/20 backdrop-blur-sm">
                                    <span class="text-xs font-black font-lexend opacity-50">#{{ $i + 1 }}</span>
                                </div>
                            @endfor
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 mt-3 font-jakarta flex items-center gap-1.5">
                            <x-icon name="clock" class="w-3.5 h-3.5" /> Terakhir diperbarui: {{ $siswa->faceDescriptors->max('created_at')->format('d M Y, H:i') }}
                        </p>
                    </div>
                @endif

                <!-- Action Button Section -->
                <div class="pt-4">
                    <a href="{{ route('siswa.enroll.create') }}"
                       class="relative flex items-center justify-center gap-2 w-full group overflow-hidden bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white py-4 rounded-2xl font-black font-lexend shadow-xl shadow-indigo-500/30 transition-all duration-300 transform active:scale-95 border border-white/20 uppercase tracking-widest text-sm">
                        <!-- Pulsing background effect inside button -->
                        <div class="absolute inset-0 bg-white/20 blur-md opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <x-icon name="scan-face" class="w-6 h-6 relative z-10 stroke-[2.5]" />
                        <span class="relative z-10 drop-shadow-sm">{{ $siswa->faceDescriptors->isNotEmpty() ? 'Pembaruan Data Wajah' : 'Mulai Perekaman Wajah' }}</span>
                    </a>
                </div>

                <div class="text-center pt-2">
                    <a href="{{ route('siswa.dashboard') }}" class="inline-flex items-center gap-1.5 text-sm font-black font-lexend text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 bg-slate-100 dark:bg-slate-800 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 px-5 py-2.5 rounded-xl transition-all duration-300 group">
                        <x-icon name="arrow-left" class="w-4 h-4 stroke-[2.5] group-hover:-translate-x-1 transition-transform" /> Kembali
                    </a>
                </div>
            </div>
        </div>

        <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 text-center leading-relaxed max-w-sm mx-auto font-jakarta uppercase tracking-wider">
            Sistem biometrik memerlukan 5 sampel variasi wajah untuk akurasi optimal saat pencahayaan minim.
        </p>
    </div>
</x-siswa-layout>
