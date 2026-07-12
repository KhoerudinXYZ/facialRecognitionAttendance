<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\FaceDescriptor;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SiswaSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function siswaBelumRegistrasi(): Siswa
    {
        $kelas = Kelas::create([
            'nama_kelas' => 'X RPL 1',
            'jurusan' => 'RPL',
            'tingkat' => 'X',
        ]);

        return Siswa::create([
            'nis' => '1001',
            'nama' => 'Budi',
            'jenis_kelamin' => 'L',
            'kelas_id' => $kelas->id,
        ]);
    }

    /**
     * 'password' sengaja tidak ada di $fillable Siswa (lihat
     * SiswaRegistrationController) supaya tidak bisa di-mass-assign lewat
     * request biasa, jadi di sini pun harus di-assign langsung.
     */
    private function registrasikanAkun(Siswa $siswa, string $username, string $password): void
    {
        $siswa->username = $username;
        $siswa->password = Hash::make($password);
        $siswa->save();
    }

    public function test_siswa_bisa_registrasi_klaim_nis_lalu_langsung_login(): void
    {
        $siswa = $this->siswaBelumRegistrasi();

        $this->post('/portal/register', [
            'nis' => $siswa->nis,
            'username' => 'budi01',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('siswa.enroll.create'));

        $this->assertAuthenticated('siswa');

        $siswa->refresh();
        $this->assertSame('budi01', $siswa->username);
        $this->assertNotNull($siswa->password);
    }

    public function test_nis_yang_sudah_diklaim_tidak_bisa_diregistrasi_ulang(): void
    {
        $siswa = $this->siswaBelumRegistrasi();
        $this->registrasikanAkun($siswa, 'sudahdaftar', 'rahasia123');

        $this->post('/portal/register', [
            'nis' => $siswa->nis,
            'username' => 'budi02',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('nis');

        $this->assertGuest('siswa');
    }

    public function test_siswa_login_lalu_absen_mandiri(): void
    {
        Carbon::setTestNow('2026-07-13 07:00:00');

        $siswa = $this->siswaBelumRegistrasi();
        $this->registrasikanAkun($siswa, 'budi01', 'password123');
        $siswa->faceDescriptors()->create(['descriptor' => array_fill(0, 128, 0.1)]);

        $this->post('/portal/login', [
            'username' => 'budi01',
            'password' => 'password123',
        ])->assertRedirect(route('siswa.dashboard'));

        $this->assertAuthenticatedAs($siswa, 'siswa');

        // Scan pertama pagi hari -> absen masuk.
        $this->postJson('/portal/absen')->assertOk()->assertJsonPath('status', 'success');
        $this->assertEquals(1, Absensi::where('siswa_id', $siswa->id)->count());

        // Scan kedua di jam yang sama (belum masuk jam pulang) -> already, tidak dobel.
        $this->postJson('/portal/absen')->assertOk()->assertJsonPath('status', 'already');
        $this->assertEquals(1, Absensi::where('siswa_id', $siswa->id)->count());

        // Scan setelah jam mulai pulang (default 13:00) -> tercatat sebagai absen pulang.
        Carbon::setTestNow('2026-07-13 13:30:00');
        $this->postJson('/portal/absen')->assertOk()->assertJsonPath('status', 'success');
        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'jam_pulang' => '13:30:00']);
    }

    public function test_siswa_absen_mandiri_di_luar_radius_ditolak(): void
    {
        Pengaturan::get()->update(['lokasi_lat' => '-6.9147000', 'lokasi_lng' => '107.6098000', 'lokasi_radius_meter' => 100]);
        Carbon::setTestNow('2026-07-13 07:00:00');

        $siswa = $this->siswaBelumRegistrasi();
        $this->registrasikanAkun($siswa, 'budi01', 'password123');
        $siswa->faceDescriptors()->create(['descriptor' => array_fill(0, 128, 0.1)]);

        $this->post('/portal/login', [
            'username' => 'budi01',
            'password' => 'password123',
        ])->assertRedirect(route('siswa.dashboard'));

        // ~1km dari titik sekolah.
        $this->postJson('/portal/absen', ['lat' => -6.9057000, 'lng' => 107.6098000])
            ->assertOk()->assertJsonPath('status', 'lokasi');

        $this->assertEquals(0, Absensi::where('siswa_id', $siswa->id)->count());
    }

    public function test_siswa_bisa_daftar_wajah_sendiri(): void
    {
        $siswa = $this->siswaBelumRegistrasi();
        $this->registrasikanAkun($siswa, 'budi01', 'password123');

        $this->actingAs($siswa, 'siswa');

        $descriptor = array_fill(0, 128, 0.1);
        $this->postJson('/portal/enroll', ['descriptors' => [$descriptor]])
            ->assertOk()->assertJsonPath('total', 1);

        $this->assertEquals(1, FaceDescriptor::where('siswa_id', $siswa->id)->count());
    }
}
