<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\FaceDescriptor;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AbsensiSmokeTest extends TestCase
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

    public function test_halaman_utama_bisa_diakses(): void
    {
        $this->actingAs($this->admin());
        $this->kelas();

        foreach ([
            '/dashboard',
            '/siswa',
            '/kelas',
            '/kelas/create',
            '/absensi',
            '/laporan',
            '/pengaturan',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_crud_kelas_dan_siswa(): void
    {
        $this->actingAs($this->admin());

        $this->post('/kelas', [
            'nama_kelas' => 'XI TKJ 1',
            'tingkat' => 'XI',
            'jurusan' => 'TKJ',
            'wali_kelas' => 'Guru B',
        ])->assertRedirect('/kelas');

        $kelas = Kelas::first();

        $this->post('/siswa', [
            'nis' => '12345',
            'nama' => 'Budi',
            'jenis_kelamin' => 'L',
            'kelas_id' => $kelas->id,
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('siswa', ['nis' => '12345', 'nama' => 'Budi']);
    }

    public function test_enroll_wajah_oleh_admin(): void
    {
        $this->actingAs($this->admin());
        $kelas = $this->kelas();
        $siswa = Siswa::create([
            'nis' => '999',
            'nama' => 'Siti',
            'jenis_kelamin' => 'P',
            'kelas_id' => $kelas->id,
        ]);

        $descriptor = array_fill(0, 128, 0.1);

        $this->postJson("/siswa/{$siswa->id}/face", [
            'descriptors' => [$descriptor],
        ])->assertOk()->assertJsonPath('total', 1);

        $this->assertEquals(1, FaceDescriptor::count());
    }

    public function test_absensi_manual_dan_export(): void
    {
        $this->actingAs($this->admin());
        $kelas = $this->kelas();
        $siswa = Siswa::create([
            'nis' => '111',
            'nama' => 'Andi',
            'jenis_kelamin' => 'L',
            'kelas_id' => $kelas->id,
        ]);

        $this->post('/absensi/manual', [
            'siswa_id' => $siswa->id,
            'tanggal' => now()->toDateString(),
            'status' => 'izin',
            'keterangan' => 'Acara keluarga',
        ])->assertRedirect();

        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'status' => 'izin']);

        // Export
        $this->get('/laporan/excel')->assertOk();
        $this->get('/laporan/pdf')->assertOk();
    }

    public function test_policy_create_menolak_kelas_dan_siswa_di_luar_binaan(): void
    {
        $kelasSendiri = $this->kelas();
        $kelasLain = Kelas::create([
            'nama_kelas' => 'XI TKJ 1',
            'jurusan' => 'TKJ',
            'tingkat' => 'XI',
        ]);
        $siswaLain = Siswa::create([
            'nis' => '777', 'nama' => 'Luar', 'jenis_kelamin' => 'L', 'kelas_id' => $kelasLain->id,
        ]);
        $wali = $this->waliKelas($kelasSendiri);

        // Ini menguji SiswaPolicy::create() dan AbsensiPolicy::create() secara
        // langsung lewat Gate, terlepas dari validasi HTTP di controller —
        // memastikan policy-nya sendiri yang menolak, bukan cuma kebetulan
        // ketolong oleh Rule::in/visibleTo() di lapisan lain.
        $this->assertTrue(Gate::forUser($wali)->allows('create', [Siswa::class, $kelasSendiri]));
        $this->assertFalse(Gate::forUser($wali)->allows('create', [Siswa::class, $kelasLain]));

        $this->assertFalse(Gate::forUser($wali)->allows('create', [Absensi::class, $siswaLain]));
    }

    public function test_wali_kelas_tidak_bisa_tambah_siswa_ke_kelas_lain(): void
    {
        $kelasSendiri = $this->kelas();
        $kelasLain = Kelas::create([
            'nama_kelas' => 'XI TKJ 1',
            'jurusan' => 'TKJ',
            'tingkat' => 'XI',
        ]);
        $wali = $this->waliKelas($kelasSendiri);

        $this->actingAs($wali);

        // Boleh: tambah siswa ke kelas binaannya sendiri.
        $this->post('/siswa', [
            'nis' => '333',
            'nama' => 'Rudi',
            'jenis_kelamin' => 'L',
            'kelas_id' => $kelasSendiri->id,
            'is_active' => '1',
        ])->assertRedirect();
        $this->assertDatabaseHas('siswa', ['nis' => '333', 'kelas_id' => $kelasSendiri->id]);

        // Ditolak: kelas_id di luar binaan gagal validasi (Rule::in) sebelum
        // sempat menyentuh policy sama sekali.
        $this->post('/siswa', [
            'nis' => '444',
            'nama' => 'Tono',
            'jenis_kelamin' => 'L',
            'kelas_id' => $kelasLain->id,
            'is_active' => '1',
        ])->assertSessionHasErrors('kelas_id');
        $this->assertDatabaseMissing('siswa', ['nis' => '444']);
    }

    public function test_wali_kelas_tidak_bisa_absensi_manual_siswa_kelas_lain(): void
    {
        $kelasSendiri = $this->kelas();
        $kelasLain = Kelas::create([
            'nama_kelas' => 'XI TKJ 1',
            'jurusan' => 'TKJ',
            'tingkat' => 'XI',
        ]);
        $siswaSendiri = Siswa::create([
            'nis' => '555', 'nama' => 'Sendiri', 'jenis_kelamin' => 'L', 'kelas_id' => $kelasSendiri->id,
        ]);
        $siswaLain = Siswa::create([
            'nis' => '666', 'nama' => 'Lain', 'jenis_kelamin' => 'L', 'kelas_id' => $kelasLain->id,
        ]);
        $wali = $this->waliKelas($kelasSendiri);

        $this->actingAs($wali);

        $this->post('/absensi/manual', [
            'siswa_id' => $siswaSendiri->id,
            'tanggal' => now()->toDateString(),
            'status' => 'izin',
        ])->assertRedirect();
        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswaSendiri->id, 'status' => 'izin']);

        // Siswa::visibleTo() sudah menyaring ini jadi 404 sebelum policy
        // sempat dievaluasi — tetap harus gagal, bukan tercatat.
        $this->post('/absensi/manual', [
            'siswa_id' => $siswaLain->id,
            'tanggal' => now()->toDateString(),
            'status' => 'izin',
        ])->assertNotFound();
        $this->assertDatabaseMissing('absensi', ['siswa_id' => $siswaLain->id]);
    }

    public function test_hapus_absensi_dari_rekap(): void
    {
        $this->actingAs($this->admin());
        $kelas = $this->kelas();
        $siswa = Siswa::create([
            'nis' => '222',
            'nama' => 'Dewi',
            'jenis_kelamin' => 'P',
            'kelas_id' => $kelas->id,
        ]);

        $absen = Absensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => now()->toDateString(),
            'jam_masuk' => '07:05:00',
            'status' => 'hadir',
            'metode' => 'face',
        ]);

        $this->delete("/absensi/{$absen->id}")->assertRedirect();

        $this->assertDatabaseMissing('absensi', ['id' => $absen->id]);

        // Baris aslinya hilang total, tapi jejaknya harus tetap ada &
        // lengkap di absensi_audit_log — itu inti dari fitur audit trail-nya.
        $this->assertDatabaseHas('absensi_audit_log', [
            'absensi_id' => $absen->id,
            'siswa_id' => $siswa->id,
            'siswa_nama' => 'Dewi',
            'status' => 'hadir',
            'metode' => 'face',
            'dihapus_oleh_nama' => 'Admin',
        ]);
    }

    public function test_halaman_riwayat_hapus_absensi_hanya_admin(): void
    {
        $kelasSendiri = $this->kelas();
        $siswa = Siswa::create([
            'nis' => '888', 'nama' => 'Wawan', 'jenis_kelamin' => 'L', 'kelas_id' => $kelasSendiri->id,
        ]);
        $absen = Absensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => now()->toDateString(),
            'jam_masuk' => '07:00:00',
            'status' => 'hadir',
            'metode' => 'face',
        ]);
        $wali = $this->waliKelas($kelasSendiri);

        // Wali kelas boleh menghapus absensi kelas binaannya sendiri
        // (AbsensiPolicy::delete), tapi tidak boleh melihat halaman audit
        // gabungan seluruh sekolah — itu murni untuk oversight admin.
        $this->actingAs($wali);
        $this->delete("/absensi/{$absen->id}")->assertRedirect();
        $this->get('/absensi/audit')->assertForbidden();

        $this->actingAs($this->admin());
        $this->get('/absensi/audit')->assertOk()->assertSee('Wawan');
    }

    public function test_halaman_notifikasi_orang_tua_hanya_admin(): void
    {
        $kelas = $this->kelas();
        $wali = $this->waliKelas($kelas);

        $this->actingAs($wali);
        $this->get('/notifikasi-absensi')->assertForbidden();

        $this->actingAs($this->admin());
        $this->get('/notifikasi-absensi')->assertOk();
    }

    /**
     * Regresi: form kirim value checkbox sebagai string ("6", "0"), bukan
     * int. HariLibur::isLibur() membandingkan Carbon::dayOfWeek (int)
     * dengan in_array(..., true) alias strict — kalau controller lupa
     * cast ke int, checkbox akan tersimpan tapi tidak pernah benar-benar
     * memblokir absensi (silent bug, sempat kejadian saat implementasi).
     */
    public function test_libur_mingguan_dari_form_string_tetap_memblokir_absensi(): void
    {
        $this->actingAs($this->admin());

        $this->put('/pengaturan/libur-mingguan', [
            'hari_libur_mingguan' => ['6', '0'],
        ])->assertRedirect();

        $this->assertSame([6, 0], Pengaturan::get()->hari_libur_mingguan);

        // 2026-07-11 = Sabtu, tidak ada baris hari_libur manual untuk tanggal ini.
        Carbon::setTestNow('2026-07-11 07:00:00');
        $this->assertTrue(HariLibur::isLibur());
        Carbon::setTestNow();
    }
}
