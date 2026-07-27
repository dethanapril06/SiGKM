<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use App\Models\Kelas;
use App\Models\MataKuliah;
use App\Models\Pengajar;
use App\Models\Perkuliahan;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PerkuliahanController extends Controller
{
    public function index(): View
    {
        $perkuliahan = Perkuliahan::with([
            'semester.tahunAkademik',
            'mataKuliah',
            'kelas',
            'pengajars.dosen',
        ])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.perkuliahan.index', compact('perkuliahan'));
    }

    public function create(): View
    {
        $semester = Semester::with('tahunAkademik')
            ->orderByDesc('is_active')
            ->latest()
            ->get();

        $mataKuliah = MataKuliah::query()
            ->where('is_active', true)
            ->orderBy('kode_mk')
            ->get();

        $kelas = Kelas::query()
            ->orderBy('nama_kelas')
            ->get();

        $dosen = Dosen::query()
            ->orderBy('nama_dosen')
            ->get();

        return view('master.perkuliahan.create', compact(
            'semester',
            'mataKuliah',
            'kelas',
            'dosen'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'mata_kuliah_id' => ['required', 'exists:mata_kuliahs,id'],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'status' => ['required', 'in:aktif,selesai'],
            'dosen_ids' => ['required', 'array', 'min:1'],
            'dosen_ids.*' => ['required', 'exists:dosens,id'],
        ], [
            'semester_id.required' => 'Semester wajib dipilih.',
            'mata_kuliah_id.required' => 'Mata kuliah wajib dipilih.',
            'kelas_id.required' => 'Kelas wajib dipilih.',
            'dosen_ids.required' => 'Minimal satu dosen pengajar wajib dipilih.',
        ]);

        $dosenIds = array_unique($validated['dosen_ids']);

        $isDuplicate = Perkuliahan::query()
            ->where('semester_id', $validated['semester_id'])
            ->where('mata_kuliah_id', $validated['mata_kuliah_id'])
            ->where('kelas_id', $validated['kelas_id'])
            ->exists();

        if ($isDuplicate) {
            throw ValidationException::withMessages([
                'mata_kuliah_id' => 'Perkuliahan dengan semester, mata kuliah, dan kelas tersebut sudah tersedia.',
            ]);
        }

        DB::transaction(function () use ($validated, $dosenIds) {
            $perkuliahan = Perkuliahan::create([
                'semester_id' => $validated['semester_id'],
                'mata_kuliah_id' => $validated['mata_kuliah_id'],
                'kelas_id' => $validated['kelas_id'],
                'status' => $validated['status'],
            ]);

            foreach ($dosenIds as $dosenId) {
                Pengajar::create([
                    'perkuliahan_id' => $perkuliahan->id,
                    'dosen_id' => $dosenId,
                ]);
            }
        });

        return redirect()
            ->route('perkuliahan.index')
            ->with('success', 'Data perkuliahan berhasil ditambahkan.');
    }

    public function edit(Perkuliahan $perkuliahan): View
    {
        $perkuliahan->load('pengajars');

        $semester = Semester::with('tahunAkademik')
            ->orderByDesc('is_active')
            ->latest()
            ->get();

        $mataKuliah = MataKuliah::query()
            ->orderBy('kode_mk')
            ->get();

        $kelas = Kelas::query()
            ->orderBy('nama_kelas')
            ->get();

        $dosen = Dosen::query()
            ->orderBy('nama_dosen')
            ->get();

        $selectedDosenIds = $perkuliahan->pengajars
            ->pluck('dosen_id')
            ->toArray();

        return view('master.perkuliahan.edit', compact(
            'perkuliahan',
            'semester',
            'mataKuliah',
            'kelas',
            'dosen',
            'selectedDosenIds',
        ));
    }

    public function update(Request $request, Perkuliahan $perkuliahan): RedirectResponse
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'mata_kuliah_id' => ['required', 'exists:mata_kuliahs,id'],
            'kelas_id' => ['required', 'exists:kelas,id'],
            'status' => ['required', 'in:aktif,selesai'],
            'dosen_ids' => ['required', 'array', 'min:1'],
            'dosen_ids.*' => ['required', 'exists:dosens,id'],
        ]);

        $dosenIds = array_unique($validated['dosen_ids']);

        $isDuplicate = Perkuliahan::query()
            ->where('semester_id', $validated['semester_id'])
            ->where('mata_kuliah_id', $validated['mata_kuliah_id'])
            ->where('kelas_id', $validated['kelas_id'])
            ->whereKeyNot($perkuliahan->id)
            ->exists();

        if ($isDuplicate) {
            throw ValidationException::withMessages([
                'mata_kuliah_id' => 'Perkuliahan dengan semester, mata kuliah, dan kelas tersebut sudah tersedia.',
            ]);
        }

        DB::transaction(function () use ($validated, $perkuliahan, $dosenIds) {
            $perkuliahan->update([
                'semester_id' => $validated['semester_id'],
                'mata_kuliah_id' => $validated['mata_kuliah_id'],
                'kelas_id' => $validated['kelas_id'],
                'status' => $validated['status'],
            ]);

            $perkuliahan->pengajars()->delete();

            foreach ($dosenIds as $dosenId) {
                Pengajar::create([
                    'perkuliahan_id' => $perkuliahan->id,
                    'dosen_id' => $dosenId,
                ]);
            }
        });

        return redirect()
            ->route('perkuliahan.index')
            ->with('success', 'Data perkuliahan berhasil diperbarui.');
    }

    public function destroy(Perkuliahan $perkuliahan): RedirectResponse
    {
        if ($perkuliahan->ringkasanPerkuliahans()->exists()) {
            return back()->with('error', 'Perkuliahan tidak dapat dihapus karena sudah digunakan pada ringkasan perkuliahan.');
        }

        DB::transaction(function () use ($perkuliahan) {
            $perkuliahan->pengajars()->delete();
            $perkuliahan->delete();
        });

        return redirect()
            ->route('perkuliahan.index')
            ->with('success', 'Data perkuliahan berhasil dihapus.');
    }
}
