@php $siswa = $siswa ?? null; @endphp
<div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
    <div>
        <x-input-label for="nis" value="NIS" />
        <x-text-input id="nis" name="nis" type="text" class="mt-1 block w-full"
                      :value="old('nis', $siswa->nis ?? '')" required />
    </div>
    <div>
        <x-input-label for="nisn" value="NISN (opsional)" />
        <x-text-input id="nisn" name="nisn" type="text" class="mt-1 block w-full"
                      :value="old('nisn', $siswa->nisn ?? '')" />
    </div>
    <div>
        <x-input-label for="email_orang_tua" value="Email Orang Tua (opsional)" />
        <x-text-input id="email_orang_tua" name="email_orang_tua" type="email" class="mt-1 block w-full"
                      placeholder="orangtua@email.com" :value="old('email_orang_tua', $siswa->email_orang_tua ?? '')" />
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Dipakai untuk notifikasi otomatis saat siswa alpha. Kosongkan kalau belum ada.</p>
    </div>
    <div>
        <x-input-label for="no_hp_orang_tua" value="No. WhatsApp Orang Tua (opsional, belum dipakai)" />
        <x-text-input id="no_hp_orang_tua" name="no_hp_orang_tua" type="text" class="mt-1 block w-full"
                      placeholder="628123456789" :value="old('no_hp_orang_tua', $siswa->no_hp_orang_tua ?? '')" />
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Disimpan untuk dipakai nanti kalau notifikasi WhatsApp diaktifkan kembali.</p>
    </div>
    <div class="sm:col-span-2">
        <x-input-label for="nama" value="Nama Lengkap" />
        <x-text-input id="nama" name="nama" type="text" class="mt-1 block w-full"
                      :value="old('nama', $siswa->nama ?? '')" required />
    </div>
    <div>
        <x-input-label for="jenis_kelamin" value="Jenis Kelamin" />
        <select id="jenis_kelamin" name="jenis_kelamin" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
            <option value="L" @selected(old('jenis_kelamin', $siswa->jenis_kelamin ?? 'L') === 'L')>Laki-laki</option>
            <option value="P" @selected(old('jenis_kelamin', $siswa->jenis_kelamin ?? '') === 'P')>Perempuan</option>
        </select>
    </div>
    <div>
        <x-input-label for="kelas_id" value="Kelas" />
        <select id="kelas_id" name="kelas_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100" required>
            <option value="">-- Pilih Kelas --</option>
            @foreach ($kelasList as $k)
                <option value="{{ $k->id }}" @selected(old('kelas_id', $siswa->kelas_id ?? '') == $k->id)>
                    {{ $k->nama_kelas }}{{ $k->waliKelas ? ' — wali ' . $k->waliKelas->name : '' }}
                </option>
            @endforeach
        </select>
        @if ($siswa?->kelas?->waliKelas)
            <p class="mt-1.5 text-xs text-yellow-700 dark:text-yellow-400">
                Saat ini di kelas {{ $siswa->kelas->nama_kelas }}, diampu {{ $siswa->kelas->waliKelas->name }}. Memindahkan
                ke kelas lain akan langsung menghilangkan siswa ini dari dashboard & pengingat {{ $siswa->kelas->waliKelas->name }}.
            </p>
        @endif
    </div>
    <div class="sm:col-span-2">
        <x-input-label for="foto" value="Foto (opsional)" />
        <input id="foto" name="foto" type="file" accept="image/*"
               class="mt-1 block w-full text-sm text-gray-600 dark:text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-900/40 dark:file:text-indigo-300 hover:file:bg-indigo-100" />
        @if (!empty($siswa?->foto))
            <img src="{{ Storage::url($siswa->foto) }}" class="mt-2 h-20 w-20 object-cover rounded" alt="foto">
        @endif
    </div>
    <div class="sm:col-span-2">
        <label class="inline-flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                   @checked(old('is_active', $siswa->is_active ?? true))>
            <span class="text-sm text-gray-700 dark:text-gray-300">Siswa aktif</span>
        </label>
    </div>
</div>
