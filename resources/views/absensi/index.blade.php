<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-outfit font-black text-2xl sm:text-3xl text-transparent bg-clip-text bg-gradient-to-r from-slate-900 via-indigo-900 to-indigo-600 dark:from-white dark:via-indigo-100 dark:to-indigo-400 tracking-tight">Rekap Absensi</h2>
                <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta mt-0.5">Data Kehadiran Harian Siswa</p>
            </div>
            <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black font-lexend bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-500/20 uppercase tracking-wider">
                <x-icon name="calendar" class="w-3.5 h-3.5 stroke-[2.5]" />
                {{ $tanggal->translatedFormat('d F Y') }}
            </span>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6"
         x-data="{ open: false, siswaId: null, siswaNama: '', tanggal: '{{ $tanggal->toDateString() }}' }">

        {{-- Bento Filter Card --}}
        <form method="GET" class="bento-card rounded-[2rem] p-6 shadow-xl relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[70px] font-black text-slate-900/[0.03] dark:text-white/[0.02] font-lexend pointer-events-none tracking-tighter leading-none select-none">FILTER</div>
            <div class="flex flex-wrap gap-4 items-end relative z-10">
                <div>
                    <label for="tanggal" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Tanggal</label>
                    <input id="tanggal" name="tanggal" type="date" value="{{ $tanggal->toDateString() }}"
                           class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500/30 backdrop-blur-sm px-4 py-2.5" />
                </div>
                @if (auth()->user()->isAdmin() || $kelasList->count() > 1)
                    <div class="min-w-48">
                        <label for="kelas_id" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Kelas</label>
                        <select id="kelas_id" name="kelas_id"
                                class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500/30 backdrop-blur-sm px-4 py-2.5">
                            <option value="">Semua Kelas</option>
                            @foreach ($kelasList as $k)
                                <option value="{{ $k->id }}" @selected($kelasId == $k->id)>{{ $k->nama_kelas }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif ($kelasList->isNotEmpty())
                    <div>
                        <span class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Kelas</span>
                        <span class="inline-flex items-center px-4 py-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-lexend font-bold text-sm border border-indigo-200/50 dark:border-indigo-800/50">{{ $kelasList->first()->nama_kelas }}</span>
                    </div>
                @endif
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-black font-lexend text-xs uppercase tracking-wider shadow-lg shadow-indigo-500/20 transition-all duration-300 transform active:scale-95">
                    <x-icon name="search" class="w-4 h-4 stroke-[2.5]" /> Tampilkan
                </button>
            </div>
        </form>

        {{-- Holiday Banner --}}
        @if ($isLibur)
            <div class="bento-card rounded-[2rem] p-6 border-indigo-200/50 dark:border-indigo-800/40 relative overflow-hidden bg-gradient-to-r from-indigo-500/10 via-purple-500/10 to-indigo-500/5 backdrop-blur-md">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-indigo-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-indigo-500/30">
                        <x-icon name="calendar" class="w-6 h-6 stroke-[2.5]" />
                    </div>
                    <div>
                        <span class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 block">Hari Ini Libur Sekolah</span>
                        <p class="text-sm font-semibold text-slate-600 dark:text-slate-400 font-jakarta mt-1">Tanggal ini terdaftar sebagai hari libur — siswa tidak diwajibkan absen, tidak dihitung alpha.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Bento Table Card --}}
        <div class="bento-card rounded-[2.5rem] p-6 sm:p-8 shadow-xl relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 text-[100px] font-black text-slate-900/[0.02] dark:text-white/[0.015] font-lexend pointer-events-none tracking-tighter leading-none select-none">REKAP</div>

            <div class="flex items-center justify-between pb-5 border-b border-slate-200/50 dark:border-slate-700/50 relative z-10 mb-2">
                <div>
                    <h3 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 tracking-tight">Daftar Kehadiran</h3>
                    <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta">{{ $tanggal->translatedFormat('l, d F Y') }}</span>
                </div>
                <span class="inline-flex items-center gap-1.5 text-xs font-black font-lexend text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 px-4 py-2 rounded-xl border border-emerald-200/50 dark:border-emerald-800/50 uppercase tracking-wider">
                    {{ count($rekap) }} Siswa
                </span>
            </div>

            <div class="overflow-x-auto relative z-10">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200/50 dark:border-slate-700/50">
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Nama Siswa</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Kelas</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Masuk</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Pulang</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Status</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Metode</th>
                            <th class="px-5 py-3 text-right text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rekap as $row)
                            @php $a = $row['absensi']; $s = $row['siswa']; @endphp
                            <tr class="border-b border-slate-100/50 dark:border-slate-800/50 hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors duration-200">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white font-black font-lexend text-xs flex items-center justify-center shadow-md">
                                            {{ Illuminate\Support\Str::of($s->nama)->substr(0, 1)->upper() }}
                                        </div>
                                        <span class="font-black font-outfit text-slate-800 dark:text-slate-100">{{ $s->nama }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex px-3 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-lexend font-bold text-xs">{{ $s->kelas->nama_kelas ?? '-' }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-lexend font-black text-sm text-slate-800 dark:text-slate-200">{{ $a ? \Illuminate\Support\Str::of($a->jam_masuk)->substr(0,5) : '—:—' }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-lexend font-black text-sm text-slate-800 dark:text-slate-200">{{ $a && $a->jam_pulang ? \Illuminate\Support\Str::of($a->jam_pulang)->substr(0,5) : '—:—' }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    @php
                                        $statusBg = [
                                            'hadir' => 'bg-emerald-500 text-white shadow-emerald-500/30',
                                            'terlambat' => 'bg-amber-500 text-white shadow-amber-500/30',
                                            'izin' => 'bg-purple-500 text-white shadow-purple-500/30',
                                            'sakit' => 'bg-purple-500 text-white shadow-purple-500/30',
                                            'alpha' => 'bg-rose-500 text-white shadow-rose-500/30',
                                            'libur' => 'bg-slate-500 text-white shadow-slate-500/30',
                                        ];
                                    @endphp
                                    @if ($a)
                                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest shadow-md {{ $statusBg[$a->status] ?? 'bg-slate-500 text-white' }}">{{ $a->status }}</span>
                                    @elseif ($isLibur)
                                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest shadow-md bg-slate-500 text-white">Libur</span>
                                    @else
                                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest shadow-md bg-rose-500 text-white shadow-rose-500/30">Alpha</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if ($a)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-lexend font-bold text-xs">
                                            <x-icon name="{{ $a->metode === 'wajah' ? 'camera' : 'pencil' }}" class="w-3 h-3 stroke-[2.5]" />
                                            {{ ucfirst($a->metode) }}
                                        </span>
                                    @else
                                        <span class="text-slate-300 dark:text-slate-600 font-lexend text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button"
                                                @click="open = true; siswaId = {{ $s->id }}; siswaNama = '{{ addslashes($s->nama) }}'"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-800/40 font-lexend font-bold text-[10px] uppercase tracking-wider border border-indigo-200/50 dark:border-indigo-800/50 transition-all duration-200">
                                            <x-icon name="pencil" class="w-3 h-3 stroke-[2.5]" /> Manual
                                        </button>
                                        @if ($a)
                                            <x-confirm-form :action="route('absensi.destroy', $a)" title="Hapus absensi {{ $s->nama }} pada tanggal ini?"
                                                             message="Status akan kembali kosong."
                                                             trigger-class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-800/40 font-lexend font-bold text-[10px] uppercase tracking-wider border border-rose-200/50 dark:border-rose-800/50 transition-all duration-200">
                                                <x-icon name="trash" class="w-3 h-3 stroke-[2.5]" /> Hapus
                                            </x-confirm-form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center">
                                            <x-icon name="users" class="w-7 h-7 stroke-[1.5]" />
                                        </div>
                                        <span class="text-sm font-semibold text-slate-500 dark:text-slate-400 font-jakarta">Tidak ada siswa untuk filter ini.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Modal Input Manual --}}
        <div x-show="open" x-cloak x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="open = false">
            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                 class="bento-card rounded-[2rem] shadow-2xl w-full max-w-md p-8 relative overflow-hidden">
                <div class="absolute -right-4 -bottom-4 text-[60px] font-black text-slate-900/[0.03] dark:text-white/[0.02] font-lexend pointer-events-none tracking-tighter leading-none select-none">MANUAL</div>

                <div class="flex items-center gap-3 mb-6 relative z-10">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white flex items-center justify-center shadow-lg shadow-indigo-500/30">
                        <x-icon name="pencil" class="w-5 h-5 stroke-[2.5]" />
                    </div>
                    <div>
                        <h3 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100">Absensi Manual</h3>
                        <p class="text-xs font-bold font-jakarta text-indigo-600 dark:text-indigo-400" x-text="siswaNama"></p>
                    </div>
                </div>

                <form action="{{ route('absensi.manual') }}" method="POST" class="space-y-5 relative z-10">
                    @csrf
                    <input type="hidden" name="siswa_id" :value="siswaId">
                    <input type="hidden" name="tanggal" :value="tanggal">
                    <div>
                        <label for="status" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Status</label>
                        <select name="status" id="status"
                                class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500/30 px-4 py-2.5">
                            @foreach (['hadir', 'terlambat', 'izin', 'sakit', 'alpha'] as $st)
                                <option value="{{ $st }}">{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="keterangan" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Keterangan (opsional)</label>
                        <input id="keterangan" name="keterangan" type="text"
                               class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500/30 px-4 py-2.5"
                               placeholder="Tambahkan keterangan..." />
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="open = false"
                                class="px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-lexend font-bold text-xs uppercase tracking-wider transition-all duration-200 border border-slate-200/50 dark:border-slate-700/50">
                            Batal
                        </button>
                        <button type="submit"
                                class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-black font-lexend text-xs uppercase tracking-wider shadow-lg shadow-indigo-500/20 transition-all duration-300 transform active:scale-95">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</x-app-layout>
