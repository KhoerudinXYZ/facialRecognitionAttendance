<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Tambah Siswa</h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <form action="{{ route('siswa.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @include('siswa.form')
                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ route('siswa.index') }}" class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-gray-100">Batal</a>
                    <x-primary-button class="inline-flex items-center gap-2">
                        <x-icon name="check" class="w-4 h-4" /> Simpan & Daftar Wajah
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
