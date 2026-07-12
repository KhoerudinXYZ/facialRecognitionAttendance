<?php

namespace App\Http\Controllers;

use App\Models\FaceDescriptor;
use App\Models\Siswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FaceEnrollmentController extends Controller
{
    /**
     * Simpan satu (atau beberapa) face descriptor untuk siswa.
     * Dipanggil dari browser (face-enroll.js) via Axios.
     */
    public function store(Request $request, Siswa $siswa): JsonResponse
    {
        $this->authorize('enroll', $siswa);

        $validated = $request->validate([
            'descriptors' => ['required', 'array', 'min:1'],
            'descriptors.*' => ['array', 'size:128'],
            'descriptors.*.*' => ['numeric'],
        ]);

        foreach ($validated['descriptors'] as $descriptor) {
            $siswa->faceDescriptors()->create([
                'descriptor' => array_map('floatval', $descriptor),
            ]);
        }

        return response()->json([
            'message' => 'Wajah berhasil didaftarkan.',
            'total' => $siswa->faceDescriptors()->count(),
        ]);
    }

    /**
     * Hapus semua descriptor siswa (daftar ulang).
     */
    public function destroy(Siswa $siswa): JsonResponse
    {
        $this->authorize('enroll', $siswa);

        $siswa->faceDescriptors()->delete();

        return response()->json(['message' => 'Data wajah dihapus.']);
    }

    /**
     * Hapus satu sampel descriptor saja (perbaiki sampel yang salah
     * tanpa perlu daftar ulang semua sampel).
     */
    public function destroyOne(Siswa $siswa, FaceDescriptor $faceDescriptor): RedirectResponse
    {
        $this->authorize('enroll', $siswa);

        $faceDescriptor->delete();

        return back()->with('success', 'Sampel wajah dihapus.');
    }
}
