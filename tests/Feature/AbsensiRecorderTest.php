<?php

namespace Tests\Feature;

use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Services\AbsensiRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AbsensiRecorderTest extends TestCase
{
    use RefreshDatabase;

    private function siswa(): Siswa
    {
        $kelas = Kelas::create([
            'nama_kelas' => 'X RPL 1',
            'jurusan' => 'RPL',
            'tingkat' => 'X',
        ]);

        return Siswa::create([
            'nis' => '12345',
            'nama' => 'Budi',
            'jenis_kelamin' => 'L',
            'kelas_id' => $kelas->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
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
}
