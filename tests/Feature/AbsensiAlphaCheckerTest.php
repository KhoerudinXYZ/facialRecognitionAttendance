<?php

namespace Tests\Feature;

use App\Mail\SiswaAlphaMail;
use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Services\AbsensiAlphaChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
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
        Mail::fake();
        Carbon::setTestNow('2026-07-13 20:00:00');
        $siswa = $this->siswa(['email_orang_tua' => 'ortu@example.com']);

        $jumlah = app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertSame(1, $jumlah);
        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'status' => 'alpha']);
        $this->assertDatabaseHas('notifikasi_absensi_log', [
            'siswa_id' => $siswa->id,
            'kontak' => 'ortu@example.com',
            'status' => 'terkirim',
        ]);
        Mail::assertSent(SiswaAlphaMail::class, fn ($mail) => $mail->hasTo('ortu@example.com'));
    }

    public function test_siswa_tanpa_email_orang_tua_tetap_ditandai_alpha_tapi_notifikasi_dicatat_tidak_ada_kontak(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-13 20:00:00');
        $siswa = $this->siswa();

        app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'status' => 'alpha']);
        $this->assertDatabaseHas('notifikasi_absensi_log', [
            'siswa_id' => $siswa->id,
            'kontak' => null,
            'status' => 'tidak_ada_kontak',
        ]);
        Mail::assertNothingSent();
    }

    public function test_siswa_yang_sudah_absen_tidak_ditandai_alpha(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-13 20:00:00');
        $siswa = $this->siswa(['email_orang_tua' => 'ortu@example.com']);
        Absensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'jam_masuk' => '07:00:00',
            'status' => 'hadir',
            'metode' => 'face',
        ]);

        $jumlah = app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertSame(0, $jumlah);
        $this->assertSame(1, Absensi::where('siswa_id', $siswa->id)->count());
        $this->assertDatabaseMissing('notifikasi_absensi_log', ['siswa_id' => $siswa->id]);
        Mail::assertNothingSent();
    }

    public function test_tidak_menandai_alpha_saat_hari_libur(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-13 20:00:00');
        HariLibur::create(['tanggal' => '2026-07-13', 'keterangan' => 'Libur Nasional']);
        $siswa = $this->siswa(['email_orang_tua' => 'ortu@example.com']);

        $jumlah = app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertSame(0, $jumlah);
        $this->assertDatabaseMissing('absensi', ['siswa_id' => $siswa->id]);
        Mail::assertNothingSent();
    }

    public function test_siswa_nonaktif_tidak_ditandai_alpha(): void
    {
        Carbon::setTestNow('2026-07-13 20:00:00');
        $siswa = $this->siswa(['is_active' => false, 'email_orang_tua' => 'ortu@example.com']);

        app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertDatabaseMissing('absensi', ['siswa_id' => $siswa->id]);
    }

    public function test_belum_menandai_alpha_sebelum_jam_tunggu_setelah_mulai_pulang(): void
    {
        Mail::fake();
        Pengaturan::get()->update(['mulai_pulang' => '13:00']);
        // Baru 1 jam setelah mulai_pulang (jam tunggunya 2 jam) -> masih terlalu awal.
        Carbon::setTestNow('2026-07-13 14:00:00');
        $siswa = $this->siswa(['email_orang_tua' => 'ortu@example.com']);

        $jumlah = app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertSame(0, $jumlah);
        $this->assertDatabaseMissing('absensi', ['siswa_id' => $siswa->id]);
        Mail::assertNothingSent();
    }

    public function test_menandai_alpha_tepat_setelah_jam_tunggu_terlewati(): void
    {
        Mail::fake();
        Pengaturan::get()->update(['mulai_pulang' => '13:00']);
        // Tepat 2 jam setelah mulai_pulang -> sudah boleh jalan.
        Carbon::setTestNow('2026-07-13 15:00:00');
        $siswa = $this->siswa(['email_orang_tua' => 'ortu@example.com']);

        $jumlah = app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertSame(1, $jumlah);
        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'status' => 'alpha']);
    }
}
