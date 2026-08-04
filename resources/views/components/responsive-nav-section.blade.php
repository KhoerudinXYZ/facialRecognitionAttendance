@props(['title', 'icon', 'active' => false])

{{-- Grup collapsible di menu mobile -- dulu semua link (Presensi + Data
     Master + Administrasi, total bisa ~14 item) tampil sekaligus sebagai
     satu daftar panjang begitu hamburger dibuka, jadi mirip menu desktop
     yang dropdown-nya emang collapsible tapi versi mobile-nya enggak.
     Default collapsed KECUALI section itu sendiri lagi aktif (biar user
     gak perlu buka manual buat lihat halaman yang sedang dibuka). --}}
<div x-data="{ show: {{ $active ? 'true' : 'false' }} }">
    <button type="button" @click="show = !show"
            class="w-full flex items-center justify-between px-4 py-2.5 text-xs font-semibold uppercase tracking-wide {{ $active ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500' }}">
        <span class="flex items-center gap-1.5">
            <x-icon :name="$icon" class="w-3.5 h-3.5" />
            {{ $title }}
        </span>
        <svg :class="{ 'rotate-180': show }" class="w-3.5 h-3.5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>
    <div x-show="show" x-transition.duration.150ms class="space-y-1 pb-1">
        {{ $slot }}
    </div>
</div>
