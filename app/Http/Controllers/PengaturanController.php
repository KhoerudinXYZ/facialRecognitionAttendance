<?php

namespace App\Http\Controllers;

use App\Models\HariLibur;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function edit(): View
    {
        $pengaturan = Pengaturan::get();
        $efektifSekarang = $pengaturan->waktuSekarang();
        $isLibur = HariLibur::isLibur($efektifSekarang->copy()->startOfDay());

        return view('pengaturan.edit', compact('pengaturan', 'efektifSekarang', 'isLibur'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_sekolah' => ['required', 'string', 'max:150'],
            'jam_masuk' => ['required', 'date_format:H:i'],
            'batas_terlambat' => ['required', 'date_format:H:i'],
            'mulai_pulang' => ['required', 'date_format:H:i'],
        ]);

        Pengaturan::get()->update($data);

        return back()->with('success', 'Pengaturan disimpan.');
    }

    /**
     * Testing only: set/reset jam simulasi yang dipakai AbsensiRecorder
     * sebagai pengganti jam asli. Boleh dihapus kapan saja bersama field
     * & UI terkait tanpa mempengaruhi alur normal.
     */
    public function updateSimulasi(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'simulasi_waktu' => ['nullable', 'date'],
        ]);

        Pengaturan::get()->update($data);

        return back()->with('success', $data['simulasi_waktu']
            ? 'Simulasi waktu diaktifkan.'
            : 'Simulasi waktu direset, kembali pakai jam asli.');
    }

    /**
     * Set/reset titik sekolah + radius toleransi buat verifikasi lokasi GPS
     * saat absen mandiri. All-or-nothing: kalau salah satu field diisi,
     * dua lainnya wajib ikut diisi (lihat Pengaturan::lokasiAktif()).
     */
    public function updateLokasi(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lokasi_lat' => ['nullable', 'required_with:lokasi_lng,lokasi_radius_meter', 'numeric', 'between:-90,90'],
            'lokasi_lng' => ['nullable', 'required_with:lokasi_lat,lokasi_radius_meter', 'numeric', 'between:-180,180'],
            'lokasi_radius_meter' => ['nullable', 'required_with:lokasi_lat,lokasi_lng', 'integer', 'min:10', 'max:5000'],
        ]);

        Pengaturan::get()->update($data);

        return back()->with('success', $data['lokasi_lat']
            ? 'Verifikasi lokasi diaktifkan.'
            : 'Verifikasi lokasi dinonaktifkan.');
    }

    /**
     * Set/reset hari dalam seminggu yang otomatis dianggap libur (mis.
     * Sabtu & Minggu), supaya HariLibur::isLibur() tidak perlu satu baris
     * manual per akhir pekan. Checkbox yang tidak dicentang tidak terkirim
     * sama sekali di request, jadi kosong = reset ke "tidak ada" (null).
     */
    public function updateLiburMingguan(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'hari_libur_mingguan' => ['nullable', 'array'],
            'hari_libur_mingguan.*' => ['integer', 'between:0,6'],
        ]);

        $hari = array_map('intval', $data['hari_libur_mingguan'] ?? []);

        Pengaturan::get()->update(['hari_libur_mingguan' => $hari === [] ? null : $hari]);

        return back()->with('success', $hari === []
            ? 'Libur mingguan otomatis dinonaktifkan.'
            : 'Libur mingguan otomatis disimpan.');
    }
}
