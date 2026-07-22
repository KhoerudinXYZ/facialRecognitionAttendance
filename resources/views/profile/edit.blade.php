<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-outfit font-black text-2xl sm:text-3xl text-transparent bg-clip-text bg-gradient-to-r from-slate-900 via-indigo-900 to-indigo-600 dark:from-white dark:via-indigo-100 dark:to-indigo-400 tracking-tight">
                    {{ __('Profile') }}
                </h2>
                <p class="text-[11px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest font-jakarta mt-0.5">Pengaturan Akun Pribadi</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            {{-- Update Profile Information --}}
            <div class="bento-card rounded-[2.5rem] p-6 sm:p-8 shadow-xl relative overflow-hidden h-full">
                <div class="absolute -right-6 -bottom-6 text-[100px] font-black text-slate-900/[0.02] dark:text-white/[0.015] font-lexend pointer-events-none tracking-tighter leading-none select-none">INFO</div>
                
                <div class="relative z-10 flex flex-col h-full">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white flex items-center justify-center shadow-lg shadow-indigo-500/30">
                            <x-icon name="user-circle" class="w-5 h-5 stroke-[2.5]" />
                        </div>
                        <div>
                            <h3 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 tracking-tight">Informasi Profil</h3>
                        </div>
                    </div>
                    
                    <div class="flex-grow">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>
            </div>

            {{-- Update Password --}}
            <div class="bento-card rounded-[2.5rem] p-6 sm:p-8 shadow-xl relative overflow-hidden h-full">
                <div class="absolute -right-6 -bottom-6 text-[100px] font-black text-slate-900/[0.02] dark:text-white/[0.015] font-lexend pointer-events-none tracking-tighter leading-none select-none">SANDI</div>
                
                <div class="relative z-10 flex flex-col h-full">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-lg shadow-emerald-500/30">
                            <x-icon name="key" class="w-5 h-5 stroke-[2.5]" />
                        </div>
                        <div>
                            <h3 class="font-outfit font-black text-lg text-slate-800 dark:text-slate-100 tracking-tight">Ubah Kata Sandi</h3>
                        </div>
                    </div>
                    
                    <div class="flex-grow">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
            </div>

            {{-- Delete Account --}}
            <div class="md:col-span-2 bento-card rounded-[2.5rem] p-6 sm:p-8 shadow-xl relative overflow-hidden border-rose-200/50 dark:border-rose-800/40 bg-gradient-to-r from-rose-500/5 via-transparent to-transparent">
                <div class="absolute -right-6 -bottom-6 text-[100px] font-black text-rose-900/[0.02] dark:text-rose-100/[0.015] font-lexend pointer-events-none tracking-tighter leading-none select-none">HAPUS</div>
                
                <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-rose-500 to-pink-600 text-white flex items-center justify-center shadow-lg shadow-rose-500/30">
                            <x-icon name="trash" class="w-5 h-5 stroke-[2.5]" />
                        </div>
                        <div>
                            <h3 class="font-outfit font-black text-lg text-rose-800 dark:text-rose-200 tracking-tight">Hapus Akun</h3>
                        </div>
                    </div>
                    
                    <div class="max-w-xl">
                        @include('profile.partials.delete-user-form')
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
