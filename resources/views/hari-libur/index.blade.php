<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-outfit font-black text-2xl sm:text-3xl text-transparent bg-clip-text bg-gradient-to-r from-slate-900 via-indigo-900 to-indigo-600 dark:from-white dark:via-indigo-100 dark:to-indigo-400 tracking-tight">Hari Libur</h2>
                <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta mt-0.5">Pengaturan Kalender Sekolah</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 space-y-6">
                {{-- Bento Card: Libur Mingguan Otomatis --}}
                <div class="bento-card rounded-[2rem] p-6 shadow-xl relative overflow-hidden">
                    <div class="absolute -right-4 -bottom-4 text-[70px] font-black text-slate-900/[0.03] dark:text-white/[0.02] font-lexend pointer-events-none tracking-tighter leading-none select-none">MINGGUAN</div>
                    
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white flex items-center justify-center shadow-lg shadow-indigo-500/30">
                                <x-icon name="calendar" class="w-5 h-5 stroke-[2.5]" />
                            </div>
                            <div>
                                <h3 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 tracking-tight">Libur Rutin</h3>
                            </div>
                        </div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 font-jakarta mb-5">
                            Centang hari yang selalu libur setiap minggu (mis. Sabtu & Minggu). Absensi otomatis diblokir setiap hari tersebut.
                        </p>
                        
                        <form action="{{ route('pengaturan.libur-mingguan') }}" method="POST" class="space-y-4">
                            @csrf @method('PUT')
                            @php
                                $liburMingguan = $pengaturan->liburMingguan();
                                $hariOptions = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 0 => 'Minggu'];
                            @endphp
                            <div class="flex flex-col gap-2.5">
                                @foreach ($hariOptions as $value => $label)
                                    <label class="flex items-center gap-3 p-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/20 cursor-pointer transition-colors duration-200">
                                        <input type="checkbox" name="hari_libur_mingguan[]" value="{{ $value }}"
                                               class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                               @checked(in_array($value, $liburMingguan, true)) />
                                        <span class="font-lexend font-bold text-sm text-slate-700 dark:text-slate-300">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <button type="submit" class="w-full inline-flex justify-center items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-black font-lexend text-xs uppercase tracking-wider shadow-lg shadow-indigo-500/20 transition-all duration-300 transform active:scale-95 mt-4">
                                Simpan Libur Mingguan
                            </button>
                        </form>
                    </div>
                </div>
                
                {{-- Bento Card: Tambah Tanggal Libur --}}
                <div class="bento-card rounded-[2rem] p-6 shadow-xl relative overflow-hidden">
                    <div class="absolute -right-4 -bottom-4 text-[70px] font-black text-slate-900/[0.03] dark:text-white/[0.02] font-lexend pointer-events-none tracking-tighter leading-none select-none">TANGGAL</div>
                    
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-lg shadow-emerald-500/30">
                                <x-icon name="plus" class="w-5 h-5 stroke-[2.5]" />
                            </div>
                            <div>
                                <h3 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 tracking-tight">Tambah Libur</h3>
                            </div>
                        </div>
                        <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 font-jakarta mb-5">
                            Tambahkan libur spesifik (mis. Libur Semester, Cuti Bersama, Tanggal Merah). Absensi scan otomatis diblokir pada periode ini.
                        </p>
                        
                        <form action="{{ route('hari-libur.store') }}" method="POST" class="space-y-4" x-data="{ dari: '', sampai: '' }">
                            @csrf
                            <div>
                                <label for="dari" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Dari Tanggal</label>
                                <input id="dari" name="dari" type="date" required x-model="dari" @change="if (!sampai) sampai = dari"
                                       class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500/30 backdrop-blur-sm px-4 py-2.5" />
                            </div>
                            <div>
                                <label for="sampai" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Sampai Tanggal</label>
                                <input id="sampai" name="sampai" type="date" required x-model="sampai"
                                       class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500/30 backdrop-blur-sm px-4 py-2.5" />
                            </div>
                            <div>
                                <label for="keterangan" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Keterangan (opsional)</label>
                                <input id="keterangan" name="keterangan" type="text" placeholder="Misal: Libur Semester"
                                       class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500/30 backdrop-blur-sm px-4 py-2.5" />
                            </div>
                            <button type="submit" class="w-full inline-flex justify-center items-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-black font-lexend text-xs uppercase tracking-wider shadow-lg shadow-emerald-500/20 transition-all duration-300 transform active:scale-95 mt-4">
                                <x-icon name="plus" class="w-4 h-4 stroke-[2.5]" /> Tambah
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                {{-- Bento Card: List Hari Libur --}}
                <div class="bento-card rounded-[2.5rem] p-6 sm:p-8 shadow-xl relative overflow-hidden h-full">
                    <div class="absolute -right-6 -bottom-6 text-[100px] font-black text-slate-900/[0.02] dark:text-white/[0.015] font-lexend pointer-events-none tracking-tighter leading-none select-none">TERDAFTAR</div>
        
                    <div class="flex items-center justify-between pb-5 border-b border-slate-200/50 dark:border-slate-700/50 relative z-10 mb-2">
                        <div>
                            <h3 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 tracking-tight">Daftar Tanggal Libur</h3>
                            <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta">Libur Kustom</span>
                        </div>
                    </div>
        
                    <div class="overflow-x-auto relative z-10">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-200/50 dark:border-slate-700/50">
                                    <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Tanggal</th>
                                    <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Keterangan</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($hariLibur as $libur)
                                    <tr class="border-b border-slate-100/50 dark:border-slate-800/50 hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors duration-200">
                                        <td class="px-5 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-black font-lexend text-xs flex flex-col items-center justify-center border border-slate-200/50 dark:border-slate-700/50">
                                                    <span class="text-xs">{{ $libur->tanggal->format('d') }}</span>
                                                    <span class="text-[9px] uppercase tracking-wider font-jakarta">{{ $libur->tanggal->format('M') }}</span>
                                                </div>
                                                <span class="font-bold font-lexend text-sm text-slate-700 dark:text-slate-300">{{ $libur->tanggal->translatedFormat('d F Y') }}</span>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="font-jakarta font-semibold text-slate-600 dark:text-slate-400">{{ $libur->keterangan ?? '-' }}</span>
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <x-confirm-form :action="route('hari-libur.destroy', $libur)" title="Hapus tanggal libur {{ $libur->tanggal->format('d/m/Y') }}?"
                                                             trigger-class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 transition-colors duration-200">
                                                <x-icon name="trash" class="w-4 h-4 stroke-[2.5]" />
                                            </x-confirm-form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center gap-3">
                                                <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center">
                                                    <x-icon name="calendar" class="w-7 h-7 stroke-[1.5]" />
                                                </div>
                                                <span class="text-sm font-semibold text-slate-500 dark:text-slate-400 font-jakarta">Belum ada tanggal libur terdaftar.</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bento Card: Kalender Tanggal Merah (pilih tersebar, simpan sekaligus) --}}
        <div class="bento-card rounded-[2.5rem] p-6 sm:p-8 shadow-xl relative overflow-hidden"
             x-data="{ selected: [], keteranganBulk: '' }">
            <div class="absolute -right-6 -bottom-6 text-[100px] font-black text-slate-900/[0.02] dark:text-white/[0.015] font-lexend pointer-events-none tracking-tighter leading-none select-none">TANGGAL MERAH</div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-5 border-b border-slate-200/50 dark:border-slate-700/50 mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-orange-600 text-white flex items-center justify-center shadow-lg shadow-rose-500/30">
                            <x-icon name="calendar" class="w-5 h-5 stroke-[2.5]" />
                        </div>
                        <div>
                            <h3 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 tracking-tight">Pilih Tanggal Merah</h3>
                            <p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 font-jakarta">Klik tanggal untuk tandai libur tersebar dalam setahun, lalu simpan sekaligus.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('hari-libur.index', ['tahun' => $tahun - 1]) }}"
                           class="w-9 h-9 inline-flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 transition-colors">
                            <x-icon name="arrow-left" class="w-4 h-4 stroke-[2.5]" />
                        </a>
                        <span class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 w-16 text-center">{{ $tahun }}</span>
                        <a href="{{ route('hari-libur.index', ['tahun' => $tahun + 1]) }}"
                           class="w-9 h-9 inline-flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 transition-colors">
                            <x-icon name="arrow-right" class="w-4 h-4 stroke-[2.5]" />
                        </a>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 mb-5 text-[11px] font-bold font-jakarta text-slate-500 dark:text-slate-400">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-rose-500"></span> Sudah libur</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-slate-300 dark:bg-slate-600"></span> Libur rutin</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded border-2 border-rose-400"></span> Dipilih</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    @for ($bulan = 1; $bulan <= 12; $bulan++)
                        @php
                            $awalBulan = \Illuminate\Support\Carbon::createFromDate($tahun, $bulan, 1);
                            $jumlahHari = $awalBulan->daysInMonth;
                            $offset = $awalBulan->dayOfWeek;
                        @endphp
                        <div class="rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-3">
                            <p class="text-center font-lexend font-black text-xs uppercase tracking-wider text-slate-600 dark:text-slate-300 mb-2">{{ $awalBulan->translatedFormat('F') }}</p>
                            <div class="grid grid-cols-7 gap-1 text-center text-[9px] font-black text-slate-400 mb-1">
                                @foreach (['M', 'S', 'S', 'R', 'K', 'J', 'S'] as $h)
                                    <span>{{ $h }}</span>
                                @endforeach
                            </div>
                            <div class="grid grid-cols-7 gap-1">
                                @for ($i = 0; $i < $offset; $i++)
                                    <span></span>
                                @endfor
                                @for ($tgl = 1; $tgl <= $jumlahHari; $tgl++)
                                    @php
                                        $tanggalObj = \Illuminate\Support\Carbon::createFromDate($tahun, $bulan, $tgl);
                                        $dateStr = $tanggalObj->toDateString();
                                        $sudahLibur = isset($tanggalLiburTahunIni[$dateStr]);
                                        $liburRutin = in_array($tanggalObj->dayOfWeek, $hariLiburMingguan, true);
                                    @endphp
                                    @if ($sudahLibur)
                                        <span class="aspect-square flex items-center justify-center rounded-lg bg-rose-500 text-white text-[10px] font-bold font-lexend" title="Sudah libur">{{ $tgl }}</span>
                                    @elseif ($liburRutin)
                                        <span class="aspect-square flex items-center justify-center rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-400 dark:text-slate-500 text-[10px] font-bold font-lexend" title="Libur rutin">{{ $tgl }}</span>
                                    @else
                                        <label class="aspect-square flex items-center justify-center rounded-lg text-[10px] font-bold font-lexend cursor-pointer border border-transparent hover:bg-rose-50 dark:hover:bg-rose-900/20"
                                               :class="selected.includes('{{ $dateStr }}') ? 'bg-rose-100 dark:bg-rose-900/40 border-rose-400 text-rose-700 dark:text-rose-300' : 'text-slate-600 dark:text-slate-300'">
                                            <input type="checkbox" value="{{ $dateStr }}" x-model="selected" class="sr-only">
                                            {{ $tgl }}
                                        </label>
                                    @endif
                                @endfor
                            </div>
                        </div>
                    @endfor
                </div>

                <div x-show="selected.length > 0" x-cloak
                     x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                     class="mt-5 flex flex-wrap items-center gap-4 rounded-2xl p-4 bg-gradient-to-r from-rose-500/5 via-orange-500/5 to-rose-500/5 border border-rose-200/50 dark:border-rose-800/30">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-rose-500 text-white font-black font-lexend flex items-center justify-center text-sm">
                            <span x-text="selected.length"></span>
                        </div>
                        <span class="font-outfit font-black text-sm text-slate-800 dark:text-slate-100">Tanggal Dipilih</span>
                    </div>
                    <input type="text" x-model="keteranganBulk" placeholder="Keterangan (opsional, berlaku utk semua)"
                           class="flex-1 min-w-[200px] rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-rose-500 focus:ring-rose-500/30 px-4 py-2">
                    <x-confirm-form :action="route('hari-libur.storeBulk')" method="POST"
                                     title="Simpan tanggal libur terpilih?"
                                     message="Semua tanggal yang dipilih akan ditambahkan sebagai hari libur."
                                     confirm-label="Simpan"
                                     trigger-class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-rose-500 to-orange-600 hover:from-rose-400 hover:to-orange-500 text-white font-black font-lexend text-[10px] uppercase tracking-wider shadow-lg shadow-rose-500/20 transition-all duration-300 transform active:scale-95">
                        <x-slot:fields>
                            <template x-for="tgl in selected" :key="tgl">
                                <input type="hidden" name="tanggal[]" :value="tgl">
                            </template>
                            <input type="hidden" name="keterangan" :value="keteranganBulk">
                        </x-slot:fields>
                        <x-icon name="check" class="w-3.5 h-3.5 stroke-[2.5]" /> Simpan
                    </x-confirm-form>
                    <button type="button" @click="selected = []" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 font-lexend font-bold text-xs uppercase tracking-wider transition-colors">Batal</button>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
