<?php

namespace Tests\Feature;

use App\Models\AbsensiKecepatanAnomaliLog;
use App\Models\AbsensiLokasiGagalLog;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AbsensiAuditLokasiTest extends TestCase
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

    public function test_percobaan_gagal_lokasi_tampil_di_halaman_audit(): void
    {
        $this->actingAs($this->admin());

        $siswa = $this->siswa();

        AbsensiLokasiGagalLog::create([
            'siswa_id' => $siswa->id,
            'lat' => -6.9057000,
            'lng' => 107.6098000,
            'accuracy' => 20.0,
            'jarak_meter' => 1000.0,
            'alasan' => 'luar_radius',
            'ip' => '127.0.0.1',
        ]);

        $response = $this->get(route('absensi.audit-lokasi'));

        $response->assertOk();
        $response->assertSeeText($siswa->nama);
        $response->assertSeeText('Di luar radius');
    }

    public function test_anomali_kecepatan_tampil_di_halaman_audit(): void
    {
        $this->actingAs($this->admin());

        $siswa = $this->siswa();

        AbsensiKecepatanAnomaliLog::create([
            'siswa_id' => $siswa->id,
            'lat_buka' => -6.9147000,
            'lng_buka' => 107.6098000,
            'lat_absen' => -6.7347000,
            'lng_absen' => 107.6098000,
            'jarak_meter' => 20000.0,
            'jeda_ms' => 60000,
            'kecepatan_kmh' => 1200.0,
        ]);

        $response = $this->get(route('absensi.audit-lokasi'));

        $response->assertOk();
        $response->assertSeeText($siswa->nama);
        $response->assertSeeText('1,200 km/jam');
    }

    public function test_wali_kelas_tidak_bisa_akses_audit_lokasi(): void
    {
        $kelas = Kelas::create(['nama_kelas' => 'X RPL 1', 'jurusan' => 'RPL', 'tingkat' => 'X']);
        $waliKelas = User::create([
            'name' => 'Wali',
            'email' => 'wali@test.test',
            'password' => Hash::make('password'),
            'role' => 'wali_kelas',
        ]);
        $kelas->update(['wali_kelas_id' => $waliKelas->id]);

        $this->actingAs($waliKelas);

        $this->get(route('absensi.audit-lokasi'))->assertForbidden();
    }
}
