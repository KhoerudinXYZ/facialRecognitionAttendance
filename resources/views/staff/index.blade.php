<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-outfit font-black text-2xl sm:text-3xl text-transparent bg-clip-text bg-gradient-to-r from-slate-900 via-indigo-900 to-indigo-600 dark:from-white dark:via-indigo-100 dark:to-indigo-400 tracking-tight">Data Staff</h2>
                <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta mt-0.5">Administrator & Wali Kelas</p>
            </div>
            <a href="{{ route('staff.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-black font-lexend text-xs uppercase tracking-wider shadow-lg shadow-indigo-500/20 transition-all duration-300 transform active:scale-95 shrink-0">
                <x-icon name="plus" class="w-4 h-4 stroke-[2.5]" /> Tambah Staff
            </a>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if ($waliTanpaKelas > 0)
            <div class="bento-card rounded-[2rem] p-6 border-amber-200/50 dark:border-amber-800/40 relative overflow-hidden bg-gradient-to-r from-amber-500/10 via-orange-500/10 to-amber-500/5 backdrop-blur-md">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-amber-500/30">
                        <x-icon name="alert-circle" class="w-6 h-6 stroke-[2.5]" />
                    </div>
                    <div>
                        <span class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 block">Wali Kelas Tanpa Penugasan</span>
                        <p class="text-sm font-semibold text-slate-600 dark:text-slate-400 font-jakarta mt-1">{{ $waliTanpaKelas }} wali kelas di bawah belum ditugaskan ke kelas manapun.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Bento Table Card --}}
        <div class="bento-card rounded-[2.5rem] p-6 sm:p-8 shadow-xl relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 text-[100px] font-black text-slate-900/[0.02] dark:text-white/[0.015] font-lexend pointer-events-none tracking-tighter leading-none select-none">STAFF</div>

            <div class="flex items-center justify-between pb-5 border-b border-slate-200/50 dark:border-slate-700/50 relative z-10 mb-2">
                <div>
                    <h3 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 tracking-tight">Daftar Akun Staff</h3>
                    <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta">Total {{ $staff->total() }} Akun</span>
                </div>
            </div>

            <div class="overflow-x-auto relative z-10">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200/50 dark:border-slate-700/50">
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Profil Staff</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Peran & Akses</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Kelas Binaan</th>
                            <th class="px-5 py-3 text-center text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Kehadiran Harian</th>
                            <th class="px-5 py-3 text-right text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($staff as $s)
                            <tr class="border-b border-slate-100/50 dark:border-slate-800/50 hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors duration-200">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-slate-500 to-slate-700 text-white font-black font-lexend text-xs flex items-center justify-center shadow-md">
                                            {{ Illuminate\Support\Str::of($s->name)->substr(0, 1)->upper() }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-black font-outfit text-slate-800 dark:text-slate-100 text-base">{{ $s->name }}</span>
                                            <span class="text-[10px] font-jakarta font-semibold text-slate-400 dark:text-slate-500">{{ $s->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    @if ($s->role === 'admin')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-black bg-gradient-to-r from-indigo-500 to-purple-500 text-white shadow-md shadow-indigo-500/20 uppercase tracking-widest">
                                            <x-icon name="cog" class="w-3.5 h-3.5" /> Administrator
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-black bg-gradient-to-r from-sky-500 to-blue-600 text-white shadow-md shadow-blue-500/20 uppercase tracking-widest">
                                            <x-icon name="user-circle" class="w-3.5 h-3.5" /> Wali Kelas
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if ($s->role === 'wali_kelas' && $s->kelasBinaan->isNotEmpty())
                                        <span class="inline-flex px-3 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-lexend font-bold text-xs w-max border border-slate-200/50 dark:border-slate-700/50">
                                            {{ $s->kelasBinaan->pluck('nama_kelas')->join(', ') }}
                                        </span>
                                    @else
                                        <span class="text-slate-300 dark:text-slate-600 font-lexend text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-center">
                                    @if ($s->role !== 'wali_kelas')
                                        <span class="text-slate-300 dark:text-slate-600 font-lexend text-xs">—</span>
                                    @elseif ($isLibur)
                                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest shadow-md bg-slate-500 text-white shadow-slate-500/30">
                                            Libur
                                        </span>
                                    @elseif ($s->total_siswa_binaan === 0)
                                        <span class="text-[10px] font-jakarta font-semibold text-slate-400 dark:text-slate-500 border-b border-dashed border-slate-400">Belum ada kelas</span>
                                    @else
                                        <span @class([
                                                'inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest shadow-md',
                                                'bg-emerald-500 text-white shadow-emerald-500/30' => $s->hadir_hari_ini_binaan >= $s->total_siswa_binaan,
                                                'bg-amber-500 text-white shadow-amber-500/30' => $s->hadir_hari_ini_binaan > 0 && $s->hadir_hari_ini_binaan < $s->total_siswa_binaan,
                                                'bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-400 shadow-none' => $s->hadir_hari_ini_binaan === 0,
                                            ])>
                                            {{ $s->hadir_hari_ini_binaan }} / {{ $s->total_siswa_binaan }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('staff.edit', $s) }}"
                                           class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors duration-200">
                                            <x-icon name="pencil" class="w-4 h-4 stroke-[2.5]" />
                                        </a>
                                        @if ($s->id !== auth()->id())
                                            <x-confirm-form :action="route('staff.destroy', $s)" title="Hapus akun staff ini?"
                                                             trigger-class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 transition-colors duration-200">
                                                <x-icon name="trash" class="w-4 h-4 stroke-[2.5]" />
                                            </x-confirm-form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center">
                                            <x-icon name="users" class="w-7 h-7 stroke-[1.5]" />
                                        </div>
                                        <span class="text-sm font-semibold text-slate-500 dark:text-slate-400 font-jakarta">Belum ada akun staff.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 relative z-10">
                {{ $staff->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
