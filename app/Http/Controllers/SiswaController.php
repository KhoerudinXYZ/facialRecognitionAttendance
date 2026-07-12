<?php

namespace App\Http\Controllers;

use App\Models\HariLibur;
use App\Models\Kelas;
use App\Models\Pengaturan;
use App\Models\Siswa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiswaController extends Controller
{
    public function index(Request $request): View
    {
        $today = Pengaturan::sekarang()->startOfDay();

        $query = Siswa::with('kelas.waliKelas')
            ->withCount('faceDescriptors')
            ->with(['absensi' => fn ($q) => $q->whereDate('tanggal', $today)])
            ->visibleTo($request->user());

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('nama', 'like', "%{$q}%")
                    ->orWhere('nis', 'like', "%{$q}%");
            });
        }

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->integer('kelas_id'));
        }

        $siswa = $query->orderBy('nama')->paginate(15)->withQueryString();
        $kelasList = Kelas::visibleTo($request->user())->orderBy('nama_kelas')->get();
        $isLibur = HariLibur::isLibur($today);

        return view('siswa.index', compact('siswa', 'kelasList', 'isLibur'));
    }

    public function create(Request $request): View
    {
        $kelasList = Kelas::visibleTo($request->user())->with('waliKelas')->orderBy('nama_kelas')->get();

        return view('siswa.create', compact('kelasList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $this->authorize('create', [Siswa::class, Kelas::findOrFail($data['kelas_id'])]);

        $data['foto'] = $this->handleFoto($request);

        $siswa = Siswa::create($data);

        return redirect()->route('siswa.enroll', $siswa)
            ->with('success', 'Siswa ditambahkan. Silakan daftarkan wajah siswa.');
    }

    public function show(Request $request, Siswa $siswa): View
    {
        $this->authorize('view', $siswa);

        $siswa->load('kelas', 'faceDescriptors');
        $riwayat = $siswa->absensi()->latest('tanggal')->take(20)->get();

        return view('siswa.show', compact('siswa', 'riwayat'));
    }

    public function edit(Request $request, Siswa $siswa): View
    {
        $this->authorize('update', $siswa);

        $siswa->load('kelas.waliKelas');
        $kelasList = Kelas::visibleTo($request->user())->with('waliKelas')->orderBy('nama_kelas')->get();

        return view('siswa.edit', compact('siswa', 'kelasList'));
    }

    public function update(Request $request, Siswa $siswa): RedirectResponse
    {
        $this->authorize('update', $siswa);

        $data = $this->validateData($request, $siswa->id);

        if ($request->hasFile('foto')) {
            if ($siswa->foto) {
                Storage::disk('public')->delete($siswa->foto);
            }
            $data['foto'] = $this->handleFoto($request);
        }

        $siswa->update($data);

        return redirect()->route('siswa.index')->with('success', 'Data siswa diperbarui.');
    }

    public function destroy(Siswa $siswa): RedirectResponse
    {
        $this->authorize('delete', $siswa);

        if ($siswa->foto) {
            Storage::disk('public')->delete($siswa->foto);
        }
        $siswa->delete();

        return redirect()->route('siswa.index')->with('success', 'Siswa berhasil dihapus.');
    }

    /**
     * Halaman pendaftaran (enroll) wajah siswa.
     */
    public function enroll(Siswa $siswa): View
    {
        $this->authorize('enroll', $siswa);

        $siswa->load('faceDescriptors');

        return view('siswa.enroll', compact('siswa'));
    }

    /**
     * Reset akun login siswa (kosongkan username/password) supaya siswa
     * bisa registrasi ulang dengan NIS-nya. Tidak ada alur reset password
     * via email karena siswa umumnya tidak punya email sekolah.
     */
    public function resetAccount(Siswa $siswa): RedirectResponse
    {
        $this->authorize('update', $siswa);

        $siswa->username = null;
        $siswa->password = null;
        $siswa->save();

        return back()->with('success', "Akun login {$siswa->nama} berhasil direset. Siswa perlu registrasi ulang.");
    }

    /**
     * Halaman upload file import Excel.
     */
    public function importForm(): View
    {
        return view('siswa.import');
    }

    /**
     * Unduh template kosong untuk diisi lalu diimport.
     */
    public function importTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $writer = new XlsxWriter();
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(['NIS', 'NISN', 'Nama', 'Jenis Kelamin (L/P)', 'Kelas', 'Aktif (Y/N)']));
            $writer->addRow(Row::fromValues(['12345', '0012345678', 'Contoh Nama Siswa', 'L', 'X RPL 1', 'Y']));
            $writer->close();
        }, 'template-import-siswa.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import banyak siswa sekaligus dari file Excel.
     * Baris bermasalah (kelas tak ditemukan, NIS duplikat, dst) dilewati &
     * dilaporkan, tanpa menggagalkan baris lain yang valid.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx'],
        ]);

        $kelasMap = Kelas::visibleTo($request->user())->get()->keyBy(fn (Kelas $k) => strtolower(trim($k->nama_kelas)));

        $reader = new XlsxReader();
        $reader->open($request->file('file')->getRealPath());

        $created = 0;
        $errors = [];
        $seenNis = [];
        $rowNum = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowNum++;
                if ($rowNum === 1) {
                    continue; // baris header
                }

                $cells = array_pad($row->toArray(), 6, null);
                [$nis, $nisn, $nama, $jk, $kelasNama, $aktif] = $cells;

                $nis = $this->normalizeCell($nis);
                $nama = $this->normalizeCell($nama);

                if ($nis === '' && $nama === '') {
                    continue; // baris kosong
                }

                if ($nis === '') {
                    $errors[] = "Baris {$rowNum}: NIS kosong.";
                    continue;
                }

                if ($nama === '') {
                    $errors[] = "Baris {$rowNum}: Nama kosong.";
                    continue;
                }

                $jk = strtoupper($this->normalizeCell($jk)) ?: 'L';
                if (! in_array($jk, ['L', 'P'], true)) {
                    $errors[] = "Baris {$rowNum}: Jenis kelamin '{$jk}' harus L atau P.";
                    continue;
                }

                $kelasNama = $this->normalizeCell($kelasNama);
                $kelas = $kelasMap->get(strtolower($kelasNama));
                if (! $kelas) {
                    $errors[] = "Baris {$rowNum}: Kelas '{$kelasNama}' tidak ditemukan.";
                    continue;
                }

                if (isset($seenNis[$nis]) || Siswa::where('nis', $nis)->exists()) {
                    $errors[] = "Baris {$rowNum}: NIS {$nis} duplikat.";
                    continue;
                }

                Siswa::create([
                    'nis' => $nis,
                    'nisn' => $this->normalizeCell($nisn) ?: null,
                    'nama' => $nama,
                    'jenis_kelamin' => $jk,
                    'kelas_id' => $kelas->id,
                    'is_active' => strtoupper($this->normalizeCell($aktif)) !== 'N',
                ]);

                $seenNis[$nis] = true;
                $created++;
            }
        }

        $reader->close();

        return redirect()->route('siswa.import.form')
            ->with('success', "{$created} siswa berhasil diimport.")
            ->with('import_errors', $errors);
    }

    /**
     * Rapikan nilai sel Excel jadi string (angka seperti NIS/NISN sering
     * terbaca sebagai float oleh reader, mis. 12345 -> 12345.0).
     */
    private function normalizeCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_float($value) && floor($value) === $value) {
            return (string) (int) $value;
        }

        return trim((string) $value);
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $kelasIds = Kelas::visibleTo($request->user())->pluck('id');

        return $request->validate([
            'nis' => ['required', 'string', 'max:30', 'unique:siswa,nis' . ($ignoreId ? ",{$ignoreId}" : '')],
            'nisn' => ['nullable', 'string', 'max:30'],
            'nama' => ['required', 'string', 'max:150'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'kelas_id' => ['required', Rule::in($kelasIds)],
            'is_active' => ['nullable', 'boolean'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ]);
    }

    private function handleFoto(Request $request): ?string
    {
        if ($request->hasFile('foto')) {
            return $request->file('foto')->store('siswa', 'public');
        }

        return null;
    }
}
