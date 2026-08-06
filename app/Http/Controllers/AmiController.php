<?php

namespace App\Http\Controllers;

use App\Models\Ami;
use App\Models\TahunAkademik;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AmiController extends Controller
{
    public function index(): View
    {
        $ami = Ami::with(['tahunAkademik', 'penginput'])
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
        $ami->load(['tahunAkademik', 'penginput']);

        return view('ami.show', compact('ami'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureManager();

        $data = $this->validated($request);
        $data['input_by'] = auth()->id();
        $data = array_merge($data, $this->handleFileUploads($request));

        Ami::create($data);

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

        $data = $this->validated($request);
        $uploads = $this->handleFileUploads($request, $ami);
        $ami->update(array_merge($data, $uploads));

        return redirect()->route('ami.index')->with('success', 'Data AMI berhasil diperbarui.');
    }

    public function destroy(Ami $ami): RedirectResponse
    {
        $this->ensureManager();

        foreach (['file_ami', 'file_tindak_lanjut', 'file_dokumentasi', 'file_absensi'] as $field) {
            if ($ami->$field) {
                Storage::disk('public')->delete($ami->$field);
            }
        }

        $ami->delete();

        return back()->with('success', 'Data AMI berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'tahun_akademik_id' => ['required', 'exists:tahun_akademiks,id'],
            'tanggal_pelaksanaan' => ['required', 'date'],
        ], [
            'tahun_akademik_id.required' => 'Tahun Akademik wajib dipilih.',
            'tanggal_pelaksanaan.required' => 'Tanggal wajib diisi.',
        ]);
    }

    private function handleFileUploads(Request $request, ?Ami $existing = null): array
    {
        $request->validate([
            'file_ami'           => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:5120'],
            'file_tindak_lanjut' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:5120'],
            'file_dokumentasi'   => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:5120'],
            'file_absensi'       => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:5120'],
        ], [
            '*.mimes' => 'File harus berupa PDF, Word, Excel, JPG, JPEG, atau PNG.',
            '*.max'   => 'Ukuran file maksimal 5 MB.',
        ]);

        $uploads = [];

        foreach (['file_ami', 'file_tindak_lanjut', 'file_dokumentasi', 'file_absensi'] as $field) {
            if ($request->hasFile($field)) {
                // Hapus file lama jika ada
                if ($existing && $existing->$field) {
                    Storage::disk('public')->delete($existing->$field);
                }
                $uploads[$field] = $request->file($field)->store('ami-files', 'public');
            }
        }

        return $uploads;
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
