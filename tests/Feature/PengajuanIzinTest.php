<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\PengajuanIzin;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PengajuanIzinTest extends TestCase
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

    private function kelas(): Kelas
    {
        return Kelas::create([
            'nama_kelas' => 'X RPL 1',
            'jurusan' => 'RPL',
            'tingkat' => 'X',
        ]);
    }

    private function waliKelas(Kelas $kelas): User
    {
        $user = User::create([
            'name' => 'Wali Kelas',
            'email' => 'wali@test.test',
            'password' => Hash::make('password'),
            'role' => 'wali_kelas',
        ]);

        $kelas->update(['wali_kelas_id' => $user->id]);

        return $user;
    }

    private function siswa(Kelas $kelas): Siswa
    {
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

    public function test_siswa_bisa_ajukan_izin(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-07-13 07:00:00');

        $siswa = $this->siswa($this->kelas());
        $this->actingAs($siswa, 'siswa');

        $this->post('/portal/izin', [
            'jenis' => 'sakit',
            'keterangan' => 'Demam',
            'bukti' => UploadedFile::fake()->image('surat.jpg'),
        ])->assertRedirect(route('siswa.izin'));

        $this->assertDatabaseHas('pengajuan_izin', [
            'siswa_id' => $siswa->id,
            'jenis' => 'sakit',
            'status' => 'menunggu',
        ]);
    }

    public function test_siswa_tidak_bisa_ajukan_dua_kali_saat_masih_menunggu(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-07-13 07:00:00');

        $siswa = $this->siswa($this->kelas());
        $this->actingAs($siswa, 'siswa');

        $this->post('/portal/izin', [
            'jenis' => 'sakit',
            'keterangan' => 'Demam',
            'bukti' => UploadedFile::fake()->image('surat.jpg'),
        ]);

        $this->post('/portal/izin', [
            'jenis' => 'izin',
            'keterangan' => 'Acara keluarga',
            'bukti' => UploadedFile::fake()->image('surat2.jpg'),
        ])->assertSessionHas('error');

        $this->assertEquals(1, PengajuanIzin::where('siswa_id', $siswa->id)->count());
    }

    public function test_siswa_tidak_bisa_ajukan_kalau_sudah_tercatat_hadir(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-07-13 07:00:00');

        $siswa = $this->siswa($this->kelas());
        Absensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'jam_masuk' => '07:00:00',
            'status' => 'hadir',
            'metode' => 'face',
        ]);

        $this->actingAs($siswa, 'siswa');

        $this->post('/portal/izin', [
            'jenis' => 'sakit',
            'keterangan' => 'Demam',
            'bukti' => UploadedFile::fake()->image('surat.jpg'),
        ])->assertSessionHas('error');

        $this->assertEquals(0, PengajuanIzin::where('siswa_id', $siswa->id)->count());
    }

    public function test_siswa_tidak_bisa_ajukan_saat_hari_libur(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-07-13 07:00:00');

        $siswa = $this->siswa($this->kelas());
        HariLibur::create(['tanggal' => '2026-07-13', 'keterangan' => 'Libur nasional']);

        $this->actingAs($siswa, 'siswa');

        $this->post('/portal/izin', [
            'jenis' => 'sakit',
            'keterangan' => 'Demam',
            'bukti' => UploadedFile::fake()->image('surat.jpg'),
        ])->assertSessionHas('error');

        $this->assertEquals(0, PengajuanIzin::where('siswa_id', $siswa->id)->count());
    }

    public function test_admin_approve_menulis_absensi(): void
    {
        Storage::fake('public');
        $kelas = $this->kelas();
        $siswa = $this->siswa($kelas);
        $pengajuan = PengajuanIzin::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'jenis' => 'sakit',
            'keterangan' => 'Demam',
            'bukti' => 'bukti-izin/surat.jpg',
            'status' => 'menunggu',
        ]);

        $this->actingAs($this->admin());
        $this->post("/pengajuan-izin/{$pengajuan->id}/approve")->assertRedirect();

        $this->assertDatabaseHas('absensi', [
            'siswa_id' => $siswa->id,
            'status' => 'sakit',
            'metode' => 'manual',
        ]);
        $this->assertDatabaseHas('pengajuan_izin', [
            'id' => $pengajuan->id,
            'status' => 'disetujui',
        ]);
    }

    public function test_wali_kelas_lain_tidak_bisa_approve(): void
    {
        Storage::fake('public');
        $kelasSendiri = $this->kelas();
        $kelasLain = Kelas::create(['nama_kelas' => 'XI TKJ 1', 'jurusan' => 'TKJ', 'tingkat' => 'XI']);
        $siswaLain = $this->siswa($kelasLain);
        $wali = $this->waliKelas($kelasSendiri);

        $pengajuan = PengajuanIzin::create([
            'siswa_id' => $siswaLain->id,
            'tanggal' => '2026-07-13',
            'jenis' => 'sakit',
            'keterangan' => 'Demam',
            'bukti' => 'bukti-izin/surat.jpg',
            'status' => 'menunggu',
        ]);

        $this->actingAs($wali);
        $this->post("/pengajuan-izin/{$pengajuan->id}/approve")->assertForbidden();

        $this->assertDatabaseMissing('absensi', ['siswa_id' => $siswaLain->id]);
    }

    public function test_admin_reject_tidak_menulis_absensi_dan_siswa_bisa_ajukan_ulang(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-07-13 07:00:00');

        $kelas = $this->kelas();
        $siswa = $this->siswa($kelas);
        $pengajuan = PengajuanIzin::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'jenis' => 'sakit',
            'keterangan' => 'Demam',
            'bukti' => 'bukti-izin/surat.jpg',
            'status' => 'menunggu',
        ]);

        $this->actingAs($this->admin());
        $this->post("/pengajuan-izin/{$pengajuan->id}/reject", [
            'catatan_admin' => 'Bukti tidak jelas',
        ])->assertRedirect();

        $this->assertDatabaseMissing('absensi', ['siswa_id' => $siswa->id]);
        $this->assertDatabaseHas('pengajuan_izin', [
            'id' => $pengajuan->id,
            'status' => 'ditolak',
            'catatan_admin' => 'Bukti tidak jelas',
        ]);

        $this->actingAs($siswa, 'siswa');
        $this->post('/portal/izin', [
            'jenis' => 'sakit',
            'keterangan' => 'Demam, coba lagi',
            'bukti' => UploadedFile::fake()->image('surat-baru.jpg'),
        ])->assertRedirect(route('siswa.izin'));

        $this->assertDatabaseHas('pengajuan_izin', [
            'id' => $pengajuan->id,
            'status' => 'menunggu',
            'keterangan' => 'Demam, coba lagi',
        ]);
    }
}
