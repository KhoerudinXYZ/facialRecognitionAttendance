<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Detail Siswa</h2>
            <a href="{{ route('siswa.enroll', $siswa) }}" class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded-lg">
                <x-icon name="camera" class="w-4 h-4" /> Daftar Wajah
            </a>
        </div>
    </x-slot>

    <div class="py-8 max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 flex gap-6">
            @if ($siswa->foto)
                <img src="{{ Storage::url($siswa->foto) }}" class="h-28 w-28 object-cover rounded-lg" alt="foto">
            @else
                <div class="h-28 w-28 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-400 dark:text-gray-500 text-3xl">
                    {{ Str::substr($siswa->nama, 0, 1) }}
                </div>
            @endif
            <div class="space-y-1">
                <div class="text-2xl font-semibold text-gray-800 dark:text-gray-100">{{ $siswa->nama }}</div>
                <div class="text-gray-500 dark:text-gray-400">NIS: {{ $siswa->nis }} @if($siswa->nisn) &middot; NISN: {{ $siswa->nisn }} @endif</div>
                <div class="text-gray-500 dark:text-gray-400">Kelas: {{ $siswa->kelas->nama_kelas ?? '-' }}</div>
                <div class="text-gray-500 dark:text-gray-400">Jenis Kelamin: {{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</div>
                <div class="pt-1 flex items-center gap-2">
                    @if ($siswa->faceDescriptors->count() > 0)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300">
                            <x-icon name="camera" class="w-3 h-3" /> Wajah terdaftar ({{ $siswa->faceDescriptors->count() }} sampel)
                        </span>
                    @else
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">Wajah belum terdaftar</span>
                    @endif

                    @if ($siswa->isRegistered())
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                            <x-icon name="key" class="w-3 h-3" /> Akun login aktif ({{ $siswa->username }})
                        </span>
                    @else
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">Belum registrasi akun</span>
                    @endif
                </div>

                @if ($siswa->isRegistered())
                    <form action="{{ route('siswa.resetAccount', $siswa) }}" method="POST"
                          onsubmit="return confirm('Reset akun login siswa ini? Siswa harus registrasi ulang dengan NIS.')">
                        @csrf @method('PUT')
                        <button class="inline-flex items-center gap-1 text-sm text-red-600 dark:text-red-400 hover:underline">
                            <x-icon name="key" class="w-3.5 h-3.5" /> Reset Akun Login
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-4">Riwayat Absensi Terakhir</h3>
            @if ($riwayat->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat absensi.</p>
            @else
                <table class="min-w-full text-sm">
                    <thead><tr class="text-left text-gray-500 dark:text-gray-400 border-b dark:border-gray-700">
                        <th class="py-2 pr-4">Tanggal</th><th class="py-2 pr-4">Jam</th><th class="py-2 pr-4">Status</th><th class="py-2">Metode</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($riwayat as $a)
                            <tr class="border-b dark:border-gray-700 last:border-0">
                                <td class="py-2 pr-4 dark:text-gray-300">{{ $a->tanggal->format('d/m/Y') }}</td>
                                <td class="py-2 pr-4 dark:text-gray-300">{{ \Illuminate\Support\Str::of($a->jam_masuk)->substr(0,5) ?: '-' }}</td>
                                <td class="py-2 pr-4"><x-status-badge :status="$a->status" /></td>
                                <td class="py-2 text-gray-500 dark:text-gray-400">{{ ucfirst($a->metode) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-app-layout>
