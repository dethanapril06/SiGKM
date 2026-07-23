<?php

namespace App\Http\Controllers;

use App\Models\Ami;
use App\Models\DokumenAmi;
use App\Models\TahunAkademik;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AmiController extends Controller
{
    public function index(): View
    {
        $ami = Ami::with(['tahunAkademik', 'penginput', 'dokumenAmis.pengunggah'])
            ->latest('tanggal_pelaksanaan')
            ->paginate(10)
            ->withQueryString();

        return view('ami.index', compact('ami'));
    }

    public function create(): View
    {
        $this->ensureManager();

        return view('ami.create', ['tahunAkademik' => $this->academicYears()]);
    }

    public function show(Ami $ami): View
    {
        $ami->load(['tahunAkademik', 'penginput', 'dokumenAmis.pengunggah']);

        return view('ami.show', compact('ami'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureManager();
        $data = $this->validated($request);
        $data['input_by'] = auth()->id();
        $ami = Ami::create($data);

        $this->handleDocumentUpload($request, $ami);

        return redirect()->route('ami.index')->with('success', 'Data AMI berhasil dibuat.');
    }

    public function edit(Ami $ami): View
    {
        $this->ensureManager();

        return view('ami.edit', [
            'ami' => $ami,
            'tahunAkademik' => $this->academicYears(),
        ]);
    }

    public function update(Request $request, Ami $ami): RedirectResponse
    {
        $this->ensureManager();
        $ami->update($this->validated($request));

        $this->handleDocumentUpload($request, $ami);

        return redirect()->route('ami.index')->with('success', 'Data AMI berhasil diperbarui.');
    }

    public function destroy(Ami $ami): RedirectResponse
    {
        $this->ensureManager();

        foreach ($ami->dokumenAmis as $document) {
            if ($document->file_path) {
                Storage::disk('public')->delete($document->file_path);
            }
        }

        $ami->delete();

        return back()->with('success', 'Data AMI berhasil dihapus.');
    }

    public function storeDocument(Request $request, Ami $ami): RedirectResponse
    {
        $this->ensureManager();
        $data = $request->validate([
            'nama_dokumen' => ['required', 'string', 'max:255'],
            'document_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:5120'],
            'link_url' => ['nullable', 'url', 'max:2048'],
        ], [
            'nama_dokumen.required' => 'Nama bukti wajib diisi.',
            'document_file.mimes' => 'File harus berupa PDF, Word, Excel, JPG, JPEG, atau PNG.',
            'document_file.max' => 'Ukuran file maksimal 5 MB.',
            'link_url.url' => 'Link Google Drive harus berupa URL yang valid.',
        ]);

        if (! $request->hasFile('document_file') && blank($data['link_url'] ?? null)) {
            throw ValidationException::withMessages([
                'document_file' => 'Unggah file atau isi link Google Drive sebagai bukti AMI.',
            ]);
        }

        $ami->dokumenAmis()->create([
            'nama_dokumen' => $data['nama_dokumen'],
            'file_path' => $request->hasFile('document_file')
                ? $request->file('document_file')->store('dokumen-ami', 'public')
                : null,
            'link_url' => $data['link_url'] ?? null,
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Bukti AMI berhasil ditambahkan.');
    }

    public function destroyDocument(DokumenAmi $dokumenAmi): RedirectResponse
    {
        $this->ensureManager();

        if ($dokumenAmi->file_path) {
            Storage::disk('public')->delete($dokumenAmi->file_path);
        }

        $dokumenAmi->delete();

        return back()->with('success', 'Bukti AMI berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'tahun_akademik_id' => ['required', 'exists:tahun_akademiks,id'],
            'tanggal_pelaksanaan' => ['required', 'date'],
            'temuan' => ['required', 'string'],
            'rekomendasi' => ['required', 'string'],
            'tindak_lanjut' => ['nullable', 'string'],
            'target_selesai' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,aktif,selesai'],
        ]);
    }

    private function handleDocumentUpload(Request $request, Ami $ami): void
    {
        $docData = $request->validate([
            'nama_dokumen' => ['nullable', 'string', 'max:255'],
            'document_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:5120'],
            'link_url' => ['nullable', 'url', 'max:2048'],
        ], [
            'document_file.mimes' => 'File harus berupa PDF, Word, Excel, JPG, JPEG, atau PNG.',
            'document_file.max' => 'Ukuran file maksimal 5 MB.',
            'link_url.url' => 'Link Google Drive harus berupa URL yang valid.',
        ]);

        if ($request->hasFile('document_file') || ! empty($docData['link_url'])) {
            $namaDokumen = $docData['nama_dokumen'] ?: 'Bukti AMI';
            $ami->dokumenAmis()->create([
                'nama_dokumen' => $namaDokumen,
                'file_path' => $request->hasFile('document_file')
                    ? $request->file('document_file')->store('dokumen-ami', 'public')
                    : null,
                'link_url' => $docData['link_url'] ?? null,
                'uploaded_by' => auth()->id(),
            ]);
        }
    }

    private function academicYears()
    {
        return TahunAkademik::query()->orderByDesc('tanggal_mulai')->get();
    }

    private function ensureManager(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['ketua-gkm', 'anggota-gkm']), 403);
    }
}
