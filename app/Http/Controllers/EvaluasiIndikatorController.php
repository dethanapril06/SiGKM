<?php

namespace App\Http\Controllers;

use App\Models\EvaluasiIndikator;
use App\Models\IndikatorMutu;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EvaluasiIndikatorController extends Controller
{
    public function index(Request $request): View
    {
        $selectedSemester = $request->query('semester_id');
        $selectedStatusCapaian = $request->query('status_capaian');

        $semesters = Semester::with('tahunAkademik')->orderByDesc('tanggal_mulai')->get();

        $evaluasiIndikator = EvaluasiIndikator::with([
            'semester.tahunAkademik',
            'evaluatable',
            'penginput',
        ])
            ->when($selectedSemester, function ($query) use ($selectedSemester) {
                $query->where('semester_id', $selectedSemester);
            })
            ->when($selectedStatusCapaian, function ($query) use ($selectedStatusCapaian) {
                $query->where('status_capaian', $selectedStatusCapaian);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('monev.evaluasi-indikator.index', compact(
            'evaluasiIndikator',
            'semesters',
            'selectedSemester',
            'selectedStatusCapaian'
        ));
    }

    public function create(): View
    {
        return view('monev.evaluasi-indikator.create', $this->formData());
    }

    public function show(EvaluasiIndikator $evaluasiIndikator): View
    {
        $evaluasiIndikator->load([
            'semester.tahunAkademik',
            'evaluatable',
            'penginput',
            'verifikator',
            'temuans',
        ]);

        return view('monev.evaluasi-indikator.show', compact('evaluasiIndikator'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);

        $this->ensureNotDuplicate($validated);

        if ($request->hasFile('bukti_capaian')) {
            $validated['bukti_capaian'] = $request
                ->file('bukti_capaian')
                ->store('evaluasi-indikator', 'public');
        }

        $validated['input_by'] = auth()->id();

        EvaluasiIndikator::create($validated);

        return redirect()
            ->route('evaluasi-indikator.index')
            ->with('success', 'Evaluasi indikator berhasil ditambahkan.');
    }

    public function edit(EvaluasiIndikator $evaluasiIndikator): View
    {
        return view('monev.evaluasi-indikator.edit', array_merge(
            ['evaluasiIndikator' => $evaluasiIndikator],
            $this->formData(false)
        ));
    }

    public function update(Request $request, EvaluasiIndikator $evaluasiIndikator): RedirectResponse
    {
        $validated = $this->validatedData($request);

        $this->ensureNotDuplicate($validated, $evaluasiIndikator);

        if ($request->hasFile('bukti_capaian')) {
            if ($evaluasiIndikator->bukti_capaian) {
                Storage::disk('public')->delete($evaluasiIndikator->bukti_capaian);
            }

            $validated['bukti_capaian'] = $request
                ->file('bukti_capaian')
                ->store('evaluasi-indikator', 'public');
        }

        $evaluasiIndikator->update($validated);

        return redirect()
            ->route('evaluasi-indikator.index')
            ->with('success', 'Evaluasi indikator berhasil diperbarui.');
    }

    public function destroy(EvaluasiIndikator $evaluasiIndikator): RedirectResponse
    {
        if ($evaluasiIndikator->temuans()->exists()) {
            return back()->with('error', 'Evaluasi indikator tidak dapat dihapus karena sudah digunakan pada data temuan.');
        }

        if ($evaluasiIndikator->bukti_capaian) {
            Storage::disk('public')->delete($evaluasiIndikator->bukti_capaian);
        }

        $evaluasiIndikator->delete();

        return redirect()
            ->route('evaluasi-indikator.index')
            ->with('success', 'Evaluasi indikator berhasil dihapus.');
    }

    private function formData(bool $activeOnly = true): array
    {
        $semester = Semester::with('tahunAkademik')
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->orderByDesc('is_active')
            ->latest()
            ->get();

        $indikatorMutu = IndikatorMutu::with('standarMutu')
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->latest()
            ->get();

        return compact('semester', 'indikatorMutu');
    }

    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'evaluatable_key' => ['required', 'regex:/^indikator_mutu:[1-9][0-9]*$/'],
            'status_capaian' => ['required', 'in:tercapai,dalam_proses,belum_tercapai'],
            'nama_penanggung_jawab' => ['nullable', 'string', 'max:255'],
            'bukti_capaian' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:2048'],
            'catatan' => ['nullable', 'string'],
        ], [
            'semester_id.required' => 'Semester wajib dipilih.',
            'evaluatable_key.required' => 'Sumber indikator wajib dipilih.',
            'evaluatable_key.regex' => 'Sumber indikator tidak valid.',
            'status_capaian.required' => 'Status capaian wajib dipilih.',
            'bukti_capaian.mimes' => 'Bukti capaian harus berupa PDF, DOC, DOCX, JPG, JPEG, atau PNG.',
            'bukti_capaian.max' => 'Ukuran bukti capaian maksimal 2 MB.',
        ]);

        [$type, $id] = explode(':', $validated['evaluatable_key']);
        $model = IndikatorMutu::class;

        if (! $model::whereKey($id)->exists()) {
            throw ValidationException::withMessages(['evaluatable_key' => 'Sumber indikator tidak ditemukan.']);
        }

        unset($validated['evaluatable_key']);
        $validated['evaluatable_type'] = $type;
        $validated['evaluatable_id'] = (int) $id;

        return $validated;
    }

    private function ensureNotDuplicate(array $validated, ?EvaluasiIndikator $current = null): void
    {
        $query = EvaluasiIndikator::query()
            ->where('semester_id', $validated['semester_id'])
            ->where('evaluatable_type', $validated['evaluatable_type'])
            ->where('evaluatable_id', $validated['evaluatable_id']);

        if ($current) {
            $query->whereKeyNot($current->id);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'evaluatable_key' => 'Evaluasi untuk semester dan indikator tersebut sudah tersedia.',
            ]);
        }
    }
}
