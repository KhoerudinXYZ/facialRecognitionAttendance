<?php

namespace Tests\Feature;

use App\Mail\SiswaBelumHadirMail;
use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\PengajuanIzin;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Services\AbsensiBelumHadirChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AbsensiBelumHadirCheckerTest extends TestCase
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

    public function test_nonaktif_kalau_jam_cek_belum_hadir_kosong(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-13 10:00:00');
        $this->siswa(['email_orang_tua' => 'ortu@example.com']);

        $jumlah = app(AbsensiBelumHadirChecker::class)->jalankan();

        $this->assertSame(0, $jumlah);
        $this->assertDatabaseMissing('notifikasi_absensi_log', ['jenis' => 'belum_hadir']);
        Mail::assertNothingOutgoing();
    }

    public function test_siswa_belum_absen_dinotifikasi_setelah_jam_cek(): void
    {
        Mail::fake();
        Pengaturan::get()->update(['jam_cek_belum_hadir' => '09:30']);
        Carbon::setTestNow('2026-07-13 09:30:00');
        $siswa = $this->siswa(['email_orang_tua' => 'ortu@example.com']);

        $jumlah = app(AbsensiBelumHadirChecker::class)->jalankan();

        $this->assertSame(1, $jumlah);
        $this->assertDatabaseHas('notifikasi_absensi_log', [
            'siswa_id' => $siswa->id,
            'jenis' => 'belum_hadir',
            'kontak' => 'ortu@example.com',
            'status' => 'terkirim',
        ]);
        // Beda dari alpha: TIDAK menulis baris absensi, siswa mungkin masih
        // dalam perjalanan.
        $this->assertDatabaseMissing('absensi', ['siswa_id' => $siswa->id]);
        Mail::assertQueued(SiswaBelumHadirMail::class, fn ($mail) => $mail->hasTo('ortu@example.com'));
    }

    public function test_belum_dinotifikasi_sebelum_jam_cek(): void
    {
        Mail::fake();
        Pengaturan::get()->update(['jam_cek_belum_hadir' => '09:30']);
        Carbon::setTestNow('2026-07-13 09:29:00');
        $this->siswa(['email_orang_tua' => 'ortu@example.com']);

        $jumlah = app(AbsensiBelumHadirChecker::class)->jalankan();

        $this->assertSame(0, $jumlah);
        Mail::assertNothingOutgoing();
    }

    public function test_tidak_dikirim_ulang_kalau_dijalankan_dua_kali(): void
    {
        Mail::fake();
        Pengaturan::get()->update(['jam_cek_belum_hadir' => '09:30']);
        Carbon::setTestNow('2026-07-13 09:30:00');
        $siswa = $this->siswa(['email_orang_tua' => 'ortu@example.com']);

        app(AbsensiBelumHadirChecker::class)->jalankan();
        $jumlahKeduaKali = app(AbsensiBelumHadirChecker::class)->jalankan();

        $this->assertSame(0, $jumlahKeduaKali);
        $this->assertSame(
            1,
            \App\Models\NotifikasiAbsensiLog::where('siswa_id', $siswa->id)->where('jenis', 'belum_hadir')->count()
        );
        Mail::assertQueued(SiswaBelumHadirMail::class, 1);
    }

    public function test_siswa_yang_sudah_absen_tidak_dinotifikasi(): void
    {
        Mail::fake();
        Pengaturan::get()->update(['jam_cek_belum_hadir' => '09:30']);
        Carbon::setTestNow('2026-07-13 09:30:00');
        $siswa = $this->siswa(['email_orang_tua' => 'ortu@example.com']);
        Absensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'jam_masuk' => '07:00:00',
            'status' => 'hadir',
            'metode' => 'face',
        ]);

        $jumlah = app(AbsensiBelumHadirChecker::class)->jalankan();

        $this->assertSame(0, $jumlah);
        Mail::assertNothingOutgoing();
    }

    public function test_siswa_dengan_pengajuan_izin_menunggu_tidak_dinotifikasi(): void
    {
        Mail::fake();
        Pengaturan::get()->update(['jam_cek_belum_hadir' => '09:30']);
        Carbon::setTestNow('2026-07-13 09:30:00');
        $siswa = $this->siswa(['email_orang_tua' => 'ortu@example.com']);
        PengajuanIzin::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'jenis' => 'sakit',
            'keterangan' => 'Demam',
            'bukti' => 'bukti-izin/surat.jpg',
            'status' => 'menunggu',
        ]);

        $jumlah = app(AbsensiBelumHadirChecker::class)->jalankan();

        $this->assertSame(0, $jumlah);
        Mail::assertNothingOutgoing();
    }

    public function test_tidak_menotifikasi_saat_hari_libur(): void
    {
        Mail::fake();
        Pengaturan::get()->update(['jam_cek_belum_hadir' => '09:30']);
        Carbon::setTestNow('2026-07-13 09:30:00');
        HariLibur::create(['tanggal' => '2026-07-13', 'keterangan' => 'Libur Nasional']);
        $this->siswa(['email_orang_tua' => 'ortu@example.com']);

        $jumlah = app(AbsensiBelumHadirChecker::class)->jalankan();

        $this->assertSame(0, $jumlah);
        Mail::assertNothingOutgoing();
    }

    public function test_siswa_nonaktif_tidak_dinotifikasi(): void
    {
        Mail::fake();
        Pengaturan::get()->update(['jam_cek_belum_hadir' => '09:30']);
        Carbon::setTestNow('2026-07-13 09:30:00');
        $this->siswa(['is_active' => false, 'email_orang_tua' => 'ortu@example.com']);

        $jumlah = app(AbsensiBelumHadirChecker::class)->jalankan();

        $this->assertSame(0, $jumlah);
        Mail::assertNothingOutgoing();
    }

    public function test_siswa_dinotifikasi_whatsapp_kalau_fonnte_aktif(): void
    {
        Mail::fake();
        config(['services.fonnte.token' => 'test-token']);
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);
        Pengaturan::get()->update(['jam_cek_belum_hadir' => '09:30']);
        Carbon::setTestNow('2026-07-13 09:30:00');
        $this->siswa(['no_hp_orang_tua' => '081234567890']);

        app(AbsensiBelumHadirChecker::class)->jalankan();

        $this->assertDatabaseHas('notifikasi_absensi_log', [
            'jenis' => 'belum_hadir',
            'kanal' => 'whatsapp',
            'kontak' => '6281234567890',
            'status' => 'terkirim',
        ]);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.fonnte.com/send'
            && $request['target'] === '6281234567890');
    }

    public function test_email_tidak_ikut_terkirim_kalau_whatsapp_berhasil_dipakai(): void
    {
        Mail::fake();
        config(['services.fonnte.token' => 'test-token']);
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);
        Pengaturan::get()->update(['jam_cek_belum_hadir' => '09:30']);
        Carbon::setTestNow('2026-07-13 09:30:00');
        $siswa = $this->siswa([
            'no_hp_orang_tua' => '081234567890',
            'email_orang_tua' => 'ortu@example.com',
        ]);

        app(AbsensiBelumHadirChecker::class)->jalankan();

        $this->assertSame(1, \App\Models\NotifikasiAbsensiLog::where('siswa_id', $siswa->id)->where('jenis', 'belum_hadir')->count());
        Mail::assertNothingOutgoing();
    }

    public function test_email_tetap_terkirim_kalau_siswa_tidak_punya_nomor_wa_walau_kanal_wa_aktif(): void
    {
        Mail::fake();
        config(['services.fonnte.token' => 'test-token']);
        Http::fake();
        Pengaturan::get()->update(['jam_cek_belum_hadir' => '09:30']);
        Carbon::setTestNow('2026-07-13 09:30:00');
        $siswa = $this->siswa(['email_orang_tua' => 'ortu@example.com']);

        app(AbsensiBelumHadirChecker::class)->jalankan();

        Mail::assertQueued(SiswaBelumHadirMail::class, fn ($mail) => $mail->hasTo('ortu@example.com'));
        $this->assertDatabaseHas('notifikasi_absensi_log', [
            'siswa_id' => $siswa->id,
            'jenis' => 'belum_hadir',
            'kanal' => 'email',
            'status' => 'terkirim',
        ]);
        Http::assertNothingSent();
    }
}
