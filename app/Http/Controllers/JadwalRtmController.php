<?php

namespace App\Http\Controllers;

use App\Models\JadwalRtm;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalRtmController extends Controller
{
    public function index(): View
    {
        $jadwalRtm = JadwalRtm::with(['semester.tahunAkademik', 'pembuat', 'notulenRtm'])
            ->latest('tanggal')
            ->paginate(10)
            ->withQueryString();

        return view('rtm.jadwal.index', compact('jadwalRtm'));
    }

    public function create(): View
    {
        return view('rtm.jadwal.create', ['semester' => $this->semesters()]);
    }

    public function show(JadwalRtm $jadwalRtm): View
    {
        $jadwalRtm->load([
            'semester.tahunAkademik',
            'pembuat',
            'notulenRtm.penginput',
            'notulenRtm.verifikator',
            'notulenRtm.keputusanRtms.rencanaTindakLanjut.temuan',
        ]);

        return view('rtm.jadwal.show', compact('jadwalRtm'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = auth()->id();
        JadwalRtm::create($data);

        return redirect()->route('jadwal-rtm.index')->with('success', 'Jadwal RTM berhasil dibuat.');
    }

    public function edit(JadwalRtm $jadwalRtm): View
    {
        return view('rtm.jadwal.edit', [
            'jadwalRtm' => $jadwalRtm,
            'semester' => $this->semesters(),
        ]);
    }

    public function update(Request $request, JadwalRtm $jadwalRtm): RedirectResponse
    {
        $jadwalRtm->update($this->validated($request));

        return redirect()->route('jadwal-rtm.index')->with('success', 'Jadwal RTM berhasil diperbarui.');
    }

    public function destroy(JadwalRtm $jadwalRtm): RedirectResponse
    {
        if ($jadwalRtm->notulenRtm()->exists()) {
            return back()->with('error', 'Jadwal tidak dapat dihapus karena sudah memiliki notulen.');
        }

        $jadwalRtm->delete();

        return back()->with('success', 'Jadwal RTM berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'judul' => ['required', 'string', 'max:255'],
            'tanggal' => ['required', 'date'],
            'waktu_mulai' => ['nullable', 'date_format:H:i,H:i:s'],
            'waktu_selesai' => ['nullable', 'date_format:H:i,H:i:s', 'after:waktu_mulai'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'agenda' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,terjadwal,selesai'],
        ]);
    }

    private function semesters()
    {
        return Semester::with('tahunAkademik')->orderByDesc('tanggal_mulai')->get();
    }
}
