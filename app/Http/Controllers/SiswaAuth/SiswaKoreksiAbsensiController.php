<?php

namespace App\Http\Controllers\SiswaAuth;

use App\Http\Controllers\Controller;
use App\Mail\KoreksiAbsensiBaruMail;
use App\Models\KoreksiAbsensi;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SiswaKoreksiAbsensiController extends Controller
{
    /**
     * Laporkan baris absensi tanggal tertentu salah, minta diperbaiki jadi
     * status_diminta. Beda dari pengajuan izin (soal hari ini/besok): ini
     * soal tanggal yang SUDAH ADA baris absensinya, siswa membantah apa
     * yang sudah tercatat -- jadi wajib ada baris Absensi lebih dulu untuk
     * tanggal itu, tidak bisa "koreksi" hari yang belum diproses sama
     * sekali.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var Siswa $siswa */
        $siswa = Auth::guard('siswa')->user();

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'status_diminta' => ['required', 'in:hadir,terlambat,izin,sakit'],
            'alasan' => ['required', 'string', 'max:255'],
            'bukti' => ['nullable', 'image', 'max:2048'],
        ]);

        $tanggal = Carbon::parse($validated['tanggal'])->startOfDay();

        // Pengaturan::sekarang() (bukan validation rule "before_or_equal:
        // today" bawaan Laravel) supaya ikut menghormati simulasi_waktu,
        // konsisten dengan seluruh alur absensi lain.
        if ($tanggal->isAfter(Pengaturan::sekarang()->startOfDay())) {
            return back()->with('error', 'Tidak bisa mengajukan koreksi untuk tanggal yang belum terjadi.');
        }

        $absensi = $siswa->absensi()->whereDate('tanggal', $tanggal)->first();
        if (! $absensi) {
            return back()->with('error', 'Belum ada catatan absensi untuk tanggal itu, tidak ada yang bisa dikoreksi.');
        }

        if ($absensi->status === $validated['status_diminta']) {
            return back()->with('error', 'Status absensi tanggal itu sudah sesuai, tidak perlu dikoreksi.');
        }

        $koreksiSebelumnya = $siswa->koreksiAbsensi()->whereDate('tanggal', $tanggal)->first();
        if ($koreksiSebelumnya && in_array($koreksiSebelumnya->status, ['menunggu', 'disetujui'], true)) {
            return back()->with('error', 'Kamu sudah punya pengajuan koreksi untuk tanggal itu.');
        }

        if ($koreksiSebelumnya && $koreksiSebelumnya->bukti) {
            Storage::disk('public')->delete($koreksiSebelumnya->bukti);
        }

        $buktiPath = $request->hasFile('bukti')
            ? $request->file('bukti')->store('bukti-koreksi', 'public')
            : null;

        $koreksi = $koreksiSebelumnya ?? new KoreksiAbsensi([
            'siswa_id' => $siswa->id,
            'tanggal' => $tanggal,
        ]);

        $koreksi->status_diminta = $validated['status_diminta'];
        $koreksi->alasan = $validated['alasan'];
        $koreksi->bukti = $buktiPath;
        $koreksi->status = 'menunggu';
        $koreksi->catatan_admin = null;
        $koreksi->reviewed_by = null;
        $koreksi->reviewed_at = null;

        try {
            $koreksi->save();
        } catch (UniqueConstraintViolationException) {
            // Dua submit nyaris bersamaan (double-klik, retry jaringan) --
            // lihat penjelasan yang sama di SiswaPengajuanIzinController.
            if ($buktiPath) {
                Storage::disk('public')->delete($buktiPath);
            }

            return back()->with('error', 'Kamu sudah punya pengajuan koreksi untuk tanggal itu.');
        }

        $this->notifikasiWaliKelas($siswa, $tanggal, $validated['status_diminta'], $validated['alasan']);

        return redirect()->route('siswa.riwayat', ['bulan' => $tanggal->format('Y-m')])
            ->with('success', 'Pengajuan koreksi berhasil dikirim, menunggu persetujuan.');
    }

    /**
     * Best effort — kegagalan kirim email tidak boleh menggagalkan
     * pengajuan siswa itu sendiri. Kalau kelas belum punya wali kelas
     * (atau wali kelasnya belum punya email), diam saja: admin tetap bisa
     * approve lewat halaman koreksi-absensi kapan saja.
     */
    private function notifikasiWaliKelas(Siswa $siswa, Carbon $tanggal, string $statusDiminta, string $alasan): void
    {
        $waliKelas = $siswa->kelas?->waliKelas;
        if (! $waliKelas?->email) {
            return;
        }

        try {
            Mail::to($waliKelas->email)->send(new KoreksiAbsensiBaruMail(
                $siswa->nama,
                $siswa->kelas->nama_kelas,
                $tanggal,
                $statusDiminta,
                $alasan,
            ));
        } catch (Throwable) {
            // Diam saja — lihat docblock method ini.
        }
    }
}
