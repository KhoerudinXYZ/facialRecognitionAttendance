<?php

namespace Tests\Feature;

use App\Mail\SiswaHadirMail;
use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Services\AbsensiRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AbsensiRecorderTest extends TestCase
{
    use RefreshDatabase;

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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Absensi::flushEventListeners();
        parent::tearDown();
    }

    public function test_scan_pertama_tercatat_sebagai_absen_masuk(): void
    {
        Pengaturan::get()->update(['batas_terlambat' => '08:00', 'mulai_pulang' => '13:00']);
        Carbon::setTestNow('2026-07-13 07:00:00');

        $result = app(AbsensiRecorder::class)->record($this->siswa());

        $this->assertSame('success', $result['status']);
        $this->assertSame('hadir', $result['keterangan']);
        $this->assertDatabaseHas('absensi', ['jam_masuk' => '07:00:00', 'jam_pulang' => null, 'status' => 'hadir']);
    }

    public function test_race_dua_scan_pertama_hampir_bersamaan_tidak_error_500(): void
    {
        Pengaturan::get()->update(['batas_terlambat' => '08:00', 'mulai_pulang' => '13:00']);
        Carbon::setTestNow('2026-07-13 07:00:00');
        $siswa = $this->siswa();

        // Simulasikan request lain yang menang race: baris pesaing masuk
        // (insert mentah, bukan lewat AbsensiRecorder) TEPAT di antara
        // pengecekan $existing === null milik record() dan INSERT-nya
        // sendiri -- momen paling sempit yang tidak bisa dipicu lewat dua
        // request sungguhan dalam test yang berjalan satu proses/thread.
        Absensi::creating(function () use ($siswa) {
            // "2026-07-13 00:00:00", bukan "2026-07-13" -- Eloquent's 'date'
            // cast menormalisasi ke datetime penuh saat disimpan, jadi kolom
            // tanggal harus sama persis biar constraint unik siswa+tanggal
            // benar-benar ketabrak (lihat komentar di AbsensiController::manual
            // soal jebakan format yang sama).
            DB::table('absensi')->insert([
                'siswa_id' => $siswa->id,
                'kelas_id' => $siswa->kelas_id,
                'tanggal' => '2026-07-13 00:00:00',
                'jam_masuk' => '07:00:01',
                'status' => 'hadir',
                'metode' => 'face',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $result = app(AbsensiRecorder::class)->record($siswa);

        // Yang kalah race harus dapat pesan "sudah absen" yang wajar, bukan
        // exception 500 dari constraint unik siswa_id+tanggal yang gagal.
        $this->assertSame('already', $result['status']);
        $this->assertSame(1, Absensi::where('siswa_id', $siswa->id)->count());
        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'jam_masuk' => '07:00:01']);
    }

    public function test_scan_kedua_sebelum_mulai_pulang_dianggap_terlalu_cepat(): void
    {
        Pengaturan::get()->update(['batas_terlambat' => '08:00', 'mulai_pulang' => '13:00']);
        $siswa = $this->siswa();

        Carbon::setTestNow('2026-07-13 07:00:00');
        app(AbsensiRecorder::class)->record($siswa);

        Carbon::setTestNow('2026-07-13 10:00:00');
        $result = app(AbsensiRecorder::class)->record($siswa);

        $this->assertSame('already', $result['status']);
        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'jam_pulang' => null]);
    }

    public function test_scan_kedua_setelah_mulai_pulang_tercatat_sebagai_absen_pulang(): void
    {
        Pengaturan::get()->update(['batas_terlambat' => '08:00', 'mulai_pulang' => '13:00']);
        $siswa = $this->siswa();

        Carbon::setTestNow('2026-07-13 07:00:00');
        app(AbsensiRecorder::class)->record($siswa);

        Carbon::setTestNow('2026-07-13 13:30:00');
        $result = app(AbsensiRecorder::class)->record($siswa);

        $this->assertSame('success', $result['status']);
        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'jam_masuk' => '07:00:00', 'jam_pulang' => '13:30:00']);
    }

    public function test_scan_ketiga_setelah_masuk_dan_pulang_ditolak(): void
    {
        Pengaturan::get()->update(['batas_terlambat' => '08:00', 'mulai_pulang' => '13:00']);
        $siswa = $this->siswa();

        Carbon::setTestNow('2026-07-13 07:00:00');
        app(AbsensiRecorder::class)->record($siswa);

        Carbon::setTestNow('2026-07-13 13:30:00');
        app(AbsensiRecorder::class)->record($siswa);

        Carbon::setTestNow('2026-07-13 15:00:00');
        $result = app(AbsensiRecorder::class)->record($siswa);

        $this->assertSame('already', $result['status']);
        $this->assertSame(1, \App\Models\Absensi::where('siswa_id', $siswa->id)->count());
    }

    public function test_absensi_diblokir_saat_hari_libur(): void
    {
        Carbon::setTestNow('2026-07-13 07:00:00');
        HariLibur::create(['tanggal' => '2026-07-13', 'keterangan' => 'Libur Nasional']);

        $siswa = $this->siswa();
        $result = app(AbsensiRecorder::class)->record($siswa);

        $this->assertSame('libur', $result['status']);
        $this->assertDatabaseMissing('absensi', ['siswa_id' => $siswa->id]);
    }

    public function test_libur_mingguan_otomatis_memblokir_absensi_tanpa_baris_manual(): void
    {
        // 2026-07-11 = Sabtu, tidak ada baris hari_libur untuk tanggal ini sama sekali.
        Pengaturan::get()->update(['hari_libur_mingguan' => [6]]); // Sabtu
        Carbon::setTestNow('2026-07-11 07:00:00');

        $siswa = $this->siswa();
        $result = app(AbsensiRecorder::class)->record($siswa);

        $this->assertSame('libur', $result['status']);
        $this->assertDatabaseMissing('absensi', ['siswa_id' => $siswa->id]);
        $this->assertDatabaseMissing('hari_libur', ['tanggal' => '2026-07-11']);
    }

    public function test_libur_mingguan_tidak_mempengaruhi_hari_yang_tidak_dicentang(): void
    {
        // Sabtu dikonfigurasi libur, tapi Senin (2026-07-13) bukan bagian dari itu.
        Pengaturan::get()->update(['hari_libur_mingguan' => [6]]);
        Carbon::setTestNow('2026-07-13 07:00:00');

        $result = app(AbsensiRecorder::class)->record($this->siswa());

        $this->assertSame('success', $result['status']);
    }

    public function test_lokasi_tidak_dikonfigurasi_absen_tetap_normal(): void
    {
        Carbon::setTestNow('2026-07-13 07:00:00');
        $siswa = $this->siswa();

        // Koordinat sembarang, jauh dari mana pun -- tidak boleh berpengaruh
        // sama sekali selama Pengaturan::lokasiAktif() masih false.
        $result = app(AbsensiRecorder::class)->record($siswa, 0.0, 0.0);

        $this->assertSame('success', $result['status']);
        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'jam_masuk' => '07:00:00']);
    }

    public function test_lokasi_dikonfigurasi_dan_dalam_radius_absen_sukses(): void
    {
        Pengaturan::get()->update(['lokasi_lat' => '-6.9147000', 'lokasi_lng' => '107.6098000', 'lokasi_radius_meter' => 100]);
        Carbon::setTestNow('2026-07-13 07:00:00');
        $siswa = $this->siswa();

        $result = app(AbsensiRecorder::class)->record($siswa, -6.9147000, 107.6098000);

        $this->assertSame('success', $result['status']);
        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'jam_masuk' => '07:00:00']);
    }

    public function test_lokasi_dikonfigurasi_dan_di_luar_radius_ditolak(): void
    {
        Pengaturan::get()->update(['lokasi_lat' => '-6.9147000', 'lokasi_lng' => '107.6098000', 'lokasi_radius_meter' => 100]);
        Carbon::setTestNow('2026-07-13 07:00:00');
        $siswa = $this->siswa();

        // ~1km ke utara dari titik sekolah.
        $result = app(AbsensiRecorder::class)->record($siswa, -6.9057000, 107.6098000);

        $this->assertSame('lokasi', $result['status']);
        $this->assertDatabaseMissing('absensi', ['siswa_id' => $siswa->id]);
    }

    public function test_lokasi_dikonfigurasi_dan_koordinat_tidak_dikirim_ditolak(): void
    {
        Pengaturan::get()->update(['lokasi_lat' => '-6.9147000', 'lokasi_lng' => '107.6098000', 'lokasi_radius_meter' => 100]);
        Carbon::setTestNow('2026-07-13 07:00:00');
        $siswa = $this->siswa();

        $result = app(AbsensiRecorder::class)->record($siswa);

        $this->assertSame('lokasi', $result['status']);
        $this->assertDatabaseMissing('absensi', ['siswa_id' => $siswa->id]);
    }

    public function test_siswa_yang_sudah_ditandai_alpha_tapi_lalu_scan_beneran_menimpa_jadi_hadir(): void
    {
        Pengaturan::get()->update(['batas_terlambat' => '08:00', 'mulai_pulang' => '13:00']);
        $siswa = $this->siswa();

        // Simulasikan baris yang ditulis AbsensiAlphaChecker di akhir hari kemarin.
        Carbon::setTestNow('2026-07-13 07:00:00');
        \App\Models\Absensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'status' => 'alpha',
            'metode' => 'manual',
        ]);

        $result = app(AbsensiRecorder::class)->record($siswa);

        $this->assertSame('success', $result['status']);
        $this->assertSame('hadir', $result['keterangan']);
        $this->assertSame(1, \App\Models\Absensi::where('siswa_id', $siswa->id)->count());
        $this->assertDatabaseHas('absensi', [
            'siswa_id' => $siswa->id,
            'status' => 'hadir',
            'jam_masuk' => '07:00:00',
            'jam_pulang' => null,
        ]);
    }

    public function test_absen_masuk_ditolak_setelah_mulai_pulang_untuk_siswa_yang_belum_absen(): void
    {
        Pengaturan::get()->update(['batas_terlambat' => '08:00', 'mulai_pulang' => '13:00']);
        Carbon::setTestNow('2026-07-13 13:00:00');
        $siswa = $this->siswa();

        $result = app(AbsensiRecorder::class)->record($siswa);

        $this->assertSame('tutup', $result['status']);
        $this->assertDatabaseMissing('absensi', ['siswa_id' => $siswa->id]);
    }

    public function test_absen_masuk_ditolak_setelah_mulai_pulang_untuk_siswa_yang_sudah_alpha(): void
    {
        Pengaturan::get()->update(['batas_terlambat' => '08:00', 'mulai_pulang' => '13:00']);
        $siswa = $this->siswa();

        Carbon::setTestNow('2026-07-13 07:00:00');
        \App\Models\Absensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'status' => 'alpha',
            'metode' => 'manual',
        ]);

        Carbon::setTestNow('2026-07-13 17:20:00');
        $result = app(AbsensiRecorder::class)->record($siswa);

        $this->assertSame('tutup', $result['status']);
        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'status' => 'alpha', 'jam_masuk' => null]);
    }

    public function test_siswa_yang_izin_atau_sakit_tidak_bisa_absen_scan(): void
    {
        Pengaturan::get()->update(['batas_terlambat' => '08:00', 'mulai_pulang' => '13:00']);
        $siswa = $this->siswa();

        Carbon::setTestNow('2026-07-13 07:00:00');
        \App\Models\Absensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'status' => 'sakit',
            'metode' => 'manual',
        ]);

        Carbon::setTestNow('2026-07-13 14:00:00');
        $result = app(AbsensiRecorder::class)->record($siswa);

        $this->assertSame('already', $result['status']);
        $this->assertDatabaseHas('absensi', [
            'siswa_id' => $siswa->id,
            'status' => 'sakit',
            'jam_masuk' => null,
            'jam_pulang' => null,
        ]);
    }

    public function test_absen_masuk_mengirim_konfirmasi_kehadiran_ke_email_orang_tua(): void
    {
        Mail::fake();
        Pengaturan::get()->update(['batas_terlambat' => '08:00', 'mulai_pulang' => '13:00']);
        Carbon::setTestNow('2026-07-13 07:00:00');
        $siswa = $this->siswa(['email_orang_tua' => 'ortu@example.com']);

        app(AbsensiRecorder::class)->record($siswa);

        $this->assertDatabaseHas('notifikasi_absensi_log', [
            'siswa_id' => $siswa->id,
            'jenis' => 'kehadiran',
            'kontak' => 'ortu@example.com',
            'status' => 'terkirim',
        ]);
        Mail::assertSent(SiswaHadirMail::class, fn ($mail) => $mail->hasTo('ortu@example.com'));
    }

    public function test_absen_masuk_tanpa_email_orang_tua_tidak_mengirim_apa_pun_tapi_tetap_absen(): void
    {
        Mail::fake();
        Pengaturan::get()->update(['batas_terlambat' => '08:00', 'mulai_pulang' => '13:00']);
        Carbon::setTestNow('2026-07-13 07:00:00');
        $siswa = $this->siswa();

        $result = app(AbsensiRecorder::class)->record($siswa);

        $this->assertSame('success', $result['status']);
        $this->assertDatabaseHas('notifikasi_absensi_log', [
            'siswa_id' => $siswa->id,
            'jenis' => 'kehadiran',
            'kontak' => null,
            'status' => 'tidak_ada_kontak',
        ]);
        Mail::assertNothingSent();
    }

    public function test_absen_pulang_tidak_mengirim_konfirmasi_kehadiran_lagi(): void
    {
        Mail::fake();
        Pengaturan::get()->update(['batas_terlambat' => '08:00', 'mulai_pulang' => '13:00']);
        $siswa = $this->siswa(['email_orang_tua' => 'ortu@example.com']);

        Carbon::setTestNow('2026-07-13 07:00:00');
        app(AbsensiRecorder::class)->record($siswa);

        Carbon::setTestNow('2026-07-13 13:30:00');
        app(AbsensiRecorder::class)->record($siswa);

        // Cuma satu notifikasi kehadiran (dari absen masuk), bukan dua.
        $this->assertSame(1, \App\Models\NotifikasiAbsensiLog::where('siswa_id', $siswa->id)->where('jenis', 'kehadiran')->count());
        Mail::assertSent(SiswaHadirMail::class, 1);
    }
}
