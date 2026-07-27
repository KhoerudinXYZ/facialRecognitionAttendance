<?php

namespace Tests\Feature;

use App\Mail\KoreksiAbsensiBaruMail;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\KoreksiAbsensi;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KoreksiAbsensiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

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

    public function test_siswa_bisa_ajukan_koreksi_untuk_tanggal_yang_sudah_ada_absensinya(): void
    {
        Storage::fake('public');
        Mail::fake();
        $kelas = $this->kelas();
        $siswa = $this->siswa($kelas);
        Absensi::create(['siswa_id' => $siswa->id, 'kelas_id' => $kelas->id, 'tanggal' => '2026-07-13', 'status' => 'alpha', 'metode' => 'manual']);
        $this->actingAs($siswa, 'siswa');

        $this->post('/portal/koreksi', [
            'tanggal' => '2026-07-13',
            'status_diminta' => 'hadir',
            'alasan' => 'Saya masuk tapi wajah gagal terdeteksi',
            'bukti' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect();

        $this->assertDatabaseHas('koreksi_absensi', [
            'siswa_id' => $siswa->id,
            'status_diminta' => 'hadir',
            'status' => 'menunggu',
        ]);
    }

    public function test_siswa_tidak_bisa_ajukan_koreksi_untuk_tanggal_tanpa_absensi(): void
    {
        $kelas = $this->kelas();
        $siswa = $this->siswa($kelas);
        $this->actingAs($siswa, 'siswa');

        $this->post('/portal/koreksi', [
            'tanggal' => '2026-07-13',
            'status_diminta' => 'hadir',
            'alasan' => 'Saya masuk',
        ])->assertSessionHas('error');

        $this->assertSame(0, KoreksiAbsensi::count());
    }

    public function test_siswa_tidak_bisa_ajukan_kalau_status_diminta_sama_dengan_tercatat(): void
    {
        $kelas = $this->kelas();
        $siswa = $this->siswa($kelas);
        Absensi::create(['siswa_id' => $siswa->id, 'kelas_id' => $kelas->id, 'tanggal' => '2026-07-13', 'status' => 'hadir', 'jam_masuk' => '07:00:00', 'metode' => 'face']);
        $this->actingAs($siswa, 'siswa');

        $this->post('/portal/koreksi', [
            'tanggal' => '2026-07-13',
            'status_diminta' => 'hadir',
            'alasan' => 'Tidak masuk akal tapi dicoba',
        ])->assertSessionHas('error');

        $this->assertSame(0, KoreksiAbsensi::count());
    }

    public function test_siswa_tidak_bisa_ajukan_dua_kali_saat_masih_menunggu(): void
    {
        $kelas = $this->kelas();
        $siswa = $this->siswa($kelas);
        Absensi::create(['siswa_id' => $siswa->id, 'kelas_id' => $kelas->id, 'tanggal' => '2026-07-13', 'status' => 'alpha', 'metode' => 'manual']);
        $this->actingAs($siswa, 'siswa');

        $this->post('/portal/koreksi', ['tanggal' => '2026-07-13', 'status_diminta' => 'hadir', 'alasan' => 'Pertama']);
        $this->post('/portal/koreksi', ['tanggal' => '2026-07-13', 'status_diminta' => 'sakit', 'alasan' => 'Kedua'])
            ->assertSessionHas('error');

        $this->assertSame(1, KoreksiAbsensi::count());
    }

    public function test_siswa_bisa_ajukan_ulang_setelah_ditolak(): void
    {
        $kelas = $this->kelas();
        $siswa = $this->siswa($kelas);
        Absensi::create(['siswa_id' => $siswa->id, 'kelas_id' => $kelas->id, 'tanggal' => '2026-07-13', 'status' => 'alpha', 'metode' => 'manual']);
        $koreksi = KoreksiAbsensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'status_diminta' => 'hadir',
            'alasan' => 'Pertama',
            'status' => 'ditolak',
            'catatan_admin' => 'Tidak ada bukti',
        ]);
        $this->actingAs($siswa, 'siswa');

        $this->post('/portal/koreksi', [
            'tanggal' => '2026-07-13',
            'status_diminta' => 'sakit',
            'alasan' => 'Sekarang ada surat dokter',
        ])->assertRedirect();

        $this->assertSame(1, KoreksiAbsensi::count());
        $this->assertDatabaseHas('koreksi_absensi', [
            'id' => $koreksi->id,
            'status_diminta' => 'sakit',
            'status' => 'menunggu',
        ]);
    }

    public function test_wali_kelas_dinotifikasi_email_saat_ada_koreksi_baru(): void
    {
        Mail::fake();
        $kelas = $this->kelas();
        $this->waliKelas($kelas);
        $siswa = $this->siswa($kelas);
        Absensi::create(['siswa_id' => $siswa->id, 'kelas_id' => $kelas->id, 'tanggal' => '2026-07-13', 'status' => 'alpha', 'metode' => 'manual']);
        $this->actingAs($siswa, 'siswa');

        $this->post('/portal/koreksi', [
            'tanggal' => '2026-07-13',
            'status_diminta' => 'hadir',
            'alasan' => 'Wajah gagal terdeteksi',
        ]);

        Mail::assertSent(KoreksiAbsensiBaruMail::class, fn ($mail) => $mail->hasTo('wali@test.test')
            && $mail->siswaNama === $siswa->nama
            && $mail->statusDiminta === 'hadir');
    }

    public function test_admin_approve_menulis_status_ke_absensi(): void
    {
        $kelas = $this->kelas();
        $siswa = $this->siswa($kelas);
        Absensi::create(['siswa_id' => $siswa->id, 'kelas_id' => $kelas->id, 'tanggal' => '2026-07-13', 'status' => 'alpha', 'metode' => 'manual']);
        $koreksi = KoreksiAbsensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'status_diminta' => 'hadir',
            'alasan' => 'Wajah gagal terdeteksi',
            'status' => 'menunggu',
        ]);

        $this->actingAs($this->admin());
        $this->post("/koreksi-absensi/{$koreksi->id}/approve", ['status' => 'hadir'])->assertRedirect();

        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'status' => 'hadir', 'metode' => 'manual']);
        $this->assertDatabaseHas('koreksi_absensi', ['id' => $koreksi->id, 'status' => 'disetujui']);
    }

    public function test_admin_approve_bisa_override_status_diminta_siswa(): void
    {
        // Admin punya keputusan akhir -- siswa minta 'hadir', tapi admin
        // menilai 'terlambat' lebih akurat, dan itu yang harus tercatat.
        $kelas = $this->kelas();
        $siswa = $this->siswa($kelas);
        Absensi::create(['siswa_id' => $siswa->id, 'kelas_id' => $kelas->id, 'tanggal' => '2026-07-13', 'status' => 'alpha', 'metode' => 'manual']);
        $koreksi = KoreksiAbsensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'status_diminta' => 'hadir',
            'alasan' => 'Saya masuk',
            'status' => 'menunggu',
        ]);

        $this->actingAs($this->admin());
        $this->post("/koreksi-absensi/{$koreksi->id}/approve", ['status' => 'terlambat'])->assertRedirect();

        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'status' => 'terlambat']);
    }

    public function test_reject_tidak_mengubah_baris_absensi(): void
    {
        $kelas = $this->kelas();
        $siswa = $this->siswa($kelas);
        Absensi::create(['siswa_id' => $siswa->id, 'kelas_id' => $kelas->id, 'tanggal' => '2026-07-13', 'status' => 'alpha', 'metode' => 'manual']);
        $koreksi = KoreksiAbsensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'status_diminta' => 'hadir',
            'alasan' => 'Saya masuk',
            'status' => 'menunggu',
        ]);

        $this->actingAs($this->admin());
        $this->post("/koreksi-absensi/{$koreksi->id}/reject", ['catatan_admin' => 'Tidak ada bukti'])->assertRedirect();

        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'status' => 'alpha']);
        $this->assertDatabaseHas('koreksi_absensi', ['id' => $koreksi->id, 'status' => 'ditolak']);
    }

    public function test_wali_kelas_lain_tidak_bisa_approve(): void
    {
        $kelasSendiri = $this->kelas();
        $kelasLain = Kelas::create(['nama_kelas' => 'XI TKJ 1', 'jurusan' => 'TKJ', 'tingkat' => 'XI']);
        $siswaLain = $this->siswa($kelasLain);
        $wali = $this->waliKelas($kelasSendiri);

        Absensi::create(['siswa_id' => $siswaLain->id, 'kelas_id' => $kelasLain->id, 'tanggal' => '2026-07-13', 'status' => 'alpha', 'metode' => 'manual']);
        $koreksi = KoreksiAbsensi::create([
            'siswa_id' => $siswaLain->id,
            'tanggal' => '2026-07-13',
            'status_diminta' => 'hadir',
            'alasan' => 'Saya masuk',
            'status' => 'menunggu',
        ]);

        $this->actingAs($wali);
        $this->post("/koreksi-absensi/{$koreksi->id}/approve", ['status' => 'hadir'])->assertForbidden();

        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswaLain->id, 'status' => 'alpha']);
    }

    public function test_koreksi_yang_sudah_diproses_tidak_bisa_diproses_ulang(): void
    {
        $kelas = $this->kelas();
        $siswa = $this->siswa($kelas);
        Absensi::create(['siswa_id' => $siswa->id, 'kelas_id' => $kelas->id, 'tanggal' => '2026-07-13', 'status' => 'hadir', 'jam_masuk' => '07:00:00', 'metode' => 'manual']);
        $koreksi = KoreksiAbsensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'status_diminta' => 'hadir',
            'alasan' => 'Saya masuk',
            'status' => 'disetujui',
            'reviewed_at' => now(),
        ]);

        $this->actingAs($this->admin());
        $this->post("/koreksi-absensi/{$koreksi->id}/approve", ['status' => 'hadir'])->assertForbidden();
        $this->post("/koreksi-absensi/{$koreksi->id}/reject")->assertForbidden();
    }

    public function test_halaman_admin_koreksi_absensi_menampilkan_pengajuan(): void
    {
        $kelas = $this->kelas();
        $siswa = $this->siswa($kelas);
        Absensi::create(['siswa_id' => $siswa->id, 'kelas_id' => $kelas->id, 'tanggal' => '2026-07-13', 'status' => 'alpha', 'metode' => 'manual']);
        KoreksiAbsensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'status_diminta' => 'hadir',
            'alasan' => 'Wajah gagal terdeteksi',
            'status' => 'menunggu',
        ]);

        $this->actingAs($this->admin());

        $this->get('/koreksi-absensi')
            ->assertOk()
            ->assertSee($siswa->nama)
            ->assertSee('Wajah gagal terdeteksi');
    }

    public function test_halaman_riwayat_siswa_menampilkan_tombol_laporkan_koreksi(): void
    {
        $kelas = $this->kelas();
        $siswa = $this->siswa($kelas);
        Absensi::create(['siswa_id' => $siswa->id, 'kelas_id' => $kelas->id, 'tanggal' => '2026-07-13', 'status' => 'alpha', 'metode' => 'manual']);
        Carbon::setTestNow('2026-07-13 20:00:00');
        $this->actingAs($siswa, 'siswa');

        $this->get('/portal/riwayat?bulan=2026-07')
            ->assertOk()
            ->assertSee('Laporkan Koreksi');
    }

    public function test_halaman_riwayat_siswa_menampilkan_status_menunggu_bukan_tombol(): void
    {
        $kelas = $this->kelas();
        $siswa = $this->siswa($kelas);
        Absensi::create(['siswa_id' => $siswa->id, 'kelas_id' => $kelas->id, 'tanggal' => '2026-07-13', 'status' => 'alpha', 'metode' => 'manual']);
        KoreksiAbsensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'status_diminta' => 'hadir',
            'alasan' => 'Saya masuk',
            'status' => 'menunggu',
        ]);
        Carbon::setTestNow('2026-07-13 20:00:00');
        $this->actingAs($siswa, 'siswa');

        $this->get('/portal/riwayat?bulan=2026-07')
            ->assertOk()
            ->assertSee('Koreksi Menunggu')
            ->assertDontSee('Laporkan Koreksi');
    }
}
