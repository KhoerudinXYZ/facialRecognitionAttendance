<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffUserControllerTest extends TestCase
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

    public function test_ganti_role_wali_kelas_ke_admin_melepas_kelas_binaannya(): void
    {
        $this->actingAs($this->admin());

        $wali = User::create([
            'name' => 'Wali Kelas',
            'email' => 'wali@test.test',
            'password' => Hash::make('password'),
            'role' => 'wali_kelas',
        ]);
        $kelas = Kelas::create([
            'nama_kelas' => 'X RPL 1', 'jurusan' => 'RPL', 'tingkat' => 'X', 'wali_kelas_id' => $wali->id,
        ]);

        $this->put("/staff/{$wali->id}", [
            'name' => $wali->name,
            'email' => $wali->email,
            'role' => 'admin',
        ])->assertRedirect(route('staff.index'));

        // Kelas yang tadinya dia bina tidak boleh tetap menunjuk ke akun
        // yang sekarang admin -- referensi basi, tidak muncul lagi di
        // pemilihan wali kelas manapun.
        $this->assertDatabaseHas('kelas', ['id' => $kelas->id, 'wali_kelas_id' => null]);
        $this->assertDatabaseHas('users', ['id' => $wali->id, 'role' => 'admin']);
    }

    public function test_ganti_data_lain_tanpa_ubah_role_tidak_menyentuh_kelas_binaan(): void
    {
        $this->actingAs($this->admin());

        $wali = User::create([
            'name' => 'Wali Kelas',
            'email' => 'wali@test.test',
            'password' => Hash::make('password'),
            'role' => 'wali_kelas',
        ]);
        $kelas = Kelas::create([
            'nama_kelas' => 'X RPL 1', 'jurusan' => 'RPL', 'tingkat' => 'X', 'wali_kelas_id' => $wali->id,
        ]);

        $this->put("/staff/{$wali->id}", [
            'name' => 'Wali Kelas Baru',
            'email' => $wali->email,
            'role' => 'wali_kelas',
        ])->assertRedirect(route('staff.index'));

        $this->assertDatabaseHas('kelas', ['id' => $kelas->id, 'wali_kelas_id' => $wali->id]);
    }
}
