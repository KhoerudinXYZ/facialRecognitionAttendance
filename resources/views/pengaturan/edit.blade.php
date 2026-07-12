<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Pengaturan</h2>
    </x-slot>

    <div class="py-8 max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <form action="{{ route('pengaturan.update') }}" method="POST" class="space-y-5">
                @csrf @method('PUT')
                <div>
                    <x-input-label for="nama_sekolah" value="Nama Sekolah" />
                    <x-text-input id="nama_sekolah" name="nama_sekolah" type="text" class="mt-1 block w-full"
                                  :value="old('nama_sekolah', $pengaturan->nama_sekolah)" required />
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="jam_masuk" value="Jam Masuk" />
                        <x-text-input id="jam_masuk" name="jam_masuk" type="time" class="mt-1 block w-full"
                                      :value="old('jam_masuk', \Illuminate\Support\Str::of($pengaturan->jam_masuk)->substr(0,5))" required />
                    </div>
                    <div>
                        <x-input-label for="batas_terlambat" value="Batas Terlambat" />
                        <x-text-input id="batas_terlambat" name="batas_terlambat" type="time" class="mt-1 block w-full"
                                      :value="old('batas_terlambat', \Illuminate\Support\Str::of($pengaturan->batas_terlambat)->substr(0,5))" required />
                    </div>
                    <div>
                        <x-input-label for="mulai_pulang" value="Mulai Jam Pulang" />
                        <x-text-input id="mulai_pulang" name="mulai_pulang" type="time" class="mt-1 block w-full"
                                      :value="old('mulai_pulang', \Illuminate\Support\Str::of($pengaturan->mulai_pulang)->substr(0,5))" required />
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Siswa yang absen setelah "Batas Terlambat" akan otomatis berstatus <strong>terlambat</strong>.
                    Scan wajah kedua di hari yang sama baru dihitung sebagai <strong>absen pulang</strong> setelah "Mulai Jam Pulang".
                </p>
                <div class="flex justify-end">
                    <x-primary-button>Simpan Pengaturan</x-primary-button>
                </div>
            </form>
        </div>

        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg shadow-sm p-6 mt-4">
            <h3 class="font-semibold text-yellow-900 dark:text-yellow-200">🧪 Simulasi Waktu (Testing)</h3>
            <p class="text-xs text-yellow-800 dark:text-yellow-300 mt-1 mb-4">
                Set jam di sini untuk menguji absen masuk/pulang tanpa nunggu jam asli — selama simulasi aktif,
                <strong>seluruh portal siswa</strong> (dashboard, tombol absen, riwayat) ikut memakai jam ini,
                bukan jam sekarang. Kosongkan / reset untuk kembali normal.
            </p>

            <div class="flex flex-wrap items-center gap-2 mb-4">
                @if ($pengaturan->simulasi_waktu)
                    <span class="inline-flex items-center gap-2 text-sm text-yellow-900 dark:text-yellow-200 bg-yellow-100 dark:bg-yellow-900/40 rounded-md px-3 py-2">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-500 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-yellow-600"></span>
                        </span>
                        Simulasi aktif: <strong>{{ $pengaturan->simulasi_waktu->format('d/m/Y H:i') }}</strong>
                    </span>
                @endif

                <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium
                             {{ $isLibur ? 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' : 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' }}">
                    <x-icon :name="$isLibur ? 'x-circle' : 'check'" class="w-3 h-3" />
                    Efektif "sekarang" ({{ $efektifSekarang->format('d/m/Y H:i') }}): {{ $isLibur ? 'hari libur, absensi nonaktif' : 'hari sekolah aktif' }}
                </span>
            </div>

            <div class="flex flex-wrap gap-3 items-end">
                <form action="{{ route('pengaturan.simulasi') }}" method="POST" class="flex flex-wrap gap-3 items-end">
                    @csrf @method('PUT')
                    <div>
                        <x-input-label for="simulasi_waktu" value="Tanggal & Jam Simulasi" />
                        <x-text-input id="simulasi_waktu" name="simulasi_waktu" type="datetime-local" class="mt-1 block"
                                      :value="old('simulasi_waktu', $pengaturan->simulasi_waktu?->format('Y-m-d\TH:i'))" />
                    </div>
                    <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium px-4 py-2 rounded-md">
                        Aktifkan Simulasi
                    </button>
                </form>

                <form action="{{ route('pengaturan.simulasi') }}" method="POST">
                    @csrf @method('PUT')
                    <input type="hidden" name="simulasi_waktu" value="">
                    <button type="submit" class="text-sm text-yellow-800 dark:text-yellow-300 hover:underline px-2 py-2">
                        Reset ke Waktu Asli
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mt-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="font-semibold text-gray-800 dark:text-gray-100">Verifikasi Lokasi Absen (GPS)</h3>
                <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium
                             {{ $pengaturan->lokasiAktif() ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                    <x-icon :name="$pengaturan->lokasiAktif() ? 'check' : 'x-circle'" class="w-3 h-3" />
                    {{ $pengaturan->lokasiAktif() ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                Kalau diisi, siswa harus berada dalam radius ini dari titik sekolah saat absen mandiri lewat
                <code>/portal/absen</code>. Kosongkan / nonaktifkan untuk kembali seperti semula (tidak ada
                pengecekan lokasi maupun prompt izin GPS sama sekali).
            </p>

            <form id="form-lokasi" action="{{ route('pengaturan.lokasi') }}" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="lokasi_lat" value="Latitude" />
                        <x-text-input id="lokasi_lat" name="lokasi_lat" type="text" inputmode="decimal" class="mt-1 block w-full"
                                      :value="old('lokasi_lat', $pengaturan->lokasi_lat)" />
                    </div>
                    <div>
                        <x-input-label for="lokasi_lng" value="Longitude" />
                        <x-text-input id="lokasi_lng" name="lokasi_lng" type="text" inputmode="decimal" class="mt-1 block w-full"
                                      :value="old('lokasi_lng', $pengaturan->lokasi_lng)" />
                    </div>
                    <div>
                        <x-input-label for="lokasi_radius_meter" value="Radius (meter)" />
                        <x-text-input id="lokasi_radius_meter" name="lokasi_radius_meter" type="number" min="10" max="5000" class="mt-1 block w-full"
                                      :value="old('lokasi_radius_meter', $pengaturan->lokasi_radius_meter)" />
                    </div>
                </div>
                <div class="flex flex-wrap justify-between items-center gap-3">
                    <button type="button" id="btn-lokasi-sekarang"
                            class="text-sm text-indigo-700 dark:text-indigo-400 hover:underline">
                        Gunakan Lokasi Saat Ini
                    </button>
                    <x-primary-button>Simpan Lokasi</x-primary-button>
                </div>
            </form>

            <form action="{{ route('pengaturan.lokasi') }}" method="POST" class="mt-3">
                @csrf @method('PUT')
                <input type="hidden" name="lokasi_lat" value="">
                <input type="hidden" name="lokasi_lng" value="">
                <input type="hidden" name="lokasi_radius_meter" value="">
                <button type="submit" class="text-sm text-gray-600 dark:text-gray-400 hover:underline px-0 py-1">
                    Nonaktifkan Verifikasi Lokasi
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('btn-lokasi-sekarang')?.addEventListener('click', () => {
            if (!navigator.geolocation) {
                alert('Browser tidak mendukung lokasi GPS.');
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    document.getElementById('lokasi_lat').value = pos.coords.latitude.toFixed(7);
                    document.getElementById('lokasi_lng').value = pos.coords.longitude.toFixed(7);
                },
                () => alert('Gagal mengambil lokasi. Pastikan izin lokasi diaktifkan.'),
                { enableHighAccuracy: true, timeout: 15000 }
            );
        });
    </script>
</x-app-layout>
