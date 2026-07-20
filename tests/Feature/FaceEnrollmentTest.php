<?php

namespace Tests\Feature;

use App\Models\FaceDescriptor;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FaceEnrollmentTest extends TestCase
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

    private function siswaDenganSampel(int $jumlah): Siswa
    {
        $kelas = Kelas::create([
            'nama_kelas' => 'X RPL 1',
            'jurusan' => 'RPL',
            'tingkat' => 'X',
        ]);

        $siswa = Siswa::create([
            'nis' => '1001',
            'nama' => 'Budi',
            'jenis_kelamin' => 'L',
            'kelas_id' => $kelas->id,
        ]);

        for ($i = 0; $i < $jumlah; $i++) {
            $siswa->faceDescriptors()->create([
                'descriptor' => array_fill(0, 128, 0.1 + $i),
            ]);
        }

        return $siswa;
    }

    public function test_hapus_satu_sampel_tidak_menghapus_sampel_lain(): void
    {
        $this->actingAs($this->admin());
        $siswa = $this->siswaDenganSampel(5);
        $ids = $siswa->faceDescriptors()->pluck('id');
        $dihapus = $ids->first();

        $this->delete("/siswa/{$siswa->id}/face/{$dihapus}")->assertRedirect();

        $this->assertDatabaseMissing('face_descriptors', ['id' => $dihapus]);
        $this->assertEquals(4, FaceDescriptor::where('siswa_id', $siswa->id)->count());
    }

    public function test_tidak_bisa_hapus_sampel_milik_siswa_lain(): void
    {
        $this->actingAs($this->admin());
        $siswaA = $this->siswaDenganSampel(1);

        $kelasB = Kelas::create(['nama_kelas' => 'XI TKJ 1', 'jurusan' => 'TKJ', 'tingkat' => 'XI']);
        $siswaB = Siswa::create([
            'nis' => '2002',
            'nama' => 'Siti',
            'jenis_kelamin' => 'P',
            'kelas_id' => $kelasB->id,
        ]);

        $sampelA = $siswaA->faceDescriptors()->first();

        $this->delete("/siswa/{$siswaB->id}/face/{$sampelA->id}")->assertNotFound();

        $this->assertDatabaseHas('face_descriptors', ['id' => $sampelA->id]);
    }

    public function test_halaman_enroll_menampilkan_daftar_sampel_tersimpan(): void
    {
        $this->actingAs($this->admin());
        $siswa = $this->siswaDenganSampel(2);

        $this->get(route('siswa.enroll', $siswa))
            ->assertOk()
            ->assertSee('Sampel tersimpan')
            ->assertSee('Sampel #1')
            ->assertSee('Sampel #2');
    }
}
