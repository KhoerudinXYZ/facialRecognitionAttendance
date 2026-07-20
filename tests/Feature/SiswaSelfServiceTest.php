<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\FaceDescriptor;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SiswaSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Siswa::flushEventListeners();
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

    public function test_race_klaim_nis_hampir_bersamaan_tidak_saling_menimpa(): void
    {
        $siswa = $this->siswaBelumRegistrasi();

        // Simulasikan request lain yang menang race: klaim NIS ini duluan
        // (raw update, bukan lewat controller) TEPAT setelah request ini
        // membaca baris siswa (lolos whereNull('username') milik store())
        // tapi SEBELUM UPDATE bersyaratnya sendiri sempat jalan -- momen
        // paling sempit yang tidak bisa dipicu lewat dua request sungguhan
        // dalam test satu proses/thread.
        Siswa::retrieved(function ($model) use ($siswa) {
            if ($model->is($siswa) && $model->username === null) {
                DB::table('siswa')->where('id', $siswa->id)->update([
                    'username' => 'pemenang_race',
                    'password' => Hash::make('passwordlain'),
                ]);
            }
        });

        $this->post('/portal/register', [
            'nis' => $siswa->nis,
            'username' => 'budi01',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('nis');

        // Yang kalah race harus ditolak dengan jelas, bukan diam-diam
        // menimpa akun yang sudah diklaim pemenang race.
        $this->assertGuest('siswa');
        $this->assertDatabaseHas('siswa', ['id' => $siswa->id, 'username' => 'pemenang_race']);
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
        $this->postJson('/portal/absen', ['liveness_verified' => true])->assertOk()->assertJsonPath('status', 'success');
        $this->assertEquals(1, Absensi::where('siswa_id', $siswa->id)->count());

        // Scan kedua di jam yang sama (belum masuk jam pulang) -> already, tidak dobel.
        $this->postJson('/portal/absen', ['liveness_verified' => true])->assertOk()->assertJsonPath('status', 'already');
        $this->assertEquals(1, Absensi::where('siswa_id', $siswa->id)->count());

        // Scan setelah jam mulai pulang (default 13:00) -> tercatat sebagai absen pulang.
        Carbon::setTestNow('2026-07-13 13:30:00');
        $this->postJson('/portal/absen', ['liveness_verified' => true])->assertOk()->assertJsonPath('status', 'success');
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
        $this->postJson('/portal/absen', ['lat' => -6.9057000, 'lng' => 107.6098000, 'liveness_verified' => true])
            ->assertOk()->assertJsonPath('status', 'lokasi');

        $this->assertEquals(0, Absensi::where('siswa_id', $siswa->id)->count());
    }

    public function test_siswa_yang_sakit_hari_ini_tidak_bisa_buka_kamera_dan_dashboard_tidak_menampilkan_masuk_tercentang(): void
    {
        Carbon::setTestNow('2026-07-13 08:00:00');

        $siswa = $this->siswaBelumRegistrasi();
        $this->registrasikanAkun($siswa, 'budi01', 'password123');
        $siswa->faceDescriptors()->create(['descriptor' => array_fill(0, 128, 0.1)]);

        Absensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'status' => 'sakit',
            'metode' => 'manual',
            'keterangan' => 'demam',
        ]);

        $this->post('/portal/login', [
            'username' => 'budi01',
            'password' => 'password123',
        ])->assertRedirect(route('siswa.dashboard'));

        // Halaman kamera tidak boleh terbuka sama sekali untuk hari sakit.
        $this->get(route('siswa.absen'))->assertRedirect(route('siswa.dashboard'));

        // Dashboard tetap menampilkan status "sakit", bukan seolah sudah absen masuk.
        $response = $this->get(route('siswa.dashboard'));
        $response->assertOk();
        $response->assertDontSee('Absen Pulang');
        $response->assertSee('Sakit');
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
