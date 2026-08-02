<?php

namespace Tests\Feature;

use App\Models\Pengaturan;
use Tests\TestCase;

/**
 * Pengaturan::ipCocok() dites lewat instance model langsung (bukan
 * Pengaturan::get()) -- method ini murni baca atribut ip_sekolah, tidak
 * perlu hit DB sama sekali.
 */
class PengaturanIpCocokTest extends TestCase
{
    public function test_ip_persis_sama_cocok(): void
    {
        $pengaturan = new Pengaturan(['ip_sekolah' => '36.85.12.40']);

        $this->assertTrue($pengaturan->ipCocok('36.85.12.40'));
    }

    public function test_ip_di_dalam_cidr_cocok(): void
    {
        $pengaturan = new Pengaturan(['ip_sekolah' => '114.79.0.0/16']);

        $this->assertTrue($pengaturan->ipCocok('114.79.20.5'));
    }

    public function test_ip_di_luar_daftar_tidak_cocok(): void
    {
        $pengaturan = new Pengaturan(['ip_sekolah' => '36.85.12.40, 114.79.0.0/16']);

        $this->assertFalse($pengaturan->ipCocok('182.1.1.1'));
    }

    public function test_daftar_kosong_null_bukan_false(): void
    {
        $pengaturan = new Pengaturan(['ip_sekolah' => null]);

        $this->assertNull($pengaturan->ipCocok('36.85.12.40'));
        $this->assertFalse($pengaturan->ipSekolahAktif());
    }

    public function test_ip_tidak_diketahui_null_bukan_false(): void
    {
        $pengaturan = new Pengaturan(['ip_sekolah' => '36.85.12.40']);

        $this->assertNull($pengaturan->ipCocok(null));
    }

    public function test_cidr_salah_format_tidak_cocok_bukan_error(): void
    {
        $pengaturan = new Pengaturan(['ip_sekolah' => 'bukan-ip/16']);

        $this->assertFalse($pengaturan->ipCocok('36.85.12.40'));
    }
}
