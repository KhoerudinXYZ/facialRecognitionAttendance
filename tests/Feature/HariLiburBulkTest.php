<?php

namespace Tests\Feature;

use App\Models\HariLibur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HariLiburBulkTest extends TestCase
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

    public function test_halaman_index_dengan_kalender_bisa_diakses(): void
    {
        $this->actingAs($this->admin());
        HariLibur::create(['tanggal' => '2026-01-01', 'keterangan' => 'Tahun Baru']);

        $this->get('/hari-libur')->assertOk()->assertSee('Pilih Tanggal Merah');
        $this->get('/hari-libur?tahun=2026')->assertOk()->assertSee('2026');
        $this->get('/hari-libur?tahun=99999')->assertOk();
    }

    public function test_tanggal_tersebar_ditambahkan_semua(): void
    {
        $this->actingAs($this->admin());

        $this->post('/hari-libur/bulk', [
            'tanggal' => ['2026-01-01', '2026-05-01', '2026-08-17', '2026-12-25'],
            'keterangan' => 'Hari libur nasional',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(4, HariLibur::count());
        $this->assertSame('Hari libur nasional', HariLibur::whereDate('tanggal', '2026-08-17')->value('keterangan'));
    }

    public function test_tanggal_yang_sudah_ada_dilewati(): void
    {
        $this->actingAs($this->admin());
        HariLibur::create(['tanggal' => '2026-01-01', 'keterangan' => 'Tahun Baru']);

        $this->post('/hari-libur/bulk', [
            'tanggal' => ['2026-01-01', '2026-05-01'],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(2, HariLibur::count());
        $this->assertSame('Tahun Baru', HariLibur::whereDate('tanggal', '2026-01-01')->value('keterangan'));
    }

    public function test_tanggal_duplikat_dalam_satu_request_tidak_dobel(): void
    {
        $this->actingAs($this->admin());

        $this->post('/hari-libur/bulk', [
            'tanggal' => ['2026-03-10', '2026-03-10'],
        ])->assertRedirect();

        $this->assertSame(1, HariLibur::count());
    }

    public function test_tanggal_kosong_ditolak(): void
    {
        $this->actingAs($this->admin());

        $this->post('/hari-libur/bulk', [
            'tanggal' => [],
        ])->assertSessionHasErrors('tanggal');

        $this->assertSame(0, HariLibur::count());
    }

    public function test_tanggal_format_invalid_ditolak(): void
    {
        $this->actingAs($this->admin());

        $this->post('/hari-libur/bulk', [
            'tanggal' => ['bukan-tanggal'],
        ])->assertSessionHasErrors();

        $this->assertSame(0, HariLibur::count());
    }
}
