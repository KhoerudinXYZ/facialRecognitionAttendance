<?php

namespace Tests\Feature;

use App\Models\Pengaturan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PengaturanControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@test.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nama_sekolah' => 'SMKN 1 SINDANG',
            'jam_masuk' => '07:00',
            'batas_terlambat' => '08:00',
            'mulai_pulang' => '13:00',
        ], $overrides);
    }

    public function test_jam_cek_belum_hadir_tersimpan_lewat_request_asli(): void
    {
        $this->actingAs($this->admin());

        $this->put('/pengaturan', $this->payload(['jam_cek_belum_hadir' => '09:30']))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('09:30', substr(Pengaturan::get()->jam_cek_belum_hadir, 0, 5));
        $this->assertTrue(Pengaturan::get()->cekBelumHadirAktif());
    }

    public function test_jam_cek_belum_hadir_kosong_menonaktifkan_fitur(): void
    {
        $this->actingAs($this->admin());
        Pengaturan::get()->update(['jam_cek_belum_hadir' => '09:30']);

        // Field kosong (bukan tidak dikirim sama sekali) -- ini yang benar-
        // benar terjadi lewat form HTML kalau admin mengosongkan input time.
        $this->put('/pengaturan', $this->payload(['jam_cek_belum_hadir' => '']))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNull(Pengaturan::get()->jam_cek_belum_hadir);
        $this->assertFalse(Pengaturan::get()->cekBelumHadirAktif());
    }
}
