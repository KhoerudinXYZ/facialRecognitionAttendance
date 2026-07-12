<?php

namespace Tests\Feature;

use App\Contracts\WhatsAppGateway;
use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\NotifikasiAbsensiLog;
use App\Models\Siswa;
use App\Services\AbsensiAlphaChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AbsensiAlphaCheckerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function siswa(array $overrides = []): Siswa
    {
        $kelas = Kelas::create([
            'nama_kelas' => 'X RPL 1',
            'jurusan' => 'RPL',
            'tingkat' => 'X',
        ]);

        return Siswa::create(array_merge([
            'nis' => '12345',
            'nama' => 'Budi',
            'jenis_kelamin' => 'L',
            'kelas_id' => $kelas->id,
        ], $overrides));
    }

    public function test_siswa_tanpa_absensi_hari_ini_ditandai_alpha_dan_dinotifikasi(): void
    {
        Carbon::setTestNow('2026-07-13 20:00:00');
        $siswa = $this->siswa(['no_hp_orang_tua' => '628123456789']);

        $gateway = \Mockery::mock(WhatsAppGateway::class);
        $gateway->shouldReceive('send')->once()->with('628123456789', \Mockery::type('string'))->andReturn(true);
        $this->app->instance(WhatsAppGateway::class, $gateway);

        $jumlah = app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertSame(1, $jumlah);
        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'status' => 'alpha']);
        $this->assertDatabaseHas('notifikasi_absensi_log', [
            'siswa_id' => $siswa->id,
            'no_hp' => '628123456789',
            'status' => 'terkirim',
        ]);
    }

    public function test_siswa_tanpa_no_hp_orang_tua_tetap_ditandai_alpha_tapi_notifikasi_dicatat_tidak_ada_no_hp(): void
    {
        Carbon::setTestNow('2026-07-13 20:00:00');
        $siswa = $this->siswa();

        $gateway = \Mockery::mock(WhatsAppGateway::class);
        $gateway->shouldNotReceive('send');
        $this->app->instance(WhatsAppGateway::class, $gateway);

        app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'status' => 'alpha']);
        $this->assertDatabaseHas('notifikasi_absensi_log', [
            'siswa_id' => $siswa->id,
            'no_hp' => null,
            'status' => 'tidak_ada_no_hp',
        ]);
    }

    public function test_siswa_yang_sudah_absen_tidak_ditandai_alpha(): void
    {
        Carbon::setTestNow('2026-07-13 20:00:00');
        $siswa = $this->siswa(['no_hp_orang_tua' => '628123456789']);
        Absensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'jam_masuk' => '07:00:00',
            'status' => 'hadir',
            'metode' => 'face',
        ]);

        $gateway = \Mockery::mock(WhatsAppGateway::class);
        $gateway->shouldNotReceive('send');
        $this->app->instance(WhatsAppGateway::class, $gateway);

        $jumlah = app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertSame(0, $jumlah);
        $this->assertSame(1, Absensi::where('siswa_id', $siswa->id)->count());
        $this->assertDatabaseMissing('notifikasi_absensi_log', ['siswa_id' => $siswa->id]);
    }

    public function test_tidak_menandai_alpha_saat_hari_libur(): void
    {
        Carbon::setTestNow('2026-07-13 20:00:00');
        HariLibur::create(['tanggal' => '2026-07-13', 'keterangan' => 'Libur Nasional']);
        $siswa = $this->siswa(['no_hp_orang_tua' => '628123456789']);

        $gateway = \Mockery::mock(WhatsAppGateway::class);
        $gateway->shouldNotReceive('send');
        $this->app->instance(WhatsAppGateway::class, $gateway);

        $jumlah = app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertSame(0, $jumlah);
        $this->assertDatabaseMissing('absensi', ['siswa_id' => $siswa->id]);
    }

    public function test_siswa_nonaktif_tidak_ditandai_alpha(): void
    {
        Carbon::setTestNow('2026-07-13 20:00:00');
        $siswa = $this->siswa(['is_active' => false, 'no_hp_orang_tua' => '628123456789']);

        app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertDatabaseMissing('absensi', ['siswa_id' => $siswa->id]);
    }
}
