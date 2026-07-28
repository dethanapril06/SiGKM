<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Ami;
use App\Models\Dosen;
use App\Models\EvaluasiIndikator;
use App\Models\JadwalMonev;
use App\Models\JadwalRtm;
use App\Models\KeputusanRtm;
use App\Models\Laporan;
use App\Models\NotulenRtm;
use App\Models\Perkuliahan;
use App\Models\RencanaTindakLanjut;
use App\Models\RingkasanPerkuliahan;
use App\Models\Temuan;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route(auth()->user()->dashboardRouteName());
    }

    public function profile(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user()->load('role'),
        ]);
    }

    public function updateProfile(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->validated());

        if ($user->isDirty('email') && $user instanceof MustVerifyEmail) {
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with('status', 'profile-updated');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }

    public function destroyProfile(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function ketuaGkm(): View
    {
        return view('dashboard.ketua-gkm', [
            'stats' => [
                ['label' => 'Total Dosen', 'value' => Dosen::count(), 'icon' => 'bx-user', 'color' => 'primary'],
                ['label' => 'Evaluasi Indikator', 'value' => EvaluasiIndikator::count(), 'icon' => 'bx-bar-chart-alt-2', 'color' => 'info'],
                ['label' => 'Temuan Terbuka', 'value' => Temuan::where('status', 'terbuka')->count(), 'icon' => 'bx-search-alt', 'color' => 'danger'],
                ['label' => 'Total AMI', 'value' => Ami::count(), 'icon' => 'bx-folder-open', 'color' => 'success'],
            ],
            'pending' => [
                'ringkasan' => Laporan::where('jenis_laporan', 'perkuliahan')->where('status', 'diajukan')->count(),
                'rtl' => Temuan::where('status', 'terbuka')->count(),
                'notulen' => NotulenRtm::where('status', 'diajukan')->count(),
            ],
            'capaian' => $this->capaian(),
            'temuanTerbaru' => Temuan::with('evaluasiIndikator.semester.tahunAkademik')->latest()->limit(5)->get(),
            'jadwalRtmTerbaru' => JadwalRtm::with(['semester.tahunAkademik', 'notulenRtm.keputusanRtms'])->latest('tanggal')->limit(5)->get(),
            'amiTerbaru' => Ami::with('tahunAkademik')->latest('tanggal_pelaksanaan')->limit(5)->get(),
        ]);
    }

    public function anggotaGkm(): View
    {
        $userId = auth()->id();

        return view('dashboard.anggota-gkm', [
            'stats' => [
                ['label' => 'Ringkasan Saya', 'value' => RingkasanPerkuliahan::where('input_by', $userId)->count(), 'icon' => 'bx-book-content', 'color' => 'primary'],
                ['label' => 'Temuan Saya', 'value' => Temuan::where('created_by', $userId)->count(), 'icon' => 'bx-search-alt', 'color' => 'danger'],
                ['label' => 'Notulen Saya', 'value' => NotulenRtm::where('input_by', $userId)->count(), 'icon' => 'bx-notepad', 'color' => 'info'],
                ['label' => 'Keputusan RTM', 'value' => KeputusanRtm::count(), 'icon' => 'bx-list-check', 'color' => 'success'],
            ],
            'statusPekerjaan' => [
                'ringkasan_draft' => RingkasanPerkuliahan::where('input_by', $userId)->where('status', 'draft')->count(),
                'ringkasan_ditolak' => RingkasanPerkuliahan::where('input_by', $userId)->where('status', 'ditolak')->count(),
                'temuan_draft' => Temuan::where('created_by', $userId)->where('status', 'draft')->count(),
                'notulen_draft' => NotulenRtm::where('input_by', $userId)->where('status', 'draft')->count(),
                'notulen_ditolak' => NotulenRtm::where('input_by', $userId)->where('status', 'ditolak')->count(),
            ],
            'ringkasanTerbaru' => RingkasanPerkuliahan::with(['perkuliahan.mataKuliah', 'perkuliahan.kelas'])->where('input_by', $userId)->latest()->limit(5)->get(),
            'temuanTerbaru' => Temuan::with('evaluasiIndikator.semester.tahunAkademik')->where('created_by', $userId)->latest()->limit(5)->get(),
            'notulenTerbaru' => NotulenRtm::with('jadwalRtm.semester.tahunAkademik')->where('input_by', $userId)->latest()->limit(5)->get(),
            'jadwalMonevAktif' => JadwalMonev::with(['semester.tahunAkademik', 'termin'])->where('status', 'aktif')->latest()->limit(3)->get(),
        ]);
    }

    public function koordinatorProdi(): View
    {
        return view('dashboard.koordinator', [
            'stats' => [
                ['label' => 'Perkuliahan', 'value' => Perkuliahan::count(), 'icon' => 'bx-book', 'color' => 'primary'],
                ['label' => 'Ringkasan Monev', 'value' => RingkasanPerkuliahan::where('status', 'diverifikasi')->count(), 'icon' => 'bx-check-square', 'color' => 'success'],
                ['label' => 'Temuan Terbuka', 'value' => Temuan::where('status', 'terbuka')->count(), 'icon' => 'bx-error-circle', 'color' => 'danger'],
                ['label' => 'Rekapan AMI', 'value' => Ami::count(), 'icon' => 'bx-folder-open', 'color' => 'info'],
            ],
            'capaian' => $this->capaian(),
            'ringkasanTerbaru' => RingkasanPerkuliahan::with(['perkuliahan.mataKuliah', 'perkuliahan.kelas', 'jadwalMonev.semester.tahunAkademik'])->where('status', 'diverifikasi')->latest()->limit(5)->get(),
            'temuanTerbuka' => Temuan::with('evaluasiIndikator.semester.tahunAkademik')->where('status', 'terbuka')->latest()->limit(5)->get(),
            'rtmTerbaru' => JadwalRtm::with(['semester.tahunAkademik', 'notulenRtm.keputusanRtms'])->latest('tanggal')->limit(5)->get(),
            'amiTerbaru' => Ami::with(['tahunAkademik', 'dokumenAmis'])->latest('tanggal_pelaksanaan')->limit(5)->get(),
        ]);
    }

    private function capaian(): array
    {
        return [
            'tercapai' => EvaluasiIndikator::where('status_capaian', 'tercapai')->count(),
            'hampir_tercapai' => EvaluasiIndikator::where('status_capaian', 'hampir_tercapai')->count(),
            'belum_tercapai' => EvaluasiIndikator::where('status_capaian', 'belum_tercapai')->count(),
        ];
    }
}
