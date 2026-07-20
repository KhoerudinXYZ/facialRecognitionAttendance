<?php

namespace Tests\Feature;

use App\Mail\PengajuanIzinBaruMail;
use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\PengajuanIzin;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
        PengajuanIzin::flushEventListeners();
        Absensi::flushEventListeners();
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

    public function test_race_dua_pengajuan_hampir_bersamaan_tidak_saling_menimpa(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-07-13 07:00:00');

        $siswa = $this->siswa($this->kelas());
        $this->actingAs($siswa, 'siswa');

        // Simulasikan request lain yang menang race: baris pesaing masuk
        // (insert mentah, bukan lewat controller) TEPAT di antara
        // pengecekan "belum ada pengajuan" milik store() dan INSERT-nya
        // sendiri -- momen paling sempit yang tidak bisa dipicu lewat dua
        // request sungguhan dalam test satu proses/thread.
        PengajuanIzin::creating(function () use ($siswa) {
            DB::table('pengajuan_izin')->insert([
                'siswa_id' => $siswa->id,
                // "00:00:00" -- samakan dengan format normalisasi cast
                // 'date' Eloquent, lihat catatan di AbsensiRecorderTest.
                'tanggal' => '2026-07-13 00:00:00',
                'jenis' => 'izin',
                'keterangan' => 'Acara keluarga (pemenang race)',
                'bukti' => 'bukti-izin/pemenang.jpg',
                'status' => 'menunggu',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $response = $this->post('/portal/izin', [
            'jenis' => 'sakit',
            'keterangan' => 'Demam (kalah race)',
            'bukti' => UploadedFile::fake()->image('surat.jpg'),
        ]);

        // Yang kalah race harus ditolak dengan jelas, bukan diam-diam
        // menimpa data pemenang race (perilaku updateOrCreate() lama).
        $response->assertSessionHas('error');
        $this->assertSame(1, PengajuanIzin::where('siswa_id', $siswa->id)->count());
        $this->assertDatabaseHas('pengajuan_izin', [
            'siswa_id' => $siswa->id,
            'jenis' => 'izin',
            'keterangan' => 'Acara keluarga (pemenang race)',
        ]);

        // File yang di-upload request yang kalah harus dibersihkan, bukan
        // ditinggal nyangkut yatim piatu di storage.
        $this->assertCount(0, Storage::disk('public')->allFiles('bukti-izin'));
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

    public function test_race_approve_dengan_scan_wajah_konkuren_tetap_tersimpan(): void
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

        // Simulasikan AbsensiRecorder (scan wajah siswa yang sama) menang
        // race TEPAT di antara pengecekan first() milik approve() dan
        // INSERT-nya sendiri -- momen paling sempit yang tidak bisa dipicu
        // lewat dua request sungguhan dalam test satu proses/thread.
        Absensi::creating(function () use ($siswa, $kelas) {
            DB::table('absensi')->insert([
                'siswa_id' => $siswa->id,
                'kelas_id' => $kelas->id,
                'tanggal' => '2026-07-13 00:00:00',
                'jam_masuk' => '07:00:00',
                'status' => 'hadir',
                'metode' => 'face',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->actingAs($this->admin());
        $this->post("/pengajuan-izin/{$pengajuan->id}/approve")->assertRedirect();

        // approve() TETAP harus berhasil (bukan 500) dan hasil akhirnya
        // status izin/sakit dengan jam kehadiran kosong, bukan baris hadir
        // pemenang race yang dibiarkan begitu saja.
        $this->assertSame(1, Absensi::where('siswa_id', $siswa->id)->count());
        $this->assertDatabaseHas('absensi', [
            'siswa_id' => $siswa->id,
            'status' => 'sakit',
            'jam_masuk' => null,
            'jam_pulang' => null,
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

    public function test_admin_reject_menandai_alpha_dan_siswa_bisa_ajukan_ulang(): void
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

        $admin = $this->admin();
        $this->actingAs($admin);
        $this->post("/pengajuan-izin/{$pengajuan->id}/reject", [
            'catatan_admin' => 'Bukti tidak jelas',
        ])->assertRedirect();

        // Ditolak = tidak ada alasan resmi yang diterima, jadi hari itu
        // ditandai alpha di sini juga -- tidak menunggu AbsensiAlphaChecker
        // (yang mungkin sudah lewat run terakhirnya hari itu).
        $this->assertDatabaseHas('absensi', [
            'siswa_id' => $siswa->id,
            'status' => 'alpha',
        ]);
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

        // Approve pengajuan baru harus menimpa baris alpha jadi sakit lagi,
        // dengan jam_masuk/jam_pulang tetap kosong (lihat fix #1).
        $this->actingAs($admin);
        $this->post("/pengajuan-izin/{$pengajuan->id}/approve")->assertRedirect();

        $this->assertDatabaseHas('absensi', [
            'siswa_id' => $siswa->id,
            'status' => 'sakit',
            'jam_masuk' => null,
            'jam_pulang' => null,
        ]);
    }

    public function test_reject_tidak_menimpa_baris_absensi_yang_sudah_ada(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-07-13 15:00:00');

        $kelas = $this->kelas();
        $siswa = $this->siswa($kelas);
        Absensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'jam_masuk' => '07:00:00',
            'status' => 'hadir',
            'metode' => 'face',
        ]);
        $pengajuan = PengajuanIzin::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'jenis' => 'sakit',
            'keterangan' => 'Demam',
            'bukti' => 'bukti-izin/surat.jpg',
            'status' => 'menunggu',
        ]);

        $this->actingAs($this->admin());
        $this->post("/pengajuan-izin/{$pengajuan->id}/reject")->assertRedirect();

        // Baris hadir yang sudah ada (siswa ternyata masuk) tidak boleh
        // ditimpa jadi alpha oleh reject().
        $this->assertDatabaseHas('absensi', [
            'siswa_id' => $siswa->id,
            'status' => 'hadir',
            'jam_masuk' => '07:00:00',
        ]);
    }

    public function test_approve_membersihkan_jam_masuk_pulang_dari_baris_hadir_sebelumnya(): void
    {
        Storage::fake('public');
        $kelas = $this->kelas();
        $siswa = $this->siswa($kelas);
        Absensi::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'jam_masuk' => '07:00:00',
            'jam_pulang' => '13:00:00',
            'status' => 'hadir',
            'metode' => 'face',
        ]);
        $pengajuan = PengajuanIzin::create([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-13',
            'jenis' => 'sakit',
            'keterangan' => 'Demam mendadak siang',
            'bukti' => 'bukti-izin/surat.jpg',
            'status' => 'menunggu',
        ]);

        $this->actingAs($this->admin());
        $this->post("/pengajuan-izin/{$pengajuan->id}/approve")->assertRedirect();

        $this->assertDatabaseHas('absensi', [
            'siswa_id' => $siswa->id,
            'status' => 'sakit',
            'jam_masuk' => null,
            'jam_pulang' => null,
        ]);
    }

    public function test_wali_kelas_dinotifikasi_email_saat_ada_pengajuan_baru(): void
    {
        Storage::fake('public');
        Mail::fake();
        Carbon::setTestNow('2026-07-13 07:00:00');

        $kelas = $this->kelas();
        $this->waliKelas($kelas);
        $siswa = $this->siswa($kelas);
        $this->actingAs($siswa, 'siswa');

        $this->post('/portal/izin', [
            'jenis' => 'sakit',
            'keterangan' => 'Demam',
            'bukti' => UploadedFile::fake()->image('surat.jpg'),
        ])->assertRedirect(route('siswa.izin'));

        Mail::assertSent(PengajuanIzinBaruMail::class, fn ($mail) => $mail->hasTo('wali@test.test')
            && $mail->siswaNama === $siswa->nama
            && $mail->jenis === 'sakit');
    }

    public function test_tidak_error_kalau_kelas_belum_punya_wali_kelas(): void
    {
        Storage::fake('public');
        Mail::fake();
        Carbon::setTestNow('2026-07-13 07:00:00');

        $siswa = $this->siswa($this->kelas());
        $this->actingAs($siswa, 'siswa');

        $this->post('/portal/izin', [
            'jenis' => 'sakit',
            'keterangan' => 'Demam',
            'bukti' => UploadedFile::fake()->image('surat.jpg'),
        ])->assertRedirect(route('siswa.izin'));

        $this->assertDatabaseHas('pengajuan_izin', ['siswa_id' => $siswa->id]);
        Mail::assertNothingSent();
    }
}
