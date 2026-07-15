<x-siswa-layout>
    @php
        $badgeMap = [
            'menunggu' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
            'disetujui' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
            'ditolak' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        ];
        $labelMap = [
            'menunggu' => 'Menunggu Persetujuan',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
        ];
        $tampilkanForm = ! $pengajuanHariIni || $pengajuanHariIni->status === 'ditolak';
    @endphp

    <div class="space-y-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-5">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Izin / Sakit</h1>
                <x-icon name="clipboard" class="w-5 h-5 text-gray-400 dark:text-gray-500" />
            </div>

            @if ($pengajuanHariIni)
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Pengajuan hari ini</p>
                        <p class="font-medium text-gray-800 dark:text-gray-100">{{ ucfirst($pengajuanHariIni->jenis) }}</p>
                    </div>
                    <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $badgeMap[$pengajuanHariIni->status] }}">
                        {{ $labelMap[$pengajuanHariIni->status] }}
                    </span>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">{{ $pengajuanHariIni->keterangan }}</p>

                @if ($pengajuanHariIni->status === 'ditolak' && $pengajuanHariIni->catatan_admin)
                    <div class="rounded-md bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 p-3 text-sm text-red-800 dark:text-red-300 mb-2">
                        <strong>Catatan:</strong> {{ $pengajuanHariIni->catatan_admin }}
                    </div>
                @endif
            @endif

            @if ($tampilkanForm)
                @if ($pengajuanHariIni)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Pengajuan sebelumnya ditolak. Kamu bisa mengajukan ulang di bawah ini.</p>
                @endif

                <form method="POST" action="{{ route('siswa.izin.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div>
                        <x-input-label for="jenis" value="Jenis" />
                        <select id="jenis" name="jenis" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                            <option value="izin" @selected(old('jenis') === 'izin')>Izin</option>
                            <option value="sakit" @selected(old('jenis') === 'sakit')>Sakit</option>
                        </select>
                        <x-input-error :messages="$errors->get('jenis')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="keterangan" value="Keterangan" />
                        <x-text-input id="keterangan" class="block mt-1 w-full" type="text" name="keterangan" :value="old('keterangan')" required />
                        <x-input-error :messages="$errors->get('keterangan')" class="mt-2" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="bukti" value="Bukti / Surat" />
                        <input id="bukti" type="file" name="bukti" accept="image/*" required
                               class="block mt-1 w-full text-sm text-gray-600 dark:text-gray-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 dark:file:bg-indigo-900/40 file:text-indigo-600 dark:file:text-indigo-300 hover:file:bg-indigo-100" />
                        <x-input-error :messages="$errors->get('bukti')" class="mt-2" />
                    </div>

                    <button type="submit" class="mt-5 w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2.5 rounded-lg font-medium transition">
                        Kirim Pengajuan
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-siswa-layout>
