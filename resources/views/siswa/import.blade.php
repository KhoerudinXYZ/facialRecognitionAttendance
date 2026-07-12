<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Import Siswa dari Excel</h2>
    </x-slot>

    <div class="py-8 max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if (session('import_errors') && count(session('import_errors')) > 0)
            <div class="bg-yellow-50 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 border border-yellow-200 dark:border-yellow-700 rounded-lg px-4 py-3 text-sm">
                <p class="font-medium mb-2">{{ count(session('import_errors')) }} baris dilewati:</p>
                <ul class="list-disc list-inside space-y-1">
                    @foreach (session('import_errors') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 space-y-4">
            <div class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                <p>Format kolom: <strong>NIS, NISN, Nama, Jenis Kelamin (L/P), Kelas, Aktif (Y/N)</strong>.</p>
                <p>Nilai <strong>Kelas</strong> harus sama persis dengan nama kelas yang sudah dibuat (tidak sensitif huruf besar/kecil). Baris dengan NIS kosong/duplikat atau kelas tidak ditemukan akan dilewati &mdash; tidak menggagalkan baris lain.</p>
                <a href="{{ route('siswa.import.template') }}" class="inline-flex items-center gap-1.5 text-indigo-600 dark:text-indigo-400 hover:underline">
                    <x-icon name="download" class="w-4 h-4" /> Unduh template Excel
                </a>
            </div>

            <form action="{{ route('siswa.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <x-input-label for="file" value="File Excel (.xlsx)" />
                    <input id="file" name="file" type="file" accept=".xlsx" required
                           class="mt-1 block w-full text-sm text-gray-600 dark:text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-900/40 dark:file:text-indigo-300 hover:file:bg-indigo-100" />
                    @error('file')
                        <p class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex justify-end gap-3">
                    <a href="{{ route('siswa.index') }}" class="px-4 py-2 text-gray-600 dark:text-gray-300 hover:text-gray-800 dark:hover:text-gray-100">Batal</a>
                    <x-primary-button class="inline-flex items-center gap-2">
                        <x-icon name="upload" class="w-4 h-4" /> Import
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
