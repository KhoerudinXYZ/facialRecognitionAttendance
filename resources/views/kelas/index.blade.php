<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-outfit font-black text-2xl sm:text-3xl text-transparent bg-clip-text bg-gradient-to-r from-slate-900 via-indigo-900 to-indigo-600 dark:from-white dark:via-indigo-100 dark:to-indigo-400 tracking-tight">Data Kelas</h2>
                <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta mt-0.5">Manajemen Kelas dan Wali Kelas</p>
            </div>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('kelas.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-black font-lexend text-xs uppercase tracking-wider shadow-lg shadow-indigo-500/20 transition-all duration-300 transform active:scale-95 shrink-0">
                    <x-icon name="plus" class="w-4 h-4 stroke-[2.5]" /> Tambah Kelas
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if ($kelasTanpaWali > 0)
            <div class="bento-card rounded-[2rem] p-6 border-amber-200/50 dark:border-amber-800/40 relative overflow-hidden bg-gradient-to-r from-amber-500/10 via-orange-500/10 to-amber-500/5 backdrop-blur-md">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-amber-500/30">
                        <x-icon name="x-circle" class="w-6 h-6 stroke-[2.5]" />
                    </div>
                    <div>
                        <span class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 block">Perhatian: Kelas Tanpa Wali</span>
                        <p class="text-sm font-semibold text-slate-600 dark:text-slate-400 font-jakarta mt-1">{{ $kelasTanpaWali }} kelas di bawah belum memiliki wali kelas yang ditugaskan.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Bento Table Card --}}
        <div class="bento-card rounded-[2.5rem] p-6 sm:p-8 shadow-xl relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 text-[100px] font-black text-slate-900/[0.02] dark:text-white/[0.015] font-lexend pointer-events-none tracking-tighter leading-none select-none">KELAS</div>

            <div class="flex items-center justify-between pb-5 border-b border-slate-200/50 dark:border-slate-700/50 relative z-10 mb-2">
                <div>
                    <h3 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 tracking-tight">Daftar Kelas</h3>
                    <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta">Total {{ $kelas->total() }} Kelas</span>
                </div>
            </div>

            <div class="overflow-x-auto relative z-10">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200/50 dark:border-slate-700/50">
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Info Kelas</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Jurusan & Tingkat</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Wali Kelas</th>
                            <th class="px-5 py-3 text-center text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Jml Siswa</th>
                            <th class="px-5 py-3 text-center text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Kehadiran Harian</th>
                            <th class="px-5 py-3 text-right text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kelas as $k)
                            <tr class="border-b border-slate-100/50 dark:border-slate-800/50 hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors duration-200">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white font-black font-lexend text-xs flex items-center justify-center shadow-md">
                                            {{ Illuminate\Support\Str::of($k->nama_kelas)->substr(0, 2)->upper() }}
                                        </div>
                                        <span class="font-black font-outfit text-slate-800 dark:text-slate-100 text-base">{{ $k->nama_kelas }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="inline-flex px-3 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-lexend font-bold text-xs w-max">{{ $k->jurusan ?? '-' }}</span>
                                        <span class="text-[10px] font-jakarta font-semibold text-slate-400 dark:text-slate-500">Tingkat {{ $k->tingkat }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    @if ($k->waliKelas)
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                                                <x-icon name="user-circle" class="w-3.5 h-3.5" />
                                            </div>
                                            <span class="font-jakarta font-semibold text-slate-700 dark:text-slate-300 text-sm">{{ $k->waliKelas->name }}</span>
                                        </div>
                                    @else
                                        <span class="inline-flex px-2 py-1 rounded-md bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 font-lexend font-bold text-[10px] uppercase tracking-wider border border-rose-200/50 dark:border-rose-800/50">
                                            Belum Diatur
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="font-lexend font-black text-sm text-slate-800 dark:text-slate-200">{{ $k->siswa_count }}</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    @if ($isLibur)
                                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest shadow-md bg-slate-500 text-white shadow-slate-500/30">
                                            Libur
                                        </span>
                                    @else
                                        <span @class([
                                                'inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest shadow-md',
                                                'bg-emerald-500 text-white shadow-emerald-500/30' => $k->siswa_count > 0 && $k->hadir_hari_ini_count >= $k->siswa_count,
                                                'bg-amber-500 text-white shadow-amber-500/30' => $k->hadir_hari_ini_count > 0 && $k->hadir_hari_ini_count < $k->siswa_count,
                                                'bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-400 shadow-none' => $k->hadir_hari_ini_count === 0,
                                            ])>
                                            {{ $k->hadir_hari_ini_count }} / {{ $k->siswa_count }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('siswa.index', ['kelas_id' => $k->id]) }}"
                                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-800/40 font-lexend font-bold text-[10px] uppercase tracking-wider border border-indigo-200/50 dark:border-indigo-800/50 transition-all duration-200">
                                            <x-icon name="users" class="w-3 h-3 stroke-[2.5]" /> Siswa
                                        </a>
                                        @if (auth()->user()->isAdmin())
                                            <a href="{{ route('kelas.edit', $k) }}"
                                               class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors duration-200">
                                                <x-icon name="pencil" class="w-3.5 h-3.5 stroke-[2.5]" />
                                            </a>
                                            <x-confirm-form :action="route('kelas.destroy', $k)" title="Hapus kelas ini?"
                                                             message="Kelas yang masih ada siswanya tidak bisa dihapus."
                                                             trigger-class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 transition-colors duration-200">
                                                <x-icon name="trash" class="w-3.5 h-3.5 stroke-[2.5]" />
                                            </x-confirm-form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center">
                                            <x-icon name="clipboard-list" class="w-7 h-7 stroke-[1.5]" />
                                        </div>
                                        <span class="text-sm font-semibold text-slate-500 dark:text-slate-400 font-jakarta">Belum ada data kelas.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 relative z-10">
                {{ $kelas->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
