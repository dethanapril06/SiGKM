<?php

namespace App\Http\Controllers;

use App\Helpers\CodeGenerator;
use App\Models\IndikatorMutu;
use App\Models\StandarMutu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IndikatorMutuController extends Controller
{
    public function index(Request $request): View
    {
        $standarMutuId = $request->standar_mutu_id;

        $indikatorMutu = IndikatorMutu::with('standarMutu')
            ->when($standarMutuId, function ($query) use ($standarMutuId) {
                $query->where('standar_mutu_id', $standarMutuId);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $standarMutu = StandarMutu::query()
            ->orderBy('kode_standar')
            ->get();

        return view('master.indikator-mutu.index', compact(
            'indikatorMutu',
            'standarMutu',
            'standarMutuId'
        ));
    }

    public function create(Request $request): View
    {
        $standarMutu = StandarMutu::query()
            ->where('is_active', true)
            ->orderBy('kode_standar')
            ->get();

        $selectedStandarMutuId = $request->standar_mutu_id;

        $autoKodeMap = [];
        foreach ($standarMutu as $item) {
            $autoKodeMap[$item->id] = CodeGenerator::kodeIndikatorMutu($item);
        }

        return view('master.indikator-mutu.create', compact(
            'standarMutu',
            'selectedStandarMutuId',
            'autoKodeMap'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        if (empty($request->kode_indikator) && ! empty($request->standar_mutu_id)) {
            $request->merge([
                'kode_indikator' => CodeGenerator::kodeIndikatorMutu((int) $request->standar_mutu_id),
            ]);
        }

        $validated = $request->validate([
            'standar_mutu_id' => ['required', 'exists:standar_mutus,id'],
            'kode_indikator' => ['nullable', 'string', 'max:50'],
            'isi_indikator' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'standar_mutu_id.required' => 'Standar mutu wajib dipilih.',
            'isi_indikator.required' => 'Isi indikator wajib diisi.',
        ]);

        $isDuplicate = IndikatorMutu::query()
            ->where('standar_mutu_id', $validated['standar_mutu_id'])
            ->where('kode_indikator', $validated['kode_indikator'])
            ->whereNotNull('kode_indikator')
            ->exists();

        if ($isDuplicate) {
            return back()
                ->withInput()
                ->withErrors([
                    'kode_indikator' => 'Kode indikator sudah digunakan pada standar mutu tersebut.',
                ]);
        }

        $validated['is_active'] = $request->boolean('is_active');

        IndikatorMutu::create($validated);

        return redirect()
            ->route('indikator-mutu.index', ['standar_mutu_id' => $validated['standar_mutu_id']])
            ->with('success', 'Indikator mutu berhasil ditambahkan.');
    }

    public function edit(IndikatorMutu $indikatorMutu): View
    {
        $standarMutu = StandarMutu::query()
            ->orderBy('kode_standar')
            ->get();

        return view('master.indikator-mutu.edit', compact(
            'indikatorMutu',
            'standarMutu'
        ));
    }

    public function update(Request $request, IndikatorMutu $indikatorMutu): RedirectResponse
    {
        $validated = $request->validate([
            'standar_mutu_id' => ['required', 'exists:standar_mutus,id'],
            'kode_indikator' => ['nullable', 'string', 'max:50'],
            'isi_indikator' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isDuplicate = IndikatorMutu::query()
            ->where('standar_mutu_id', $validated['standar_mutu_id'])
            ->where('kode_indikator', $validated['kode_indikator'])
            ->whereNotNull('kode_indikator')
            ->whereKeyNot($indikatorMutu->id)
            ->exists();

        if ($isDuplicate) {
            return back()
                ->withInput()
                ->withErrors([
                    'kode_indikator' => 'Kode indikator sudah digunakan pada standar mutu tersebut.',
                ]);
        }

        $validated['is_active'] = $request->boolean('is_active');

        $indikatorMutu->update($validated);

        return redirect()
            ->route('indikator-mutu.index', ['standar_mutu_id' => $validated['standar_mutu_id']])
            ->with('success', 'Indikator mutu berhasil diperbarui.');
    }

    public function destroy(IndikatorMutu $indikatorMutu): RedirectResponse
    {
        if ($indikatorMutu->evaluasiIndikators()->exists()) {
            return back()->with('error', 'Indikator mutu tidak dapat dihapus karena sudah digunakan pada evaluasi indikator.');
        }

        $standarMutuId = $indikatorMutu->standar_mutu_id;

        $indikatorMutu->delete();

        return redirect()
            ->route('indikator-mutu.index', ['standar_mutu_id' => $standarMutuId])
            ->with('success', 'Indikator mutu berhasil dihapus.');
    }
}
