<?php

namespace App\Http\Controllers;

use App\Models\GkmMembership;
use App\Models\Role;
use App\Models\User;
use App\Support\RoleSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class GkmMembershipController extends Controller
{
    public function index(): View
    {
        $gkmMembership = GkmMembership::query()
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.gkm-membership.index', compact('gkmMembership'));
    }

    public function create(): View
    {
        return view('master.gkm-membership.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_anggota' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:50'],
            'peran' => ['required', 'in:ketua,anggota'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'is_active' => ['nullable', 'boolean'],
            'create_account' => ['nullable', 'boolean'],
            'email' => ['required_if:create_account,1', 'nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required_if:create_account,1', 'nullable', 'string', 'min:6'],
        ], [
            'nama_anggota.required' => 'Nama anggota wajib diisi.',
            'peran.required' => 'Peran wajib dipilih.',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
            'email.required_if' => 'Email login wajib diisi jika ingin membuat akun login.',
            'email.unique' => 'Email sudah terdaftar pada pengguna lain.',
            'password.required_if' => 'Password wajib diisi jika ingin membuat akun login.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        if ($validated['is_active'] && $validated['peran'] === 'ketua') {
            GkmMembership::query()
                ->where('peran', 'ketua')
                ->update(['is_active' => false]);
        }

        if ($validated['is_active']) {
            $sameActiveMembership = GkmMembership::query()
                ->where('nama_anggota', $validated['nama_anggota'])
                ->where('peran', $validated['peran'])
                ->where('is_active', true)
                ->exists();

            if ($sameActiveMembership) {
                return back()
                    ->withInput()
                    ->with('error', 'Anggota ini sudah aktif dengan peran tersebut.');
            }
        }

        DB::transaction(function () use ($validated, $request) {
            GkmMembership::create([
                'nama_anggota' => $validated['nama_anggota'],
                'nip' => $validated['nip'] ?? null,
                'peran' => $validated['peran'],
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'] ?? null,
                'is_active' => $validated['is_active'],
            ]);

            if ($request->boolean('create_account')) {
                $roleSlug = $validated['peran'] === 'ketua'
                    ? RoleSlug::KETUA_GKM
                    : RoleSlug::ANGGOTA_GKM;

                $role = Role::where('slug', $roleSlug)->first();

                if ($role) {
                    User::create([
                        'role_id' => $role->id,
                        'name' => $validated['nama_anggota'],
                        'email' => $validated['email'],
                        'password' => Hash::make($validated['password']),
                        'is_active' => true,
                    ]);
                }
            }
        });

        $message = $request->boolean('create_account')
            ? 'Keanggotaan GKM dan akun login berhasil ditambahkan.'
            : 'Keanggotaan GKM berhasil ditambahkan.';

        return redirect()
            ->route('gkm-membership.index')
            ->with('success', $message);
    }

    public function edit(GkmMembership $gkmMembership): View
    {
        return view('master.gkm-membership.edit', compact('gkmMembership'));
    }

    public function update(Request $request, GkmMembership $gkmMembership): RedirectResponse
    {
        $validated = $request->validate([
            'nama_anggota' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:50'],
            'peran' => ['required', 'in:ketua,anggota'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'nama_anggota.required' => 'Nama anggota wajib diisi.',
            'peran.required' => 'Peran wajib dipilih.',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        if ($validated['is_active'] && $validated['peran'] === 'ketua') {
            GkmMembership::query()
                ->where('peran', 'ketua')
                ->whereKeyNot($gkmMembership->id)
                ->update(['is_active' => false]);
        }

        $gkmMembership->update($validated);

        return redirect()
            ->route('gkm-membership.index')
            ->with('success', 'Keanggotaan GKM berhasil diperbarui.');
    }

    public function destroy(GkmMembership $gkmMembership): RedirectResponse
    {
        $gkmMembership->delete();

        return redirect()
            ->route('gkm-membership.index')
            ->with('success', 'Keanggotaan GKM berhasil dihapus.');
    }
}
