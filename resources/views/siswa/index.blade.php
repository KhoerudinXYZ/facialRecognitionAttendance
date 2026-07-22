<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-outfit font-black text-2xl sm:text-3xl text-transparent bg-clip-text bg-gradient-to-r from-slate-900 via-indigo-900 to-indigo-600 dark:from-white dark:via-indigo-100 dark:to-indigo-400 tracking-tight">Data Siswa</h2>
                <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta mt-0.5">Database Siswa Terdaftar</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('siswa.import.form') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-lexend font-bold text-[10px] uppercase tracking-wider transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                    <x-icon name="upload" class="w-4 h-4 stroke-[2.5]" /> Import Excel
                </a>
                <a href="{{ route('siswa.create') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-black font-lexend text-[10px] uppercase tracking-wider shadow-lg shadow-indigo-500/20 transition-all duration-300 transform active:scale-95">
                    <x-icon name="plus" class="w-4 h-4 stroke-[3]" /> Tambah Siswa
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6"
         x-data="{ selected: [], targetKelasId: '', pageIds: @json($siswa->getCollection()->pluck('id')).map(String) }">

        {{-- Bento Filter Card --}}
        <form method="GET" class="bento-card rounded-[2rem] p-6 shadow-xl relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[70px] font-black text-slate-900/[0.03] dark:text-white/[0.02] font-lexend pointer-events-none tracking-tighter leading-none select-none">CARI</div>
            <div class="flex flex-wrap gap-4 items-end relative z-10">
                <div class="flex-1 min-w-48">
                    <label for="q" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Cari (Nama / NIS)</label>
                    <input id="q" name="q" type="text" value="{{ request('q') }}" placeholder="Ketik nama atau NIS..."
                           class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500/30 backdrop-blur-sm px-4 py-2.5" />
                </div>
                @if (auth()->user()->isAdmin() || $kelasList->count() > 1)
                    <div class="min-w-48">
                        <label for="kelas_id" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Kelas</label>
                        <select id="kelas_id" name="kelas_id"
                                class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500/30 backdrop-blur-sm px-4 py-2.5">
                            <option value="">Semua Kelas</option>
                            @foreach ($kelasList as $k)
                                <option value="{{ $k->id }}" @selected(request('kelas_id') == $k->id)>{{ $k->nama_kelas }}</option>
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
                    <x-icon name="search" class="w-4 h-4 stroke-[2.5]" /> Filter
                </button>
                <a href="{{ route('siswa.index') }}" class="px-4 py-2.5 text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 font-lexend font-bold text-xs uppercase tracking-wider transition-colors">Reset</a>
            </div>
        </form>

        {{-- Bulk Move Bar --}}
        <div x-show="selected.length > 0" x-cloak
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             class="bento-card rounded-[2rem] p-5 shadow-xl border-indigo-200/50 dark:border-indigo-800/30 relative overflow-hidden bg-gradient-to-r from-indigo-500/5 via-purple-500/5 to-indigo-500/5">
            <div class="flex flex-wrap items-center gap-4 relative z-10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500 text-white font-black font-lexend flex items-center justify-center shadow-lg shadow-indigo-500/30 text-sm">
                        <span x-text="selected.length"></span>
                    </div>
                    <span class="font-outfit font-black text-sm text-slate-800 dark:text-slate-100">Siswa Dipilih</span>
                </div>
                <select x-model="targetKelasId"
                        class="rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500/30 px-4 py-2.5">
                    <option value="">Pindahkan ke kelas...</option>
                    @foreach ($kelasList as $k)
                        <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
                <template x-if="targetKelasId">
                    <x-confirm-form :action="route('siswa.bulkMove')" method="PUT" title="Pindahkan siswa terpilih?"
                                     message="Siswa yang dipilih akan dipindahkan ke kelas tujuan."
                                     confirm-label="Pindahkan"
                                     trigger-class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-black font-lexend text-[10px] uppercase tracking-wider shadow-lg shadow-indigo-500/20 transition-all duration-300 transform active:scale-95">
                        <x-slot:fields>
                            <template x-for="id in selected" :key="id">
                                <input type="hidden" name="siswa_ids[]" :value="id">
                            </template>
                            <input type="hidden" name="kelas_id" :value="targetKelasId">
                        </x-slot:fields>
                        <x-icon name="users" class="w-3.5 h-3.5 stroke-[2.5]" /> Pindahkan
                    </x-confirm-form>
                </template>
                <button type="button" @click="selected = []" class="text-sm text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 font-lexend font-bold text-xs uppercase tracking-wider transition-colors">Batal</button>
            </div>
        </div>

        {{-- Bento Table Card --}}
        <div class="bento-card rounded-[2.5rem] p-6 sm:p-8 shadow-xl relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 text-[100px] font-black text-slate-900/[0.02] dark:text-white/[0.015] font-lexend pointer-events-none tracking-tighter leading-none select-none">SISWA</div>

            <div class="overflow-x-auto relative z-10">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200/50 dark:border-slate-700/50">
                            <th class="px-4 py-3 w-8">
                                <div class="flex items-center justify-center">
                                    <input type="checkbox"
                                           class="w-4 h-4 rounded-md border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500/30 dark:bg-slate-800"
                                           :checked="pageIds.length > 0 && selected.length === pageIds.length"
                                           @change="selected = $event.target.checked ? [...pageIds] : []">
                                </div>
                            </th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">NIS</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Nama Siswa</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">L/P</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Kelas</th>
                            <th class="px-5 py-3 text-center text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Face ID</th>
                            <th class="px-5 py-3 text-center text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Hari Ini</th>
                            <th class="px-5 py-3 text-right text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($siswa as $s)
                            <tr class="border-b border-slate-100/50 dark:border-slate-800/50 hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors duration-200">
                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-center">
                                        <input type="checkbox" value="{{ $s->id }}" x-model="selected"
                                               class="w-4 h-4 rounded-md border-slate-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500/30 dark:bg-slate-800">
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-lexend font-bold text-slate-500 dark:text-slate-400 text-xs">{{ $s->nis }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white font-black font-lexend text-xs flex items-center justify-center shadow-md shrink-0">
                                            {{ Illuminate\Support\Str::of($s->nama)->substr(0, 1)->upper() }}
                                        </div>
                                        <a href="{{ route('siswa.show', $s) }}" class="font-outfit font-black text-slate-800 dark:text-slate-100 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">{{ $s->nama }}</a>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-lexend font-bold text-[10px] uppercase">{{ $s->jenis_kelamin }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div>
                                        <span class="inline-flex px-3 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-lexend font-bold text-xs border border-indigo-200/50 dark:border-indigo-800/50">{{ $s->kelas->nama_kelas ?? '-' }}</span>
                                        @if ($s->kelas?->waliKelas)
                                            <div class="text-[10px] font-bold font-jakarta text-slate-400 dark:text-slate-500 mt-1 pl-1">{{ $s->kelas->waliKelas->name }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    @if ($s->face_descriptors_count > 0)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black font-lexend bg-emerald-500 text-white uppercase tracking-wider shadow-md shadow-emerald-500/30">
                                            <x-icon name="camera" class="w-3 h-3 stroke-[2.5]" /> {{ $s->face_descriptors_count }}
                                        </span>
                                    @else
                                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend bg-slate-200 dark:bg-slate-700 text-slate-500 dark:text-slate-400 uppercase tracking-wider">Belum</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-center">
                                    @php
                                        $statusBg = [
                                            'hadir' => 'bg-emerald-500 text-white shadow-emerald-500/30',
                                            'terlambat' => 'bg-amber-500 text-white shadow-amber-500/30',
                                            'izin' => 'bg-purple-500 text-white shadow-purple-500/30',
                                            'sakit' => 'bg-purple-500 text-white shadow-purple-500/30',
                                            'alpha' => 'bg-rose-500 text-white shadow-rose-500/30',
                                        ];
                                    @endphp
                                    @if ($isLibur)
                                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend bg-slate-500 text-white uppercase tracking-widest shadow-md">Libur</span>
                                    @elseif ($s->absensi->first())
                                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest shadow-md {{ $statusBg[$s->absensi->first()->status] ?? 'bg-slate-500 text-white' }}">{{ $s->absensi->first()->status }}</span>
                                    @else
                                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend bg-rose-500 text-white uppercase tracking-widest shadow-md shadow-rose-500/30">Alpha</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap" x-data="{ target: '' }">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('siswa.enroll', $s) }}"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-800/40 font-lexend font-bold text-[10px] uppercase tracking-wider border border-emerald-200/50 dark:border-emerald-800/50 transition-all duration-200">
                                            <x-icon name="camera" class="w-3 h-3 stroke-[2.5]" /> Wajah
                                        </a>
                                        <a href="{{ route('siswa.edit', $s) }}"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-800/40 font-lexend font-bold text-[10px] uppercase tracking-wider border border-indigo-200/50 dark:border-indigo-800/50 transition-all duration-200">
                                            <x-icon name="pencil" class="w-3 h-3 stroke-[2.5]" /> Edit
                                        </a>
                                        <select x-model="target"
                                                class="inline-block w-auto text-[10px] border-slate-200 dark:border-slate-700 rounded-xl bg-white/50 dark:bg-slate-900/40 text-slate-600 dark:text-slate-300 font-lexend font-bold py-1.5 pl-3 pr-7 focus:border-indigo-500 focus:ring-indigo-500/30">
                                            <option value="">Pindah...</option>
                                            @foreach ($kelasList as $k)
                                                @if ($k->id !== $s->kelas_id)
                                                    <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        <template x-if="target">
                                            <x-confirm-form :action="route('siswa.bulkMove')" method="PUT"
                                                             title="Pindahkan {{ $s->nama }}?"
                                                             message="Siswa ini akan dipindahkan ke kelas terpilih."
                                                             confirm-label="Pindahkan"
                                                             trigger-class="inline-flex items-center px-2.5 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-800/40 font-lexend font-bold text-[10px] uppercase tracking-wider border border-indigo-200/50 dark:border-indigo-800/50 transition-all duration-200">
                                                <x-slot:fields>
                                                    <input type="hidden" name="siswa_ids[]" value="{{ $s->id }}">
                                                    <input type="hidden" name="kelas_id" :value="target">
                                                </x-slot:fields>
                                                Go
                                            </x-confirm-form>
                                        </template>
                                        <x-confirm-form :action="route('siswa.destroy', $s)" title="Hapus siswa ini?"
                                                         trigger-class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-800/40 font-lexend font-bold text-[10px] uppercase tracking-wider border border-rose-200/50 dark:border-rose-800/50 transition-all duration-200">
                                            <x-icon name="trash" class="w-3 h-3 stroke-[2.5]" />
                                        </x-confirm-form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center">
                                            <x-icon name="user-circle" class="w-7 h-7 stroke-[1.5]" />
                                        </div>
                                        <span class="text-sm font-semibold text-slate-500 dark:text-slate-400 font-jakarta">Belum ada data siswa.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="flex justify-center">{{ $siswa->links() }}</div>
    </div>

</x-app-layout>
