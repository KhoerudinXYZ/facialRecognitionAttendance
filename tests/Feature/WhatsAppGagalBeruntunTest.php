<?php

namespace Tests\Feature;

use App\Mail\WhatsAppGagalBeruntunMail;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Services\AbsensiAlphaChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WhatsAppGagalBeruntunTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function siswaDenganNomorWa(string $nis): Siswa
    {
        $kelas = Kelas::firstOrCreate(
            ['nama_kelas' => 'X RPL 1'],
            ['jurusan' => 'RPL', 'tingkat' => 'X'],
        );

        return Siswa::create([
            'nis' => $nis,
            'nama' => "Siswa {$nis}",
            'jenis_kelamin' => 'L',
            'kelas_id' => $kelas->id,
            'no_hp_orang_tua' => '08123456' . $nis,
        ]);
    }

    public function test_admin_diperingatkan_setelah_lima_kegagalan_whatsapp_beruntun(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        config(['services.fonnte.token' => 'test-token']);
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => false], 500)]);
        Carbon::setTestNow('2026-07-13 20:00:00');

        for ($i = 1; $i <= 5; $i++) {
            $this->siswaDenganNomorWa("0000{$i}");
        }

        app(AbsensiAlphaChecker::class)->jalankan();

        Mail::assertSent(WhatsAppGagalBeruntunMail::class, 1);
        Mail::assertSent(WhatsAppGagalBeruntunMail::class, fn ($mail) => $mail->hasTo($admin->email));
    }

    public function test_admin_tidak_diperingatkan_berulang_kali_selama_masih_gagal(): void
    {
        Mail::fake();
        User::factory()->create(['role' => 'admin']);
        config(['services.fonnte.token' => 'test-token']);
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => false], 500)]);
        Carbon::setTestNow('2026-07-13 20:00:00');

        for ($i = 1; $i <= 8; $i++) {
            $this->siswaDenganNomorWa("0000{$i}");
        }

        app(AbsensiAlphaChecker::class)->jalankan();

        // 8 siswa gagal semua, tapi cuma 1 email peringatan -- bukan satu
        // tiap kegagalan setelah ambang tercapai.
        Mail::assertSent(WhatsAppGagalBeruntunMail::class, 1);
    }

    public function test_tidak_ada_peringatan_kalau_kegagalan_belum_mencapai_ambang(): void
    {
        Mail::fake();
        User::factory()->create(['role' => 'admin']);
        config(['services.fonnte.token' => 'test-token']);
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => false], 500)]);
        Carbon::setTestNow('2026-07-13 20:00:00');

        for ($i = 1; $i <= 4; $i++) {
            $this->siswaDenganNomorWa("0000{$i}");
        }

        app(AbsensiAlphaChecker::class)->jalankan();

        Mail::assertNotSent(WhatsAppGagalBeruntunMail::class);
    }
}
