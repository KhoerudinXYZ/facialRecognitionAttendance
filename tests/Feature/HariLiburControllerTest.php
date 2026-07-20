<?php

namespace Tests\Feature;

use App\Models\HariLibur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HariLiburControllerTest extends TestCase
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

    public function test_rentang_wajar_ditambahkan_semua(): void
    {
        $this->actingAs($this->admin());

        $this->post('/hari-libur', [
            'dari' => '2026-12-22',
            'sampai' => '2026-12-31',
            'keterangan' => 'Libur akhir semester',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(10, HariLibur::count());
    }

    public function test_rentang_lebih_dari_setahun_ditolak(): void
    {
        $this->actingAs($this->admin());

        // Salah ketik tahun (2026 -> 2062) -- tanpa batas ini memicu
        // puluhan ribu insert satu per satu dalam satu request.
        $this->post('/hari-libur', [
            'dari' => '2026-01-01',
            'sampai' => '2062-12-31',
            'keterangan' => 'Typo tahun',
        ])->assertSessionHasErrors('sampai');

        $this->assertSame(0, HariLibur::count());
    }
}
