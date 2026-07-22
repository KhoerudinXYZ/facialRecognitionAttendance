<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-outfit font-black text-2xl sm:text-3xl text-transparent bg-clip-text bg-gradient-to-r from-slate-900 via-indigo-900 to-indigo-600 dark:from-white dark:via-indigo-100 dark:to-indigo-400 tracking-tight">Laporan Absensi</h2>
                <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta mt-0.5">Laporan Kehadiran Berdasarkan Periode</p>
            </div>
            <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black font-lexend bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-500/20 uppercase tracking-wider">
                <x-icon name="calendar" class="w-3.5 h-3.5 stroke-[2.5]" />
                {{ $dari->translatedFormat('d M Y') }} - {{ $sampai->translatedFormat('d M Y') }}
            </span>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- Bento Filter Card --}}
        <form method="GET" class="bento-card rounded-[2rem] p-6 shadow-xl relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[70px] font-black text-slate-900/[0.03] dark:text-white/[0.02] font-lexend pointer-events-none tracking-tighter leading-none select-none">FILTER</div>
            <div class="flex flex-wrap gap-4 items-end relative z-10">
                <div>
                    <label for="dari" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Dari Tanggal</label>
                    <input id="dari" name="dari" type="date" value="{{ $dari->toDateString() }}"
                           class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500/30 backdrop-blur-sm px-4 py-2.5" />
                </div>
                <div>
                    <label for="sampai" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Sampai Tanggal</label>
                    <input id="sampai" name="sampai" type="date" value="{{ $sampai->toDateString() }}"
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

                @php $params = ['dari' => $dari->toDateString(), 'sampai' => $sampai->toDateString(), 'kelas_id' => $kelasId]; @endphp
                <div class="flex gap-2">
                    <a href="{{ route('laporan.excel', $params) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black font-lexend text-xs uppercase tracking-wider shadow-lg shadow-emerald-500/20 transition-all duration-300 transform active:scale-95">
                        <x-icon name="download" class="w-4 h-4 stroke-[2.5]" /> Excel
                    </a>
                    <a href="{{ route('laporan.pdf', $params) }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-black font-lexend text-xs uppercase tracking-wider shadow-lg shadow-rose-500/20 transition-all duration-300 transform active:scale-95">
                        <x-icon name="download" class="w-4 h-4 stroke-[2.5]" /> PDF
                    </a>
                </div>

                <div class="w-full flex flex-wrap gap-2 pt-3 border-t border-slate-200/50 dark:border-slate-700/50 mt-2">
                    @foreach ($presets as $label => [$presetDari, $presetSampai])
                        <a href="{{ route('laporan.index', ['dari' => $presetDari->toDateString(), 'sampai' => $presetSampai->toDateString(), 'kelas_id' => $kelasId]) }}"
                           class="text-[10px] font-black uppercase tracking-widest px-4 py-1.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors duration-200 border border-slate-200/50 dark:border-slate-700/50">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </form>

        {{-- Bento Table Card --}}
        <div class="bento-card rounded-[2.5rem] p-6 sm:p-8 shadow-xl relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 text-[100px] font-black text-slate-900/[0.02] dark:text-white/[0.015] font-lexend pointer-events-none tracking-tighter leading-none select-none">DATA</div>

            <div class="flex items-center justify-between pb-5 border-b border-slate-200/50 dark:border-slate-700/50 relative z-10 mb-2">
                <div>
                    <h3 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 tracking-tight">Data Laporan Absensi</h3>
                    <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta">Total {{ $data->count() }} baris</span>
                </div>
            </div>

            <div class="overflow-x-auto relative z-10">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200/50 dark:border-slate-700/50">
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Tanggal</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Siswa</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Kelas</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Jam Masuk</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Jam Pulang</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Status</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $a)
                            <tr class="border-b border-slate-100/50 dark:border-slate-800/50 hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors duration-200">
                                <td class="px-5 py-4">
                                    <span class="font-lexend font-bold text-xs text-slate-700 dark:text-slate-300">{{ $a->tanggal->format('d/m/Y') }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-black font-outfit text-slate-800 dark:text-slate-100">{{ $a->siswa->nama ?? '-' }}</span>
                                        <span class="text-[10px] font-lexend text-slate-400 dark:text-slate-500">{{ $a->siswa->nis ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col">
                                        <span class="inline-flex px-3 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-lexend font-bold text-xs w-max">{{ $a->kelas->nama_kelas ?? '-' }}</span>
                                        @if ($a->kelas?->waliKelas)
                                            <span class="text-[10px] font-jakarta font-semibold text-slate-400 dark:text-slate-500 mt-1">{{ $a->kelas->waliKelas->name }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-lexend font-black text-sm text-slate-800 dark:text-slate-200">{{ \Illuminate\Support\Str::of($a->jam_masuk)->substr(0,5) ?: '—:—' }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-lexend font-black text-sm text-slate-800 dark:text-slate-200">{{ \Illuminate\Support\Str::of($a->jam_pulang)->substr(0,5) ?: '—:—' }}</span>
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
                                    <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest shadow-md {{ $statusBg[$a->status] ?? 'bg-slate-500 text-white' }}">
                                        {{ $a->status }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-xs font-jakarta font-semibold text-slate-500 dark:text-slate-400">
                                    {{ $a->keterangan ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center">
                                            <x-icon name="clipboard" class="w-7 h-7 stroke-[1.5]" />
                                        </div>
                                        <span class="text-sm font-semibold text-slate-500 dark:text-slate-400 font-jakarta">Tidak ada data pada periode ini.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 flex flex-wrap items-center gap-3 relative z-10 pt-4 border-t border-slate-200/50 dark:border-slate-700/50">
                @if ($liburDalamPeriode > 0)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-lexend font-bold text-[10px] uppercase tracking-wider">
                        <x-icon name="calendar" class="w-3.5 h-3.5 stroke-[2.5]" />
                        Termasuk {{ $liburDalamPeriode }} hari libur
                    </span>
                @endif
                @if ($barisTanpaWali > 0)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 font-lexend font-bold text-[10px] uppercase tracking-wider">
                        <x-icon name="x-circle" class="w-3.5 h-3.5 stroke-[2.5]" />
                        {{ $barisTanpaWali }} baris tanpa wali kelas
                    </span>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
