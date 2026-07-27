<?php

namespace App\Http\Controllers;

use App\Helpers\CodeGenerator;
use App\Models\StandarMutu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StandarMutuController extends Controller
{
    public function index(): View
    {
        $standarMutu = StandarMutu::withCount('indikatorMutus')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.standar-mutu.index', compact('standarMutu'));
    }

    public function create(): View
    {
        $autoKode = CodeGenerator::kodeStandarMutu();

        return view('master.standar-mutu.create', compact('autoKode'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (empty($request->kode_standar)) {
            $request->merge(['kode_standar' => CodeGenerator::kodeStandarMutu()]);
        }

        $validated = $request->validate([
            'kode_standar' => ['required', 'string', 'max:50', 'unique:standar_mutus,kode_standar'],
            'nama_standar' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'kode_standar.unique' => 'Kode standar sudah digunakan.',
            'nama_standar.required' => 'Nama standar wajib diisi.',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['created_by'] = auth()->id();

        StandarMutu::create($validated);

        return redirect()
            ->route('standar-mutu.index')
            ->with('success', 'Standar mutu berhasil ditambahkan.');
    }

    public function edit(StandarMutu $standarMutu): View
    {
        return view('master.standar-mutu.edit', compact('standarMutu'));
    }

    public function update(Request $request, StandarMutu $standarMutu): RedirectResponse
    {
        $validated = $request->validate([
            'kode_standar' => ['nullable', 'string', 'max:50', 'unique:standar_mutus,kode_standar,'.$standarMutu->id],
            'nama_standar' => ['required', 'string', 'max:255'],
            'deskripsi' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'kode_standar.unique' => 'Kode standar sudah digunakan.',
            'nama_standar.required' => 'Nama standar wajib diisi.',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $standarMutu->update($validated);

        return redirect()
            ->route('standar-mutu.index')
            ->with('success', 'Standar mutu berhasil diperbarui.');
    }

    public function destroy(StandarMutu $standarMutu): RedirectResponse
    {
        if ($standarMutu->indikatorMutus()->exists()) {
            return back()->with('error', 'Standar mutu tidak dapat dihapus karena sudah memiliki indikator mutu.');
        }

        $standarMutu->delete();

        return redirect()
            ->route('standar-mutu.index')
            ->with('success', 'Standar mutu berhasil dihapus.');
    }
}
