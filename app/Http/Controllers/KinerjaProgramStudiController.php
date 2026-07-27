<?php

namespace App\Http\Controllers;

use App\Helpers\CodeGenerator;
use App\Models\IndikatorKinerjaKegiatan;
use App\Models\IndikatorKinerjaKegiatanSatuan;
use App\Models\IndikatorKinerjaUtama;
use App\Models\SasaranStrategis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request; 
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KinerjaProgramStudiController extends Controller
{
    private const JENIS = ['sasaran', 'iku', 'ikk', 'ikks'];

    public function index(): View
    {
        $sasaranStrategis = SasaranStrategis::orderBy('kode_sasaran')->paginate(15, ['*'], 'sasaran_page')->withQueryString();
    
        $ikuList = IndikatorKinerjaUtama::with('sasaranStrategis')
            ->orderBy('kode_iku')->paginate(15, ['*'], 'iku_page')->withQueryString();
    
        $ikkList = IndikatorKinerjaKegiatan::with('indikatorKinerjaUtama')
            ->orderBy('kode_ikk')->paginate(15, ['*'], 'ikk_page')->withQueryString();
    
        $ikksList = IndikatorKinerjaKegiatanSatuan::with('indikatorKinerjaKegiatan')
            ->orderBy('kode_ikks')->paginate(15, ['*'], 'ikks_page')->withQueryString();
    
        return view('program-studi.kinerja.index', compact(
            'sasaranStrategis',
            'ikuList',
            'ikkList',
            'ikksList',
        ));
    }

    public function create(string $jenis): View
    {
        $this->ensureJenis($jenis);

        return view('program-studi.kinerja.form', $this->formData($jenis));
    }

    public function store(Request $request, string $jenis): RedirectResponse
    {
        $this->ensureJenis($jenis);

        $field = "kode_{$jenis}";
        if (empty($request->input($field))) {
            $autoKode = match ($jenis) {
                'sasaran' => CodeGenerator::kodeSasaran(),
                'iku' => CodeGenerator::kodeIku((int) $request->input('sasaran_strategis_id')),
                'ikk' => CodeGenerator::kodeIkk((int) $request->input('indikator_kinerja_utama_id')),
                'ikks' => CodeGenerator::kodeIkks((int) $request->input('indikator_kinerja_kegiatan_id')),
            };
            $request->merge([$field => $autoKode]);
        }

        $validated = $this->validatedData($request, $jenis);

        if ($jenis === 'sasaran') {
            $validated['created_by'] = auth()->id();
        }

        $validated['is_active'] = $request->boolean('is_active');
        $this->modelClass($jenis)::create($validated);

        return redirect()->route('kinerja-program-studi.index')
            ->with('success', $this->label($jenis).' berhasil ditambahkan.');
    }

    public function edit(string $jenis, int $id): View
    {
        $this->ensureJenis($jenis);
        $item = $this->modelClass($jenis)::findOrFail($id);

        return view('program-studi.kinerja.form', $this->formData($jenis, $item));
    }

    public function update(Request $request, string $jenis, int $id): RedirectResponse
    {
        $this->ensureJenis($jenis);
        $item = $this->modelClass($jenis)::findOrFail($id);
        $validated = $this->validatedData($request, $jenis, $item);
        $validated['is_active'] = $request->boolean('is_active');
        $item->update($validated);

        return redirect()->route('kinerja-program-studi.index')
            ->with('success', $this->label($jenis).' berhasil diperbarui.');
    }

    public function destroy(string $jenis, int $id): RedirectResponse
    {
        $this->ensureJenis($jenis);
        $item = $this->modelClass($jenis)::findOrFail($id);

        $hasChildren = match ($jenis) {
            'sasaran' => $item->indikatorKinerjaUtamas()->exists(),
            'iku' => $item->indikatorKinerjaKegiatans()->exists(),
            'ikk' => $item->indikatorKinerjaKegiatanSatuan()->exists(),
            'ikks' => $item->evaluasiIndikators()->exists(),
        };

        if ($hasChildren) {
            return back()->with('error', $this->label($jenis).' tidak dapat dihapus karena sudah digunakan atau memiliki turunan.');
        }

        $item->delete();

        return redirect()->route('kinerja-program-studi.index')
            ->with('success', $this->label($jenis).' berhasil dihapus.');
    }

    private function formData(string $jenis, ?Model $item = null): array
    {
        $parents = match ($jenis) {
            'iku' => SasaranStrategis::orderBy('kode_sasaran')->get(),
            'ikk' => IndikatorKinerjaUtama::with('sasaranStrategis')->orderBy('kode_iku')->get(),
            'ikks' => IndikatorKinerjaKegiatan::with([
                'indikatorKinerjaUtama.sasaranStrategis',
                'indikatorKinerjaKegiatanSatuan',
            ])->orderBy('kode_ikk')->get(),
            default => collect(),
        };

        $autoKodeMap = [];
        if (! $item) {
            if ($jenis === 'sasaran') {
                $autoKodeMap['default'] = CodeGenerator::kodeSasaran();
            } else {
                foreach ($parents as $parent) {
                    $autoKodeMap[$parent->id] = match ($jenis) {
                        'iku' => CodeGenerator::kodeIku($parent->id),
                        'ikk' => CodeGenerator::kodeIkk($parent->id),
                        'ikks' => CodeGenerator::kodeIkks($parent->id),
                        default => '',
                    };
                }
            }
        }

        return [
            'jenis' => $jenis,
            'item' => $item,
            'label' => $this->label($jenis),
            'parents' => $parents,
            'autoKodeMap' => $autoKodeMap,
        ];
    }

    private function validatedData(Request $request, string $jenis, ?Model $item = null): array
    {
        return match ($jenis) {
            'sasaran' => $request->validate([
                'kode_sasaran' => ['nullable', 'string', 'max:50', Rule::unique('sasaran_strategis')->ignore($item)],
                'uraian_sasaran' => ['required', 'string'],
                'is_active' => ['nullable', 'boolean'],
            ]),
            'iku' => $request->validate([
                'sasaran_strategis_id' => ['required', 'exists:sasaran_strategis,id'],
                'kode_iku' => ['nullable', 'string', 'max:50', Rule::unique('indikator_kinerja_utamas')->where('sasaran_strategis_id', $request->input('sasaran_strategis_id'))->ignore($item)],
                'uraian_iku' => ['required', 'string'],
                'is_active' => ['nullable', 'boolean'],
            ]),
            'ikk' => $request->validate([
                'indikator_kinerja_utama_id' => ['required', 'exists:indikator_kinerja_utamas,id'],
                'kode_ikk' => ['nullable', 'string', 'max:50', Rule::unique('indikator_kinerja_kegiatans')->where('indikator_kinerja_utama_id', $request->input('indikator_kinerja_utama_id'))->ignore($item)],
                'uraian_ikk' => ['required', 'string'],
                'is_active' => ['nullable', 'boolean'],
            ]),
            'ikks' => $request->validate([
                'indikator_kinerja_kegiatan_id' => ['required', 'exists:indikator_kinerja_kegiatans,id', Rule::unique('indikator_kinerja_kegiatan_satuans')->ignore($item)],
                'kode_ikks' => ['nullable', 'string', 'max:50'],
                'uraian_ikks' => ['required', 'string'],
                'is_active' => ['nullable', 'boolean'],
            ]),
        };
    }

    private function modelClass(string $jenis): string
    {
        return match ($jenis) {
            'sasaran' => SasaranStrategis::class,
            'iku' => IndikatorKinerjaUtama::class,
            'ikk' => IndikatorKinerjaKegiatan::class,
            'ikks' => IndikatorKinerjaKegiatanSatuan::class,
        };
    }

    private function label(string $jenis): string
    {
        return match ($jenis) {
            'sasaran' => 'Sasaran Strategis',
            'iku' => 'Indikator Kinerja Utama (IKU)',
            'ikk' => 'Indikator Kinerja Kegiatan (IKK)',
            'ikks' => 'Indikator Kinerja Kegiatan Satuan (IKKS)',
        };
    }

    private function ensureJenis(string $jenis): void
    {
        abort_unless(in_array($jenis, self::JENIS, true), 404);
    }
}
