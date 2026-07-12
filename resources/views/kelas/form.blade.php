@php $kelas = $kelas ?? null; @endphp
<div class="space-y-5">
    <div>
        <x-input-label for="nama_kelas" value="Nama Kelas" />
        <x-text-input id="nama_kelas" name="nama_kelas" type="text" class="mt-1 block w-full"
                      :value="old('nama_kelas', $kelas->nama_kelas ?? '')" required placeholder="mis. X RPL 1" />
    </div>

    <div>
        <x-input-label for="tingkat" value="Tingkat" />
        <select id="tingkat" name="tingkat" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
            @foreach (['X', 'XI', 'XII'] as $t)
                <option value="{{ $t }}" @selected(old('tingkat', $kelas->tingkat ?? 'X') === $t)>{{ $t }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <x-input-label for="jurusan" value="Jurusan" />
        <x-text-input id="jurusan" name="jurusan" type="text" class="mt-1 block w-full"
                      :value="old('jurusan', $kelas->jurusan ?? '')" placeholder="mis. Rekayasa Perangkat Lunak" />
    </div>

    <div>
        <x-input-label for="wali_kelas_id" value="Wali Kelas" />
        <select id="wali_kelas_id" name="wali_kelas_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
            <option value="">-- Belum ditentukan --</option>
            @foreach ($waliKelasList as $wali)
                @php $kelasLain = $wali->kelasBinaan->reject(fn ($k) => $kelas && $k->id === $kelas->id); @endphp
                <option value="{{ $wali->id }}" @selected((int) old('wali_kelas_id', $kelas->wali_kelas_id ?? '') === $wali->id)>
                    {{ $wali->name }}{{ $kelasLain->isNotEmpty() ? ' — sudah wali ' . $kelasLain->pluck('nama_kelas')->join(', ') : '' }}
                </option>
            @endforeach
        </select>
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
            Nama yang diikuti "sudah wali ..." berarti orang itu sudah menjadi wali kelas lain — memilihnya di sini akan membuatnya mengelola lebih dari satu kelas sekaligus (dashboard & pengingatnya otomatis menggabungkan semua kelas binaan).
        </p>
        @if ($kelas?->waliKelas)
            <p class="mt-1.5 text-xs text-yellow-700 dark:text-yellow-400">
                Kelas ini saat ini diampu oleh {{ $kelas->waliKelas->name }}. Mengganti atau mengosongkan pilihan di atas
                akan langsung menghilangkan kelas ini dari dashboard & pengingat {{ $kelas->waliKelas->name }}.
            </p>
        @endif
    </div>
</div>
