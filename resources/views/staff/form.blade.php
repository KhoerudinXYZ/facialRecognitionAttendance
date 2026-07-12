@php $staff = $staff ?? null; @endphp
<div class="space-y-5">
    <div>
        <x-input-label for="name" value="Nama" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      :value="old('name', $staff->name ?? '')" required />
    </div>

    <div>
        <x-input-label for="email" value="Email" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                      :value="old('email', $staff->email ?? '')" required />
    </div>

    <div>
        <x-input-label for="role" value="Peran" />
        <select id="role" name="role" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
            <option value="admin" @selected(old('role', $staff->role ?? 'wali_kelas') === 'admin')>Admin</option>
            <option value="wali_kelas" @selected(old('role', $staff->role ?? 'wali_kelas') === 'wali_kelas')>Wali Kelas</option>
        </select>
        @if ($staff && $staff->role === 'wali_kelas' && $staff->kelasBinaan->isNotEmpty())
            <p class="mt-1.5 text-xs text-yellow-700 dark:text-yellow-400">
                Saat ini wali kelas {{ $staff->kelasBinaan->pluck('nama_kelas')->join(', ') }}. Mengubah peran ke Admin
                tidak otomatis melepas kelas ini — lepaskan dulu lewat halaman Kelas kalau memang ingin diserahkan ke orang lain.
            </p>
        @endif
    </div>

    <div>
        <x-input-label for="password" :value="$staff ? 'Password Baru (kosongkan jika tidak diubah)' : 'Password'" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" :required="! $staff" />
    </div>

    <div>
        <x-input-label for="password_confirmation" value="Konfirmasi Password" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" :required="! $staff" />
    </div>
</div>
