<?php

namespace Tests\Feature;

use App\Mail\WhatsAppGagalBeruntunMail;
use App\Models\Kelas;
use App\Models\NotifikasiAbsensiLog;
use App\Models\Siswa;
use App\Models\User;
use App\Services\WhatsAppNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FonnteWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function bikinLog(string $messageId, string $status = 'terkirim'): NotifikasiAbsensiLog
    {
        return NotifikasiAbsensiLog::create([
            'siswa_nama' => 'Siswa Uji',
            'tanggal' => '2026-07-31',
            'jenis' => 'kehadiran',
            'kanal' => 'whatsapp',
            'kontak' => '6281234567890',
            'pesan' => 'pesan uji',
            'status' => $status,
            'fonnte_message_id' => $messageId,
        ]);
    }

    public function test_secret_salah_ditolak_404(): void
    {
        $this->bikinLog('80367170');

        $this->postJson('/webhooks/fonnte/secret-yang-salah', [
            'id' => '80367170',
            'state' => 0,
        ])->assertNotFound();

        $this->assertSame('terkirim', NotifikasiAbsensiLog::first()->status);
    }

    public function test_state_0_mengubah_status_jadi_gagal(): void
    {
        $log = $this->bikinLog('80367170');

        $this->postJson('/webhooks/fonnte/test-secret-fonnte-webhook', [
            'device' => '6281200000000',
            'id' => '80367170',
            'state' => 0,
            'status' => 'sent',
        ])->assertNoContent();

        $log->refresh();
        $this->assertSame('gagal', $log->status);
        $this->assertSame(0, $log->whatsapp_state);
        $this->assertNotNull($log->alasan_gagal);
    }

    public function test_state_selain_0_tidak_mengubah_status(): void
    {
        $log = $this->bikinLog('80367170');

        $this->postJson('/webhooks/fonnte/test-secret-fonnte-webhook', [
            'id' => '80367170',
            'state' => 2,
        ])->assertNoContent();

        $log->refresh();
        $this->assertSame('terkirim', $log->status);
        $this->assertSame(2, $log->whatsapp_state);
    }

    public function test_id_tidak_cocok_baris_manapun_tidak_error(): void
    {
        $this->bikinLog('80367170');

        $this->postJson('/webhooks/fonnte/test-secret-fonnte-webhook', [
            'id' => 'id-lain-yang-tidak-ada',
            'state' => 0,
        ])->assertNoContent();

        $this->assertSame('terkirim', NotifikasiAbsensiLog::first()->status);
    }

    public function test_state_0_beruntun_via_webhook_memicu_peringatan_admin(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $logs = [];
        for ($i = 1; $i <= 5; $i++) {
            $logs[] = $this->bikinLog("id-{$i}");
        }

        foreach ($logs as $log) {
            $this->postJson('/webhooks/fonnte/test-secret-fonnte-webhook', [
                'id' => $log->fonnte_message_id,
                'state' => 0,
            ])->assertNoContent();
        }

        Mail::assertSent(WhatsAppGagalBeruntunMail::class, 1);
        Mail::assertSent(WhatsAppGagalBeruntunMail::class, fn ($mail) => $mail->hasTo($admin->email));
    }

    public function test_alur_penuh_kirim_lalu_webhook_state_0_membalik_status(): void
    {
        config(['services.fonnte.token' => 'test-token']);
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true, 'id' => ['80367170']], 200)]);
        Carbon::setTestNow('2026-07-31 07:00:00');

        $kelas = Kelas::create(['nama_kelas' => 'X RPL 1', 'jurusan' => 'RPL', 'tingkat' => 'X']);
        $siswa = Siswa::create([
            'nis' => '00001',
            'nama' => 'Siswa Uji',
            'jenis_kelamin' => 'L',
            'kelas_id' => $kelas->id,
            'no_hp_orang_tua' => '081234567890',
        ]);

        app(WhatsAppNotifier::class)->kirimDanCatat($siswa, Carbon::now(), 'kehadiran', 'pesan uji');

        $log = NotifikasiAbsensiLog::first();
        $this->assertSame('terkirim', $log->status);
        $this->assertSame('80367170', $log->fonnte_message_id);

        $this->postJson('/webhooks/fonnte/test-secret-fonnte-webhook', [
            'id' => '80367170',
            'state' => 0,
        ])->assertNoContent();

        $this->assertSame('gagal', $log->refresh()->status);
    }
}
