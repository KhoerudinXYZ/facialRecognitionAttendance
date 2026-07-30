<?php

namespace Tests\Feature;

use App\Mail\SiswaAlphaMail;
use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\PengajuanIzin;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Services\AbsensiAlphaChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
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
        Mail::assertQueued(SiswaAlphaMail::class, fn ($mail) => $mail->hasTo('ortu@example.com'));
    }

    public function test_siswa_alpha_dinotifikasi_whatsapp_kalau_fonnte_aktif(): void
    {
        Mail::fake();
        config(['services.fonnte.token' => 'test-token']);
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);
        Carbon::setTestNow('2026-07-13 20:00:00');
        $siswa = $this->siswa(['no_hp_orang_tua' => '081234567890']);

        app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertDatabaseHas('notifikasi_absensi_log', [
            'siswa_id' => $siswa->id,
            'jenis' => 'alpha',
            'kanal' => 'whatsapp',
            'kontak' => '6281234567890',
            'status' => 'terkirim',
        ]);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.fonnte.com/send'
            && $request['target'] === '6281234567890');
    }

    public function test_whatsapp_dicoba_ulang_kalau_gagal_sesaat_lalu_berhasil(): void
    {
        Mail::fake();
        config(['services.fonnte.token' => 'test-token']);
        // Percobaan pertama gagal (device Fonnte lagi bermasalah sesaat),
        // percobaan kedua berhasil -- WhatsAppNotifier::kirim() harus
        // mencoba ulang otomatis, bukan langsung menyerah di percobaan
        // pertama.
        Http::fake(['api.fonnte.com/*' => Http::sequence()
            ->push(['status' => false, 'reason' => 'device offline'], 500)
            ->push(['status' => true], 200)]);
        Carbon::setTestNow('2026-07-13 20:00:00');
        $siswa = $this->siswa(['no_hp_orang_tua' => '081234567890']);

        app(AbsensiAlphaChecker::class)->jalankan();

        Http::assertSentCount(2);
        $this->assertDatabaseHas('notifikasi_absensi_log', [
            'siswa_id' => $siswa->id,
            'jenis' => 'alpha',
            'kanal' => 'whatsapp',
            'status' => 'terkirim',
        ]);
    }

    public function test_whatsapp_dicatat_gagal_setelah_semua_percobaan_ulang_habis(): void
    {
        Mail::fake();
        config(['services.fonnte.token' => 'test-token']);
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => false, 'reason' => 'device offline'], 500)]);
        Carbon::setTestNow('2026-07-13 20:00:00');
        $siswa = $this->siswa(['no_hp_orang_tua' => '081234567890']);

        app(AbsensiAlphaChecker::class)->jalankan();

        // retry(3, ...) di WhatsAppNotifier::kirim() -- $times di situ
        // total percobaan (1 awal + 2 ulang), bukan jumlah ulangan
        // tambahan.
        Http::assertSentCount(3);
        $this->assertDatabaseHas('notifikasi_absensi_log', [
            'siswa_id' => $siswa->id,
            'jenis' => 'alpha',
            'kanal' => 'whatsapp',
            'status' => 'gagal',
        ]);
    }

    public function test_notifikasi_alpha_yang_gagal_dicoba_ulang_di_run_berikutnya(): void
    {
        Mail::fake();
        config(['services.fonnte.token' => 'test-token']);
        // 3 respons gagal pertama habis dipakai oleh retry(3,...) internal
        // di run pertama; respons ke-4 (sukses) dipakai run kedua, mensimulasikan
        // Fonnte pulih sebelum cek alpha jam berikutnya.
        Http::fake(['api.fonnte.com/*' => Http::sequence()
            ->push(['status' => false], 500)
            ->push(['status' => false], 500)
            ->push(['status' => false], 500)
            ->push(['status' => true], 200)]);
        Carbon::setTestNow('2026-07-13 20:00:00');
        $siswa = $this->siswa(['no_hp_orang_tua' => '081234567890']);

        $jumlahPertama = app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertSame(1, $jumlahPertama);
        $this->assertSame(1, Absensi::where('siswa_id', $siswa->id)->count());
        $this->assertDatabaseHas('notifikasi_absensi_log', [
            'siswa_id' => $siswa->id,
            'jenis' => 'alpha',
            'status' => 'gagal',
        ]);
        Http::assertSentCount(3);

        // Siswa yang sudah resmi alpha (baris absensi sudah ada) TIDAK
        // ditandai ulang, tapi notifikasinya harus dicoba ulang karena
        // belum pernah berhasil.
        $jumlahKedua = app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertSame(0, $jumlahKedua); // bukan siswa baru yang ditandai alpha
        $this->assertSame(1, Absensi::where('siswa_id', $siswa->id)->count()); // tidak dobel
        $this->assertDatabaseHas('notifikasi_absensi_log', [
            'siswa_id' => $siswa->id,
            'jenis' => 'alpha',
            'status' => 'terkirim',
        ]);
        Http::assertSentCount(4);

        // Run ketiga: sudah berhasil, tidak boleh dikirim ulang lagi.
        app(AbsensiAlphaChecker::class)->jalankan();
        Http::assertSentCount(4);
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
        Mail::assertNothingOutgoing();
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
        Mail::assertNothingOutgoing();
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
        Mail::assertNothingOutgoing();
    }

    public function test_siswa_nonaktif_tidak_ditandai_alpha(): void
    {
        Carbon::setTestNow('2026-07-13 20:00:00');
        $siswa = $this->siswa(['is_active' => false, 'email_orang_tua' => 'ortu@example.com']);

        app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertDatabaseMissing('absensi', ['siswa_id' => $siswa->id]);
    }

    public function test_belum_menandai_alpha_sebelum_mulai_pulang(): void
    {
        Mail::fake();
        Pengaturan::get()->update(['mulai_pulang' => '13:00']);
        Carbon::setTestNow('2026-07-13 12:59:00');
        $siswa = $this->siswa(['email_orang_tua' => 'ortu@example.com']);

        $jumlah = app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertSame(0, $jumlah);
        $this->assertDatabaseMissing('absensi', ['siswa_id' => $siswa->id]);
        Mail::assertNothingOutgoing();
    }

    public function test_menandai_alpha_tepat_saat_mulai_pulang(): void
    {
        Mail::fake();
        Pengaturan::get()->update(['mulai_pulang' => '13:00']);
        // Sama persis dengan titik AbsensiRecorder menutup absen masuk —
        // tidak ada jam tunggu tambahan lagi (lihat AbsensiRecorder::record()).
        Carbon::setTestNow('2026-07-13 13:00:00');
        $siswa = $this->siswa(['email_orang_tua' => 'ortu@example.com']);

        $jumlah = app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertSame(1, $jumlah);
        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'status' => 'alpha']);
    }

    public function test_siswa_dengan_pengajuan_izin_menunggu_tidak_ditandai_alpha(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-13 20:00:00');
        $siswa = $this->siswa(['email_orang_tua' => 'ortu@example.com']);
        PengajuanIzin::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'jenis' => 'sakit',
            'keterangan' => 'Demam',
            'bukti' => 'bukti-izin/surat.jpg',
            'status' => 'menunggu',
        ]);

        $jumlah = app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertSame(0, $jumlah);
        $this->assertDatabaseMissing('absensi', ['siswa_id' => $siswa->id]);
        Mail::assertNothingOutgoing();
    }

    public function test_siswa_dengan_pengajuan_izin_ditolak_tetap_ditandai_alpha(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-13 20:00:00');
        $siswa = $this->siswa(['email_orang_tua' => 'ortu@example.com']);
        PengajuanIzin::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'jenis' => 'sakit',
            'keterangan' => 'Demam',
            'bukti' => 'bukti-izin/surat.jpg',
            'status' => 'ditolak',
        ]);

        $jumlah = app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertSame(1, $jumlah);
        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'status' => 'alpha']);
    }

    public function test_email_tidak_ikut_terkirim_kalau_whatsapp_berhasil_dipakai(): void
    {
        Mail::fake();
        config(['services.fonnte.token' => 'test-token']);
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);
        Carbon::setTestNow('2026-07-13 20:00:00');
        $siswa = $this->siswa([
            'no_hp_orang_tua' => '081234567890',
            'email_orang_tua' => 'ortu@example.com',
        ]);

        app(AbsensiAlphaChecker::class)->jalankan();

        $this->assertSame(1, \App\Models\NotifikasiAbsensiLog::where('siswa_id', $siswa->id)->where('jenis', 'alpha')->count());
        $this->assertDatabaseHas('notifikasi_absensi_log', [
            'siswa_id' => $siswa->id,
            'jenis' => 'alpha',
            'kanal' => 'whatsapp',
            'status' => 'terkirim',
        ]);
        Mail::assertNothingOutgoing();
    }

    public function test_email_tetap_terkirim_kalau_siswa_tidak_punya_nomor_wa_walau_kanal_wa_aktif(): void
    {
        Mail::fake();
        config(['services.fonnte.token' => 'test-token']);
        Http::fake();
        Carbon::setTestNow('2026-07-13 20:00:00');
        $siswa = $this->siswa(['email_orang_tua' => 'ortu@example.com']);

        app(AbsensiAlphaChecker::class)->jalankan();

        Mail::assertQueued(SiswaAlphaMail::class, fn ($mail) => $mail->hasTo('ortu@example.com'));
        $this->assertDatabaseHas('notifikasi_absensi_log', [
            'siswa_id' => $siswa->id,
            'jenis' => 'alpha',
            'kanal' => 'email',
            'status' => 'terkirim',
        ]);
        Http::assertNothingSent();
    }
}
