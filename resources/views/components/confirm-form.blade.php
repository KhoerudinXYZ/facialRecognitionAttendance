@props([
    'action',
    'method' => 'DELETE',
    'title',
    'message' => null,
    'confirmLabel' => 'Hapus',
    'triggerClass' => 'inline-flex items-center gap-1 text-red-600 dark:text-red-400 hover:underline',
    'fields' => null,
    'danger' => true,
])

<div x-data="{ open: false }" class="inline">
    <button type="button" @click="open = true" class="{{ $triggerClass }}">
        {{ $slot }}
    </button>

    {{-- z-[1000], bukan z-50 -- panel internal Leaflet (dipakai di halaman
         Pengaturan lokasi) pakai z-index sampai ~700 (popup pane), jadi
         z-50 bisa ketiban peta & tombol modal ketutupan/ketimpa elemen
         peta yang ada di baliknya (nge-block klik walau modal kelihatan
         di atas secara visual). --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak
             class="fixed inset-0 z-[1000] flex items-center justify-center px-4"
             @click.self="open = false"
             @keydown.escape.window="open = false">
            <div x-show="open" x-transition
                 class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-sm p-6 text-left">
                <h3 class="font-semibold text-gray-800 dark:text-gray-100">{{ $title }}</h3>

                @if ($message)
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $message }}</p>
                @endif

                <form action="{{ $action }}" method="POST" class="mt-6 space-y-4">
                    @csrf
                    @if (strtoupper($method) !== 'POST')
                        @method($method)
                    @endif

                    {{-- Alpine scope tetap menumpuk lewat DOM biarpun ada
                         x-data="{open:false}" di div terluar komponen ini --
                         slot Blade bukan batas scope Alpine, jadi field di
                         sini masih bisa baca state (mis. $selected) dari
                         x-data milik halaman pemanggil tanpa perlu di-drill.
                         Ditaruh di baris/blok sendiri (bukan ikut flex tombol
                         di bawah) supaya bisa dipakai buat field yang KELIHATAN
                         (mis. input catatan), bukan cuma hidden input. --}}
                    {{ $fields }}

                    <div class="flex justify-end gap-3">
                        <x-secondary-button type="button" @click="open = false">Batal</x-secondary-button>
                        @if ($danger)
                            <x-danger-button type="submit">{{ $confirmLabel }}</x-danger-button>
                        @else
                            <x-primary-button type="submit">{{ $confirmLabel }}</x-primary-button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
