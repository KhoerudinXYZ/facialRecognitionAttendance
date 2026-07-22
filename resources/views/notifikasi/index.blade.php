<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-outfit font-black text-2xl sm:text-3xl text-transparent bg-clip-text bg-gradient-to-r from-slate-900 via-indigo-900 to-indigo-600 dark:from-white dark:via-indigo-100 dark:to-indigo-400 tracking-tight">Notifikasi Orang Tua</h2>
                <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta mt-0.5">Riwayat Pengiriman Email Absensi</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        
        <div class="bento-card rounded-[2rem] p-6 border-indigo-200/50 dark:border-indigo-800/40 relative overflow-hidden bg-gradient-to-r from-indigo-500/10 via-purple-500/10 to-indigo-500/5 backdrop-blur-md">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-indigo-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-indigo-500/30">
                    <x-icon name="information-circle" class="w-6 h-6 stroke-[2.5]" />
                </div>
                <div>
                    <span class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 block">Sistem Notifikasi Otomatis</span>
                    <p class="text-xs font-semibold text-slate-600 dark:text-slate-400 font-jakarta mt-1 leading-relaxed">
                        Riwayat notifikasi email ke orang tua: konfirmasi <strong class="text-indigo-600 dark:text-indigo-400">kehadiran</strong> (dikirim otomatis tiap siswa absen masuk) dan pemberitahuan <strong class="text-rose-600 dark:text-rose-400">alpha</strong> (siswa belum absen sampai beberapa jam setelah jam pulang). 
                        Kalau <code>MAIL_MAILER</code> di server menggunakan <code>log</code> (belum SMTP asli), status "Terkirim" berarti email berhasil ditulis ke log lokal.
                    </p>
                </div>
            </div>
        </div>

        {{-- Bento Table Card --}}
        <div class="bento-card rounded-[2.5rem] p-6 sm:p-8 shadow-xl relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 text-[100px] font-black text-slate-900/[0.02] dark:text-white/[0.015] font-lexend pointer-events-none tracking-tighter leading-none select-none">RIWAYAT</div>

            <div class="flex items-center justify-between pb-5 border-b border-slate-200/50 dark:border-slate-700/50 relative z-10 mb-2">
                <div>
                    <h3 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 tracking-tight">Daftar Pengiriman Email</h3>
                    <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta">Total {{ $log->total() }} Notifikasi</span>
                </div>
            </div>

            <div class="overflow-x-auto relative z-10">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200/50 dark:border-slate-700/50">
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Tanggal & Waktu</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Info Siswa</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Jenis Notifikasi</th>
                            <th class="px-5 py-3 text-left text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Alamat Email</th>
                            <th class="px-5 py-3 text-center text-[10px] font-black uppercase tracking-widest font-jakarta text-slate-400 dark:text-slate-500">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($log as $entry)
                            <tr class="border-b border-slate-100/50 dark:border-slate-800/50 hover:bg-indigo-50/30 dark:hover:bg-indigo-900/10 transition-colors duration-200">
                                <td class="px-5 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-lexend font-bold text-xs text-slate-700 dark:text-slate-300">{{ $entry->tanggal->format('d M Y') }}</span>
                                        <span class="text-[10px] font-jakarta font-semibold text-slate-400 dark:text-slate-500">{{ $entry->created_at->format('H:i') }} WIB</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-black font-lexend text-[10px] flex items-center justify-center border border-slate-200/50 dark:border-slate-700/50">
                                            {{ Illuminate\Support\Str::of($entry->siswa_nama)->substr(0, 1)->upper() }}
                                        </div>
                                        <span class="font-black font-outfit text-slate-800 dark:text-slate-100">{{ $entry->siswa_nama }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    @if ($entry->jenis === 'kehadiran')
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-lexend font-bold text-[10px] uppercase tracking-wider border border-indigo-200/50 dark:border-indigo-800/50">
                                            <x-icon name="check-circle" class="w-3 h-3 stroke-[2.5]" /> Kehadiran
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 font-lexend font-bold text-[10px] uppercase tracking-wider border border-rose-200/50 dark:border-rose-800/50">
                                            <x-icon name="x-circle" class="w-3 h-3 stroke-[2.5]" /> Alpha
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <span class="font-jakarta font-semibold text-slate-600 dark:text-slate-300">{{ $entry->kontak ?? '-' }}</span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    @if ($entry->status === 'terkirim')
                                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest shadow-md bg-emerald-500 text-white shadow-emerald-500/30">
                                            Terkirim
                                        </span>
                                    @elseif ($entry->status === 'tidak_ada_kontak')
                                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest shadow-md bg-slate-200 text-slate-500 dark:bg-slate-700 dark:text-slate-400 shadow-none">
                                            Tanpa Email
                                        </span>
                                    @else
                                        <span class="inline-flex px-3 py-1 rounded-full text-[10px] font-black font-lexend uppercase tracking-widest shadow-md bg-amber-500 text-white shadow-amber-500/30">
                                            Gagal
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 flex items-center justify-center">
                                            <x-icon name="mail" class="w-7 h-7 stroke-[1.5]" />
                                        </div>
                                        <span class="text-sm font-semibold text-slate-500 dark:text-slate-400 font-jakarta">Belum ada riwayat notifikasi yang dikirim.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($log->hasPages())
                <div class="mt-6 relative z-10">
                    {{ $log->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
