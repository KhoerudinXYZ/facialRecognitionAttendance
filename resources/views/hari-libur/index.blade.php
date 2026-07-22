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

    </div>
</x-app-layout>
