<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LaporanControllerTest extends TestCase
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

    public function test_libur_dalam_periode_ikut_hitung_libur_mingguan_bukan_cuma_baris_manual(): void
    {
        $this->actingAs($this->admin());

        // Sabtu dikonfigurasi libur rutin, tidak ada baris hari_libur manual sama sekali.
        Pengaturan::get()->update(['hari_libur_mingguan' => [6]]);

        // 2026-07-01 (Rabu) s.d. 2026-07-14 (Selasa): dua hari Sabtu (04 & 11 Juli),
        // ditambah satu libur manual (misalnya tanggal merah 08 Juli).
        HariLibur::create(['tanggal' => '2026-07-08', 'keterangan' => 'Libur nasional']);

        $response = $this->get(route('laporan.index', ['dari' => '2026-07-01', 'sampai' => '2026-07-14']));

        $response->assertOk();
        // 2 Sabtu (04, 11) + 1 libur manual (08) = 3 hari libur dalam periode.
        $response->assertViewHas('liburDalamPeriode', 3);
    }

    public function test_export_tetap_bisa_diakses_dengan_libur_mingguan_aktif(): void
    {
        $this->actingAs($this->admin());
        Pengaturan::get()->update(['hari_libur_mingguan' => [0, 6]]);

        $this->get(route('laporan.excel', ['dari' => '2026-07-01', 'sampai' => '2026-07-14']))->assertOk();
        $this->get(route('laporan.pdf', ['dari' => '2026-07-01', 'sampai' => '2026-07-14']))->assertOk();
    }

    public function test_wali_kelas_tidak_bisa_lihat_nama_kelas_lain_lewat_filter_pdf(): void
    {
        $kelasSendiri = Kelas::create(['nama_kelas' => 'X RPL 1', 'jurusan' => 'RPL', 'tingkat' => 'X']);
        $kelasLain = Kelas::create(['nama_kelas' => 'XI TKJ 1', 'jurusan' => 'TKJ', 'tingkat' => 'XI']);
        $wali = $this->waliKelas($kelasSendiri);

        $this->actingAs($wali);

        // kelasCakupan() (dipakai judul PDF & nama file) harus null kalau
        // kelas_id di luar cakupan wali kelas ini -- bukan kelas orang lain
        // yang ditampilkan/di-slug-kan ke nama file, meski isi datanya
        // (queryLaporan) sudah lebih dulu kosong lewat visibleTo().
        $pdf = $this->get(route('laporan.pdf', ['kelas_id' => $kelasLain->id]));

        $pdf->assertOk();
        $contentDisposition = $pdf->headers->get('content-disposition');
        $this->assertStringNotContainsString('xi-tkj-1', strtolower($contentDisposition));
    }

    public function test_laporan_kelas_lama_tidak_berubah_setelah_siswa_pindah_kelas(): void
    {
        $this->actingAs($this->admin());

        $kelasLama = Kelas::create(['nama_kelas' => 'X RPL 1', 'jurusan' => 'RPL', 'tingkat' => 'X']);
        $kelasBaru = Kelas::create(['nama_kelas' => 'X RPL 2', 'jurusan' => 'RPL', 'tingkat' => 'X']);
        $siswa = Siswa::create([
            'nis' => '111', 'nama' => 'Budi', 'jenis_kelamin' => 'L', 'kelas_id' => $kelasLama->id,
        ]);

        // Absen dicatat SEBELUM siswa pindah kelas -- baris ini harus
        // menyimpan snapshot kelas_id = kelasLama selamanya.
        $this->post('/absensi/manual', [
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-10',
            'status' => 'hadir',
        ])->assertRedirect();

        $siswa->update(['kelas_id' => $kelasBaru->id]);

        $laporanKelasLama = $this->get(route('laporan.index', [
            'dari' => '2026-07-01', 'sampai' => '2026-07-14', 'kelas_id' => $kelasLama->id,
        ]));
        $laporanKelasLama->assertOk();
        $this->assertCount(1, $laporanKelasLama->viewData('data'));

        $laporanKelasBaru = $this->get(route('laporan.index', [
            'dari' => '2026-07-01', 'sampai' => '2026-07-14', 'kelas_id' => $kelasBaru->id,
        ]));
        $laporanKelasBaru->assertOk();
        $this->assertCount(0, $laporanKelasBaru->viewData('data'));
    }

    /**
     * Akses (visibleTo) sengaja tetap ikut kelas siswa SEKARANG -- konsisten
     * dengan Siswa::visibleTo/Kelas::visibleTo di seluruh app (lihat komentar
     * di Absensi::scopeVisibleTo). Wali kelas baru otomatis bisa lihat
     * seluruh riwayat murid barunya termasuk sebelum pindah, tapi begitu dia
     * filter eksplisit ke kelas binaannya sendiri, baris sebelum kepindahan
     * (snapshot-nya masih kelas lama) tidak boleh ikut ketarik.
     */
    public function test_wali_kelas_baru_lihat_riwayat_lengkap_tapi_filter_kelas_sendiri_kecualikan_baris_sebelum_pindah(): void
    {
        $kelasLama = Kelas::create(['nama_kelas' => 'X RPL 1', 'jurusan' => 'RPL', 'tingkat' => 'X']);
        $kelasBaru = Kelas::create(['nama_kelas' => 'X RPL 2', 'jurusan' => 'RPL', 'tingkat' => 'X']);
        $waliLama = $this->waliKelas($kelasLama);
        $waliBaru = User::create([
            'name' => 'Wali Baru', 'email' => 'walibaru@test.test', 'password' => Hash::make('password'), 'role' => 'wali_kelas',
        ]);
        $kelasBaru->update(['wali_kelas_id' => $waliBaru->id]);

        $siswa = Siswa::create([
            'nis' => '222', 'nama' => 'Rina', 'jenis_kelamin' => 'P', 'kelas_id' => $kelasLama->id,
        ]);

        $this->actingAs($waliLama);
        $this->post('/absensi/manual', [
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-07-10',
            'status' => 'hadir',
        ])->assertRedirect();

        $siswa->update(['kelas_id' => $kelasBaru->id]);

        // Wali kelas baru: tanpa filter, riwayat lengkap (termasuk sebelum
        // pindah) tetap kelihatan -- ini muridnya SEKARANG.
        $tanpaFilter = $this->actingAs($waliBaru)
            ->get(route('laporan.index', ['dari' => '2026-07-01', 'sampai' => '2026-07-14']));
        $tanpaFilter->assertOk();
        $this->assertCount(1, $tanpaFilter->viewData('data'));

        // Tapi begitu difilter eksplisit ke kelas binaannya sendiri, baris
        // yang snapshot-nya masih kelas lama tidak boleh ikut (itu bukan
        // riwayat kelas X RPL 2, biarpun muridnya sudah di sana sekarang).
        $filterKelasSendiri = $this->actingAs($waliBaru)
            ->get(route('laporan.index', [
                'dari' => '2026-07-01', 'sampai' => '2026-07-14', 'kelas_id' => $kelasBaru->id,
            ]));
        $filterKelasSendiri->assertOk();
        $this->assertCount(0, $filterKelasSendiri->viewData('data'));

        // Wali kelas lama: siswanya sudah bukan murid binaannya lagi, jadi
        // baris ini juga tidak lagi kelihatan buat dia sama sekali TANPA
        // filter (browsing umum tetap berbasis roster sekarang)...
        $waliLamaLihat = $this->actingAs($waliLama)
            ->get(route('laporan.index', ['dari' => '2026-07-01', 'sampai' => '2026-07-14']));
        $waliLamaLihat->assertOk();
        $this->assertCount(0, $waliLamaLihat->viewData('data'));

        // ...TAPI begitu wali kelas lama filter eksplisit ke kelas
        // binaannya SENDIRI (kelasLama, yang tetap dia bina), baris riwayat
        // ini harus tetap muncul -- dia berhak lihat laporan historis kelas
        // binaannya sendiri, terlepas dari siswanya sudah pindah atau
        // belum. Ini bug yang diperbaiki: sebelumnya di-AND dengan
        // Absensi::visibleTo() (roster SEKARANG) sehingga baris ini ikut
        // hilang dari laporan kelas asalnya sendiri.
        $waliLamaFilterKelasSendiri = $this->actingAs($waliLama)
            ->get(route('laporan.index', [
                'dari' => '2026-07-01', 'sampai' => '2026-07-14', 'kelas_id' => $kelasLama->id,
            ]));
        $waliLamaFilterKelasSendiri->assertOk();
        $this->assertCount(1, $waliLamaFilterKelasSendiri->viewData('data'));
    }

    /**
     * Kebocoran yang sekalian ketutup oleh fix yang sama: sebelumnya wali
     * kelas bisa "mengintip" baris dari kelas yang TIDAK PERNAH dia bina,
     * cuma dengan filter kelas_id ke kelas itu, asalkan siswanya kebetulan
     * SEKARANG ada di kelas binaannya (roster overlap via AND lama).
     * Sekarang filter kelas_id diotorisasi lewat Kelas::visibleTo() dulu,
     * jadi kelas yang bukan binaannya selalu kosong, siapa pun siswanya.
     */
    public function test_wali_kelas_tidak_bisa_filter_kelas_yang_bukan_binaannya_walau_siswanya_pernah_di_sana(): void
    {
        $kelasLama = Kelas::create(['nama_kelas' => 'X RPL 1', 'jurusan' => 'RPL', 'tingkat' => 'X']);
        $kelasBaru = Kelas::create(['nama_kelas' => 'X RPL 2', 'jurusan' => 'RPL', 'tingkat' => 'X']);
        $waliBaru = $this->waliKelas($kelasBaru);

        $siswa = Siswa::create([
            'nis' => '333', 'nama' => 'Dedi', 'jenis_kelamin' => 'L', 'kelas_id' => $kelasLama->id,
        ]);
        Absensi::create([
            'siswa_id' => $siswa->id, 'kelas_id' => $kelasLama->id, 'tanggal' => '2026-07-10',
            'jam_masuk' => '07:00:00', 'status' => 'hadir', 'metode' => 'face',
        ]);
        $siswa->update(['kelas_id' => $kelasBaru->id]);

        $response = $this->actingAs($waliBaru)
            ->get(route('laporan.index', [
                'dari' => '2026-07-01', 'sampai' => '2026-07-14', 'kelas_id' => $kelasLama->id,
            ]));
        $response->assertOk();
        $this->assertCount(0, $response->viewData('data'));
    }
}
