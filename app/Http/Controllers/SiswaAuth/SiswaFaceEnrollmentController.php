<?php

namespace App\Http\Controllers\SiswaAuth;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SiswaFaceEnrollmentController extends Controller
{
    /**
     * Halaman status pendaftaran wajah (sebelum masuk ke kamera perekaman).
     */
    public function index(): View
    {
        /** @var Siswa $siswa */
        $siswa = Auth::guard('siswa')->user();
        $siswa->load('faceDescriptors');

        return view('siswa-auth.wajah', compact('siswa'));
    }

    public function create(): View
    {
        /** @var Siswa $siswa */
        $siswa = Auth::guard('siswa')->user();
        $siswa->load('faceDescriptors');

        return view('siswa-auth.enroll', compact('siswa'));
    }

    /**
     * Simpan sampel wajah untuk siswa yang sedang login. Identitas selalu
     * diambil dari sesi (guard siswa), bukan dari input request, supaya
     * satu siswa tidak bisa mendaftarkan wajah atas nama siswa lain.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'descriptors' => ['required', 'array', 'min:1'],
            'descriptors.*' => ['array', 'size:128'],
            'descriptors.*.*' => ['numeric'],
        ]);

        /** @var Siswa $siswa */
        $siswa = Auth::guard('siswa')->user();

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
}
