<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class SiswaImportTest extends TestCase
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

    private function buildXlsx(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'import') . '.xlsx';

        $writer = new Writer();
        $writer->openToFile($path);
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return new UploadedFile($path, 'siswa.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    public function test_halaman_import_dan_template_bisa_diakses(): void
    {
        $this->actingAs($this->admin());

        $this->get('/siswa/import')->assertOk();
        $this->get('/siswa/import/template')->assertOk();
    }

    public function test_import_excel_membuat_siswa_valid_dan_melewati_baris_bermasalah(): void
    {
        $this->actingAs($this->admin());
        Kelas::create([
            'nama_kelas' => 'X RPL 1',
            'jurusan' => 'RPL',
            'tingkat' => 'X',
        ]);

        $file = $this->buildXlsx([
            ['NIS', 'NISN', 'Nama', 'Jenis Kelamin (L/P)', 'Kelas', 'Aktif (Y/N)'],
            ['1001', '0011', 'Budi Santoso', 'L', 'X RPL 1', 'Y'],
            ['1002', '', 'Siti Aminah', 'P', 'Kelas Tidak Ada', 'Y'],
            ['', '', 'Tanpa NIS', 'L', 'X RPL 1', 'Y'],
        ]);

        $response = $this->post('/siswa/import', ['file' => $file]);

        $response->assertRedirect(route('siswa.import.form'));

        $this->assertDatabaseHas('siswa', ['nis' => '1001', 'nama' => 'Budi Santoso']);
        $this->assertDatabaseMissing('siswa', ['nis' => '1002']);
        $this->assertDatabaseMissing('siswa', ['nama' => 'Tanpa NIS']);

        $errors = session('import_errors');
        $this->assertCount(2, $errors);
    }

    public function test_import_melewati_nis_duplikat(): void
    {
        $this->actingAs($this->admin());
        Kelas::create([
            'nama_kelas' => 'X RPL 1',
            'jurusan' => 'RPL',
            'tingkat' => 'X',
        ]);

        $file = $this->buildXlsx([
            ['NIS', 'NISN', 'Nama', 'Jenis Kelamin (L/P)', 'Kelas', 'Aktif (Y/N)'],
            ['2001', '', 'Andi', 'L', 'X RPL 1', 'Y'],
            ['2001', '', 'Andi Duplikat', 'L', 'X RPL 1', 'Y'],
        ]);

        $this->post('/siswa/import', ['file' => $file]);

        $this->assertEquals(1, \App\Models\Siswa::where('nis', '2001')->count());
        $this->assertCount(1, session('import_errors'));
    }

    public function test_import_membaca_no_hp_orang_tua_di_kolom_terakhir(): void
    {
        $this->actingAs($this->admin());
        Kelas::create([
            'nama_kelas' => 'X RPL 1',
            'jurusan' => 'RPL',
            'tingkat' => 'X',
        ]);

        $file = $this->buildXlsx([
            ['NIS', 'NISN', 'Nama', 'Jenis Kelamin (L/P)', 'Kelas', 'Aktif (Y/N)', 'No. WhatsApp Orang Tua'],
            ['3001', '', 'Rina', 'P', 'X RPL 1', 'Y', '628123456789'],
        ]);

        $this->post('/siswa/import', ['file' => $file]);

        $this->assertDatabaseHas('siswa', ['nis' => '3001', 'no_hp_orang_tua' => '628123456789']);
    }

    public function test_import_membaca_email_orang_tua_di_kolom_paling_akhir(): void
    {
        $this->actingAs($this->admin());
        Kelas::create([
            'nama_kelas' => 'X RPL 1',
            'jurusan' => 'RPL',
            'tingkat' => 'X',
        ]);

        $file = $this->buildXlsx([
            ['NIS', 'NISN', 'Nama', 'Jenis Kelamin (L/P)', 'Kelas', 'Aktif (Y/N)', 'No. WhatsApp Orang Tua', 'Email Orang Tua'],
            ['4001', '', 'Doni', 'L', 'X RPL 1', 'Y', '628123456789', 'ortu@example.com'],
        ]);

        $this->post('/siswa/import', ['file' => $file]);

        $this->assertDatabaseHas('siswa', [
            'nis' => '4001',
            'no_hp_orang_tua' => '628123456789',
            'email_orang_tua' => 'ortu@example.com',
        ]);
    }
}
