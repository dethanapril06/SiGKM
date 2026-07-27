<?php

namespace App\Http\Controllers;

use App\Models\Dosen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DosenController extends Controller
{
    public function index(): View
    {
        $dosen = Dosen::query()
            ->orderBy('nama_dosen')
            ->paginate(10)
            ->withQueryString();

        return view('master.dosen.index', compact('dosen'));
    }

    public function create(): View
    {
        return view('master.dosen.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nip' => ['nullable', 'string', 'max:30', 'unique:dosens,nip'],
            'nidn' => ['nullable', 'string', 'max:20', 'unique:dosens,nidn'],
            'nama_dosen' => ['required', 'string', 'max:255'],
            'file_penelitian' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:2048'],
        ], [
            'nama_dosen.required' => 'Nama dosen wajib diisi.',
            'nip.unique' => 'NIP sudah digunakan.',
            'nidn.unique' => 'NIDN sudah digunakan.',
            'file_penelitian.mimes' => 'File penelitian harus berupa PDF, DOC, atau DOCX.',
            'file_penelitian.max' => 'Ukuran file maksimal 2 MB.',
        ]);

        if ($request->hasFile('file_penelitian')) {
            $validated['file_penelitian'] = $request
                ->file('file_penelitian')
                ->store('file-penelitian', 'public');
        }

        Dosen::create($validated);

        return redirect()
            ->route('dosen.index')
            ->with('success', 'Data dosen berhasil ditambahkan.');
    }

    public function edit(Dosen $dosen): View
    {
        return view('master.dosen.edit', compact('dosen'));
    }

    public function update(Request $request, Dosen $dosen): RedirectResponse
    {
        $validated = $request->validate([
            'nip' => ['nullable', 'string', 'max:30', 'unique:dosens,nip,'.$dosen->id],
            'nidn' => ['nullable', 'string', 'max:20', 'unique:dosens,nidn,'.$dosen->id],
            'nama_dosen' => ['required', 'string', 'max:255'],
            'file_penelitian' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:2048'],
        ], [
            'nama_dosen.required' => 'Nama dosen wajib diisi.',
            'nip.unique' => 'NIP sudah digunakan.',
            'nidn.unique' => 'NIDN sudah digunakan.',
            'file_penelitian.mimes' => 'File penelitian harus berupa PDF, DOC, atau DOCX.',
            'file_penelitian.max' => 'Ukuran file maksimal 2 MB.',
        ]);

        if ($request->hasFile('file_penelitian')) {
            if ($dosen->file_penelitian && Storage::disk('public')->exists($dosen->file_penelitian)) {
                Storage::disk('public')->delete($dosen->file_penelitian);
            }

            $validated['file_penelitian'] = $request
                ->file('file_penelitian')
                ->store('file-penelitian', 'public');
        }

        $dosen->update($validated);

        return redirect()
            ->route('dosen.index')
            ->with('success', 'Data dosen berhasil diperbarui.');
    }

    public function destroy(Dosen $dosen): RedirectResponse
    {
        if ($dosen->pengajars()->exists()) {
            return back()->with('error', 'Dosen tidak dapat dihapus karena sudah digunakan pada data pengajar.');
        }

        if ($dosen->file_penelitian && Storage::disk('public')->exists($dosen->file_penelitian)) {
            Storage::disk('public')->delete($dosen->file_penelitian);
        }

        $dosen->delete();

        return redirect()
            ->route('dosen.index')
            ->with('success', 'Data dosen berhasil dihapus.');
    }
}
