<x-siswa-layout>
    @php
        $jumlahSampel = $siswa->faceDescriptors->count();
        $targetSampel = 5;
        $persenSampel = min(100, round($jumlahSampel / $targetSampel * 100));
    @endphp

    <div class="space-y-6">
        <div class="glass-card rounded-2xl shadow-sm p-6 border border-slate-200/50 dark:border-slate-800/55 space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider block">Data Sampel Wajah</span>
                    <h1 class="font-outfit font-bold text-lg text-slate-805 dark:text-slate-50">{{ $siswa->nama }}</h1>
                </div>
                @if ($siswa->faceDescriptors->isNotEmpty())
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-green-50 text-green-700 dark:bg-green-950/20 dark:text-green-400 border border-green-200/30">
                        <x-icon name="check-circle" class="w-3.5 h-3.5" /> Terdaftar
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500 dark:bg-slate-800/40 dark:text-slate-400 border border-slate-200/40 dark:border-slate-700/40">
                        Belum Terdaftar
                    </span>
                @endif
            </div>

            <!-- Progress Bar -->
            <div class="bg-slate-50/50 dark:bg-slate-900/30 rounded-xl p-4 border border-slate-100 dark:border-slate-800/50">
                <div class="flex justify-between text-xs font-bold text-slate-550 dark:text-slate-450 mb-2">
                    <span>Progres Pendaftaran Sampel</span>
                    <span class="font-outfit text-slate-850 dark:text-slate-105">{{ $jumlahSampel }} / {{ $targetSampel }}</span>
                </div>
                <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-3 overflow-hidden shadow-inner border border-slate-300/10 dark:border-slate-700/10">
                    <div @class([
                             'h-full rounded-full transition-all duration-500 shadow-sm',
                             'bg-gradient-to-r from-green-500 to-emerald-500 glow-green' => $jumlahSampel >= $targetSampel,
                             'bg-gradient-to-r from-indigo-500 to-indigo-600' => $jumlahSampel < $targetSampel,
                         ])
                         style="width: {{ $persenSampel }}%"></div>
                </div>
                @if ($jumlahSampel > 0 && $jumlahSampel < $targetSampel)
                    <p class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 mt-2">Daftarkan {{ $targetSampel - $jumlahSampel }} sampel wajah lagi untuk performa optimal.</p>
                @endif
            </div>

            <!-- Face Samples Grid -->
            @if ($siswa->faceDescriptors->isNotEmpty())
                <div class="space-y-3">
                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-550 uppercase tracking-wider block">Daftar Sampel Wajah Aktif</span>
                    <div class="grid grid-cols-5 gap-2.5">
                        @foreach ($siswa->faceDescriptors as $index => $fd)
                            <div class="aspect-square rounded-xl bg-gradient-to-br from-indigo-50 to-indigo-100/50 dark:from-indigo-950/20 dark:to-indigo-900/10 border border-indigo-200/50 dark:border-indigo-800/50 flex flex-col items-center justify-center relative overflow-hidden group shadow-sm">
                                <div class="absolute inset-0 bg-gradient-to-t from-indigo-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                <x-icon name="camera" class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                                <span class="text-[9px] font-bold text-indigo-550 dark:text-indigo-450 mt-1 font-outfit">#{{ $index + 1 }}</span>
                            </div>
                        @endforeach
                        
                        <!-- Empty slots to reach target -->
                        @for ($i = $jumlahSampel; $i < $targetSampel; $i++)
                            <div class="aspect-square rounded-xl border border-dashed border-slate-200 dark:border-slate-800 flex items-center justify-center text-slate-300 dark:text-slate-700 bg-slate-50/50 dark:bg-slate-900/10">
                                <span class="text-xs font-bold font-outfit">#{{ $i + 1 }}</span>
                            </div>
                        @endfor
                    </div>
                    <p class="text-[10px] font-medium text-slate-450 dark:text-slate-500 mt-2">Terakhir diperbarui: {{ $siswa->faceDescriptors->max('created_at')->format('d/m/Y H:i') }} WIB</p>
                </div>
            @endif

            <!-- Action Button -->
            <a href="{{ route('siswa.enroll.create') }}"
               class="flex items-center justify-center gap-2 w-full bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white py-3 rounded-xl font-semibold shadow-md shadow-indigo-500/10 hover:shadow-lg hover:shadow-indigo-500/20 transition-all duration-300 transform active:scale-[0.98]">
                <x-icon name="camera" class="w-5 h-5" />
                <span>{{ $siswa->faceDescriptors->isNotEmpty() ? 'Pbarui Sampel Wajah' : 'Daftar Wajah Sekarang' }}</span>
            </a>

            <div class="text-center pt-2">
                <a href="{{ route('siswa.dashboard') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                    <x-icon name="arrow-left" class="w-3.5 h-3.5" /> Kembali ke Beranda
                </a>
            </div>
        </div>

        <p class="text-[10px] font-semibold text-slate-400 dark:text-slate-500 text-center leading-relaxed max-w-xs mx-auto">
            Sistem memerlukan beberapa sudut pengambilan gambar wajah agar kecocokan wajah saat absensi tetap akurat pada berbagai kondisi cahaya.
        </p>
    </div>
</x-siswa-layout>
