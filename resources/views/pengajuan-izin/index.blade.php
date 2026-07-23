<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-outfit font-black text-2xl sm:text-3xl text-transparent bg-clip-text bg-gradient-to-r from-slate-900 via-indigo-900 to-indigo-600 dark:from-white dark:via-indigo-100 dark:to-indigo-400 tracking-tight">Pengajuan Izin</h2>
                <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta mt-0.5">Approval Izin & Sakit Siswa</p>
            </div>
            <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black font-lexend bg-purple-50 dark:bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-200/50 dark:border-purple-500/20 uppercase tracking-wider">
                <x-icon name="alert-circle" class="w-3.5 h-3.5 stroke-[2.5]" />
                Approval Panel
            </span>
        </div>
    </x-slot>

    @php
        $badgeMap = [
            'menunggu'  => 'bg-amber-500 text-white shadow-amber-500/30',
            'disetujui' => 'bg-emerald-500 text-white shadow-emerald-500/30',
            'ditolak'   => 'bg-rose-500 text-white shadow-rose-500/30',
        ];
        $jenisLabelMap = [
            'izin'         => '📋 Izin',
            'sakit'        => '🏥 Sakit',
            'pulang_cepat' => '🚪 Pulang Cepat',
        ];
        $jenisBadgeMap = [
            'izin'         => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200/50 dark:border-blue-800/50',
            'sakit'        => 'bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-300 border border-rose-200/50 dark:border-rose-800/50',
            'pulang_cepat' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 border border-orange-300/50 dark:border-orange-800/50',
        ];
    @endphp

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        {{-- Bento Filter Card --}}
        <form method="GET" class="bento-card rounded-[2rem] p-6 shadow-xl relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[70px] font-black text-slate-900/[0.03] dark:text-white/[0.02] font-lexend pointer-events-none tracking-tighter leading-none select-none">IZIN</div>
            <div class="flex flex-wrap gap-4 items-end relative z-10">
                <div class="min-w-40">
                    <label for="status" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Status</label>
                    <select id="status" name="status"
                            class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500/30 backdrop-blur-sm px-4 py-2.5">
                        @foreach (['menunggu' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'semua' => 'Semua'] as $val => $label)
                            <option value="{{ $val }}" @selected($status === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
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
                @endif
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-black font-lexend text-xs uppercase tracking-wider shadow-lg shadow-indigo-500/20 transition-all duration-300 transform active:scale-95">
                    <x-icon name="search" class="w-4 h-4 stroke-[2.5]" /> Tampilkan
                </button>
            </div>
        </form>

        {{-- Bento Cards Pengajuan --}}
        @forelse ($pengajuanList as $p)
            <div class="bento-card rounded-[2rem] p-6 sm:p-7 shadow-xl relative group transition-all duration-300 hover:scale-[1.005] overflow-x-auto {{ $p->status === 'menunggu' ? 'border-amber-200/50 dark:border-amber-800/30' : '' }}">
                {{-- Decorative overlay (overflow-hidden isolated agar tidak clip modal) --}}
                <div class="absolute inset-0 rounded-[2rem] overflow-hidden pointer-events-none">
                    @if ($p->status === 'menunggu')
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-amber-400 via-amber-500 to-orange-500 rounded-t-[2rem]"></div>
                    @endif
                    <div class="absolute -right-4 -bottom-4 text-[50px] font-black text-slate-900/[0.025] dark:text-white/[0.015] font-lexend tracking-tighter leading-none select-none uppercase">{{ $p->jenis }}</div>
                </div>

                <div class="flex items-center justify-between gap-5 relative z-10 min-w-max">
                    {{-- Left: Student Info --}}
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white font-black font-lexend flex items-center justify-center shadow-lg shadow-indigo-500/30 shrink-0 text-lg leading-none">{{ Illuminate\Support\Str::of($p->siswa->nama)->substr(0, 1)->upper() }}</div>
                        <div class="min-w-0 flex-1">
                            <h4 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 truncate">{{ $p->siswa->nama }}</h4>
                            <div class="flex flex-wrap items-center gap-2 mt-1.5">
                                <span class="inline-flex px-3 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-lexend font-bold text-[10px] uppercase tracking-wider">{{ $p->siswa->kelas->nama_kelas ?? '-' }}</span>
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-lexend font-bold text-[10px] uppercase tracking-wider border border-indigo-200/50 dark:border-indigo-800/50">
                                    <x-icon name="calendar" class="w-3 h-3 stroke-[2.5]" />
                                    {{ $p->tanggal->format('d/m/Y') }}
                                </span>
                                <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest shadow-md {{ $badgeMap[$p->status] ?? 'bg-slate-500 text-white' }}">
                                    {{ ucfirst($p->status) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Center: Details --}}
                    <div class="flex flex-wrap items-center gap-4 lg:gap-6">
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest font-jakarta">Jenis</span>
                            <span class="inline-flex items-center gap-1.5 mt-1 px-3 py-1 rounded-lg font-lexend font-black text-[10px] uppercase tracking-wider {{ $jenisBadgeMap[$p->jenis] ?? 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                                {{ $jenisLabelMap[$p->jenis] ?? ucfirst($p->jenis) }}
                            </span>
                        </div>
                        <div class="flex flex-col max-w-xs">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest font-jakarta">Keterangan</span>
                            <span class="font-jakarta font-semibold text-sm text-slate-600 dark:text-slate-300 mt-0.5 line-clamp-2">{{ $p->keterangan }}</span>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest font-jakarta">Bukti</span>
                            <a href="{{ \Illuminate\Support\Facades\Storage::url($p->bukti) }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1.5 mt-0.5 text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 font-lexend font-bold text-sm transition-colors">
                                <x-icon name="eye" class="w-4 h-4 stroke-[2.5]" /> Lihat
                            </a>
                        </div>
                    </div>

                    {{-- Right: Actions --}}
                    <div class="flex items-center gap-2 shrink-0">
                        @if ($p->status === 'menunggu')
                            <x-confirm-form :action="route('pengajuan-izin.approve', $p)" method="POST"
                                             title="Setujui pengajuan {{ $p->siswa->nama }}?"
                                             message="{{ $p->jenis === 'pulang_cepat' ? 'Siswa akan diizinkan melakukan absen pulang sebelum jam resmi. Status hadir tidak berubah.' : 'Absensi tanggal '.$p->tanggal->format('d/m/Y').' akan dicatat sebagai '.$p->jenis.'. Pengajuan yang sudah disetujui tidak bisa ditinjau ulang.' }}"
                                             confirm-label="Setujui" :danger="false"
                                             trigger-class="inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-400 hover:to-green-500 text-white font-black font-lexend text-[10px] uppercase tracking-wider shadow-lg shadow-emerald-500/20 transition-all duration-300 transform active:scale-95"><x-icon name="check-circle" class="w-4 h-4 stroke-[2.5]" /> Approve</x-confirm-form>
                            <x-confirm-form :action="route('pengajuan-izin.reject', $p)" method="POST"
                                             title="Tolak pengajuan {{ $p->siswa->nama }}?"
                                             confirm-label="Tolak"
                                             trigger-class="inline-flex justify-center items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 font-black font-lexend text-[10px] uppercase tracking-wider transition-all duration-300 border border-slate-200/50 dark:border-slate-700/50">
                                <x-slot:fields>
                                    <div>
                                        <label for="catatan_admin_{{ $p->id }}" class="block text-[11px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500 mb-1.5">Catatan (opsional)</label>
                                        <input id="catatan_admin_{{ $p->id }}" name="catatan_admin" type="text"
                                               class="block w-full rounded-xl border-slate-200 dark:border-slate-700 bg-white/50 dark:bg-slate-900/40 text-slate-800 dark:text-slate-100 font-lexend font-bold text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500/30 px-4 py-2.5" />
                                    </div>
                                </x-slot:fields><x-icon name="x-circle" class="w-4 h-4 stroke-[2.5]" /> Reject</x-confirm-form>
                        @endif
                    </div>
                </div>

                {{-- Admin Note (if rejected) --}}
                @if ($p->status === 'ditolak' && $p->catatan_admin)
                    <div class="mt-4 pt-4 border-t border-slate-200/50 dark:border-slate-700/50 relative z-10">
                        <div class="flex items-start gap-3">
                            <div class="w-7 h-7 rounded-lg bg-rose-100 dark:bg-rose-900/30 text-rose-500 flex items-center justify-center shrink-0">
                                <x-icon name="alert-circle" class="w-4 h-4 stroke-[2.5]" />
                            </div>
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-widest font-jakarta text-rose-500 dark:text-rose-400">Alasan Penolakan</span>
                                <p class="text-sm font-semibold text-slate-600 dark:text-slate-400 font-jakarta mt-0.5">{{ $p->catatan_admin }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="bento-card rounded-[2.5rem] p-12 shadow-xl relative overflow-hidden">
                <div class="flex flex-col items-center gap-4 relative z-10">
                    <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center">
                        <x-icon name="alert-circle" class="w-8 h-8 stroke-[1.5]" />
                    </div>
                    <span class="text-sm font-semibold text-slate-500 dark:text-slate-400 font-jakarta">Tidak ada pengajuan untuk filter ini.</span>
                </div>
            </div>
        @endforelse
    </div>

</x-app-layout>
