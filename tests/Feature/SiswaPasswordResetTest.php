<?php

namespace Tests\Feature;

use App\Mail\SiswaResetPasswordMail;
use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SiswaPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function kelas(): Kelas
    {
        return Kelas::create([
            'nama_kelas' => 'X RPL 1',
            'jurusan' => 'RPL',
            'tingkat' => 'X',
        ]);
    }

    private function siswaTerdaftar(array $overrides = []): Siswa
    {
        $siswa = Siswa::create(array_merge([
            'nis' => '1001',
            'nama' => 'Budi',
            'jenis_kelamin' => 'L',
            'kelas_id' => $this->kelas()->id,
            'email_orang_tua' => 'ortu@example.com',
        ], $overrides));

        $siswa->username = $overrides['username'] ?? 'budi01';
        $siswa->password = Hash::make('passwordLama123');
        $siswa->save();

        return $siswa;
    }

    /**
     * Ambil resetUrl dari mail yang benar-benar dikirim, lalu pecah jadi
     * token + query string supaya test bisa langsung memanggil endpoint
     * reset tanpa perlu parsing HTML.
     */
    private function ambilResetUrl(): string
    {
        $url = null;
        Mail::assertSent(SiswaResetPasswordMail::class, function ($mail) use (&$url) {
            $url = $mail->resetUrl;

            return true;
        });

        return $url;
    }

    public function test_siswa_terdaftar_bisa_minta_reset_link_dan_mengganti_password(): void
    {
        Mail::fake();
        $siswa = $this->siswaTerdaftar();

        $this->post('/portal/forgot-password', [
            'nis' => '1001',
            'email_orang_tua' => 'ortu@example.com',
        ])->assertSessionHasNoErrors();

        Mail::assertSent(SiswaResetPasswordMail::class, fn ($mail) => $mail->hasTo('ortu@example.com'));

        $url = parse_url($this->ambilResetUrl());
        parse_str($url['query'], $query);
        $token = basename($url['path']);

        $this->post('/portal/reset-password', [
            'token' => $token,
            'nis' => $query['nis'],
            'email_orang_tua' => $query['email_orang_tua'],
            'password' => 'passwordBaru456',
            'password_confirmation' => 'passwordBaru456',
        ])->assertRedirect(route('siswa.login'));

        $this->post('/portal/login', [
            'username' => 'budi01',
            'password' => 'passwordBaru456',
        ])->assertRedirect(route('siswa.dashboard'));
    }

    public function test_kombinasi_nis_dan_email_yang_tidak_cocok_ditolak(): void
    {
        Mail::fake();
        $this->siswaTerdaftar();

        $this->post('/portal/forgot-password', [
            'nis' => '1001',
            'email_orang_tua' => 'email-salah@example.com',
        ])->assertSessionHasErrors('nis');

        Mail::assertNothingSent();
    }

    public function test_siswa_yang_belum_registrasi_diarahkan_untuk_registrasi_dulu(): void
    {
        Mail::fake();
        Siswa::create([
            'nis' => '2002',
            'nama' => 'Siti',
            'jenis_kelamin' => 'P',
            'kelas_id' => $this->kelas()->id,
            'email_orang_tua' => 'ortu2@example.com',
        ]);

        $response = $this->post('/portal/forgot-password', [
            'nis' => '2002',
            'email_orang_tua' => 'ortu2@example.com',
        ]);

        $response->assertSessionHasErrors('nis');
        $this->assertStringContainsString('belum diregistrasi', session('errors')->first('nis'));
        Mail::assertNothingSent();
    }

    public function test_kakak_adik_berbagi_email_orang_tua_tetap_bisa_dibedakan_lewat_nis(): void
    {
        Mail::fake();
        $kelas = $this->kelas();
        $kakak = $this->siswaTerdaftar(['nis' => '3001', 'nama' => 'Kakak', 'kelas_id' => $kelas->id, 'email_orang_tua' => 'ortu.bersama@example.com', 'username' => 'kakak01']);
        $adik = $this->siswaTerdaftar(['nis' => '3002', 'nama' => 'Adik', 'kelas_id' => $kelas->id, 'email_orang_tua' => 'ortu.bersama@example.com', 'username' => 'adik01']);

        // Reset punya kakak dulu, sampai selesai.
        $this->post('/portal/forgot-password', ['nis' => '3001', 'email_orang_tua' => 'ortu.bersama@example.com']);
        $url = parse_url($this->ambilResetUrl());
        parse_str($url['query'], $query);
        $this->post('/portal/reset-password', [
            'token' => basename($url['path']),
            'nis' => $query['nis'],
            'email_orang_tua' => $query['email_orang_tua'],
            'password' => 'passwordKakak789',
            'password_confirmation' => 'passwordKakak789',
        ])->assertRedirect(route('siswa.login'));

        // Password adik tidak ikut berubah.
        $adik->refresh();
        $this->assertTrue(Hash::check('passwordLama123', $adik->password));
    }
}
