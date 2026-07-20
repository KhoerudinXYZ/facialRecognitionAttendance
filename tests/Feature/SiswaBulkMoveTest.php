<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SiswaBulkMoveTest extends TestCase
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

    private function kelas(string $nama = 'X RPL 1'): Kelas
    {
        return Kelas::create([
            'nama_kelas' => $nama,
            'jurusan' => 'RPL',
            'tingkat' => 'X',
        ]);
    }

    private function waliKelas(Kelas ...$kelasBinaan): User
    {
        $user = User::create([
            'name' => 'Wali Kelas',
            'email' => 'wali' . uniqid() . '@test.test',
            'password' => Hash::make('password'),
            'role' => 'wali_kelas',
        ]);

        foreach ($kelasBinaan as $kelas) {
            $kelas->update(['wali_kelas_id' => $user->id]);
        }

        return $user;
    }

    private function siswa(Kelas $kelas, array $overrides = []): Siswa
    {
        return Siswa::create(array_merge([
            'nis' => (string) random_int(10000, 99999),
            'nama' => 'Siswa ' . uniqid(),
            'jenis_kelamin' => 'L',
            'kelas_id' => $kelas->id,
        ], $overrides));
    }

    public function test_admin_bisa_pindah_kelas_massal(): void
    {
        $kelasA = $this->kelas('X RPL 1');
        $kelasB = $this->kelas('XI RPL 1');
        $s1 = $this->siswa($kelasA);
        $s2 = $this->siswa($kelasA);

        $this->actingAs($this->admin());
        $this->put('/siswa/pindah-kelas', [
            'siswa_ids' => [$s1->id, $s2->id],
            'kelas_id' => $kelasB->id,
        ])->assertRedirect(route('siswa.index', ['kelas_id' => $kelasB->id]));

        $this->assertDatabaseHas('siswa', ['id' => $s1->id, 'kelas_id' => $kelasB->id]);
        $this->assertDatabaseHas('siswa', ['id' => $s2->id, 'kelas_id' => $kelasB->id]);
    }

    public function test_wali_kelas_bisa_pindah_siswa_binaan_ke_kelas_binaan_lain(): void
    {
        $kelasA = $this->kelas('X RPL 1');
        $kelasB = $this->kelas('XI RPL 1');
        $wali = $this->waliKelas($kelasA, $kelasB);
        $siswa = $this->siswa($kelasA);

        $this->actingAs($wali);
        $this->put('/siswa/pindah-kelas', [
            'siswa_ids' => [$siswa->id],
            'kelas_id' => $kelasB->id,
        ])->assertRedirect(route('siswa.index', ['kelas_id' => $kelasB->id]));

        $this->assertDatabaseHas('siswa', ['id' => $siswa->id, 'kelas_id' => $kelasB->id]);
    }

    public function test_wali_kelas_tidak_bisa_pindahkan_siswa_kelas_lain(): void
    {
        $kelasSendiri = $this->kelas('X RPL 1');
        $kelasTujuan = $this->kelas('XI RPL 1');
        $kelasOrangLain = $this->kelas('X TKJ 1');
        $waliSendiri = $this->waliKelas($kelasSendiri, $kelasTujuan);
        $this->waliKelas($kelasOrangLain);

        $siswaSendiri = $this->siswa($kelasSendiri);
        $siswaOrangLain = $this->siswa($kelasOrangLain);

        $this->actingAs($waliSendiri);
        $response = $this->put('/siswa/pindah-kelas', [
            'siswa_ids' => [$siswaSendiri->id, $siswaOrangLain->id],
            'kelas_id' => $kelasTujuan->id,
        ]);

        // Batch tetap sukses (bukan gagal total) -- yang berhak dipindahkan
        // tetap dipindahkan, yang di luar akses cuma dilewati.
        $response->assertRedirect(route('siswa.index', ['kelas_id' => $kelasTujuan->id]));
        $response->assertSessionHas('success', function ($message) {
            return str_contains($message, '1 siswa dipindahkan') && str_contains($message, 'dilewati');
        });

        $this->assertDatabaseHas('siswa', ['id' => $siswaSendiri->id, 'kelas_id' => $kelasTujuan->id]);
        // Siswa milik wali kelas lain tidak boleh ikut pindah.
        $this->assertDatabaseHas('siswa', ['id' => $siswaOrangLain->id, 'kelas_id' => $kelasOrangLain->id]);
    }

    public function test_wali_kelas_tidak_bisa_pindahkan_ke_kelas_yang_bukan_binaannya(): void
    {
        $kelasSendiri = $this->kelas('X RPL 1');
        $kelasBukanBinaan = $this->kelas('XI RPL 1');
        $wali = $this->waliKelas($kelasSendiri);
        $siswa = $this->siswa($kelasSendiri);

        $this->actingAs($wali);
        $this->put('/siswa/pindah-kelas', [
            'siswa_ids' => [$siswa->id],
            'kelas_id' => $kelasBukanBinaan->id,
        ])->assertSessionHasErrors('kelas_id');

        $this->assertDatabaseHas('siswa', ['id' => $siswa->id, 'kelas_id' => $kelasSendiri->id]);
    }

    public function test_siswa_ids_kosong_ditolak(): void
    {
        $kelas = $this->kelas();

        $this->actingAs($this->admin());
        $this->put('/siswa/pindah-kelas', [
            'siswa_ids' => [],
            'kelas_id' => $kelas->id,
        ])->assertSessionHasErrors('siswa_ids');
    }

    public function test_absensi_kelas_id_snapshot_tidak_berubah_setelah_siswa_dipindah(): void
    {
        $kelasA = $this->kelas('X RPL 1');
        $kelasB = $this->kelas('XI RPL 1');
        $siswa = $this->siswa($kelasA);

        Absensi::create([
            'siswa_id' => $siswa->id,
            'kelas_id' => $kelasA->id,
            'tanggal' => '2026-07-10',
            'jam_masuk' => '07:00:00',
            'status' => 'hadir',
            'metode' => 'face',
        ]);

        $this->actingAs($this->admin());
        $this->put('/siswa/pindah-kelas', [
            'siswa_ids' => [$siswa->id],
            'kelas_id' => $kelasB->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('siswa', ['id' => $siswa->id, 'kelas_id' => $kelasB->id]);
        // Snapshot kelas_id di baris absensi yang sudah ada tidak boleh
        // ikut berubah -- itu tujuan utama migration add_kelas_id_to_absensi.
        $this->assertDatabaseHas('absensi', ['siswa_id' => $siswa->id, 'kelas_id' => $kelasA->id]);
    }
}
