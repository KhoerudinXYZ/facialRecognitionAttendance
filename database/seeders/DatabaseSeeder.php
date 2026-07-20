<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Akun admin default
        User::updateOrCreate(
            ['email' => 'admin@smk.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Kelas contoh, masing-masing dengan akun wali kelas sendiri
        $kelasContoh = [
            ['nama_kelas' => 'X RPL 1', 'jurusan' => 'Rekayasa Perangkat Lunak', 'tingkat' => 'X', 'wali_nama' => 'Budi Santoso', 'wali_email' => 'budi@smk.test'],
            ['nama_kelas' => 'XI TKJ 1', 'jurusan' => 'Teknik Komputer Jaringan', 'tingkat' => 'XI', 'wali_nama' => 'Siti Aminah', 'wali_email' => 'siti@smk.test'],
            ['nama_kelas' => 'XII MM 1', 'jurusan' => 'Multimedia', 'tingkat' => 'XII', 'wali_nama' => 'Andi Wijaya', 'wali_email' => 'andi@smk.test'],
        ];

        foreach ($kelasContoh as $k) {
            $wali = User::updateOrCreate(
                ['email' => $k['wali_email']],
                ['name' => $k['wali_nama'], 'password' => Hash::make('password'), 'role' => 'wali_kelas']
            );

            Kelas::firstOrCreate(
                ['nama_kelas' => $k['nama_kelas']],
                ['jurusan' => $k['jurusan'], 'tingkat' => $k['tingkat'], 'wali_kelas_id' => $wali->id]
            );
        }

        // Pengaturan default
        Pengaturan::get();
    }
}
