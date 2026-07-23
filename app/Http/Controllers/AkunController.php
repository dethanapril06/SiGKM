<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AkunController extends Controller
{
    public function index(): View
    {
        $akun = User::with(['role'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('master.akun.index', compact('akun'));
    }

    public function create(): View
    {
        $role = Role::query()
            ->orderBy('name')
            ->get();

        return view('master.akun.create', compact('role'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'role_id.required' => 'Role wajib dipilih.',
            'name.required' => 'Nama akun wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',
        ]);

        User::create([
            'role_id' => $validated['role_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('akun.index')
            ->with('success', 'Akun berhasil ditambahkan.');
    }

    public function edit(User $akun): View
    {
        $role = Role::query()
            ->orderBy('name')
            ->get();

        return view('master.akun.edit', compact('akun', 'role'));
    }

    public function update(Request $request, User $akun): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($akun->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'role_id.required' => 'Role wajib dipilih.',
            'name.required' => 'Nama akun wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah digunakan.',
            'password.confirmed' => 'Konfirmasi password tidak sesuai.',
        ]);

        $akun->fill([
            'role_id' => $validated['role_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $request->boolean('is_active'),
        ]);

        if (! empty($validated['password'])) {
            $akun->password = Hash::make($validated['password']);
        }

        $akun->save();

        return redirect()
            ->route('akun.index')
            ->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(User $akun): RedirectResponse
    {
        if (auth()->id() === $akun->id) {
            return back()->with('error', 'Akun yang sedang digunakan tidak dapat dihapus.');
        }

        $akun->delete();

        return redirect()
            ->route('akun.index')
            ->with('success', 'Akun berhasil dihapus.');
    }

    public function toggleStatus(User $akun): RedirectResponse
    {
        if (auth()->id() === $akun->id) {
            return back()->with('error', 'Akun yang sedang digunakan tidak dapat dinonaktifkan.');
        }

        $akun->update([
            'is_active' => ! $akun->is_active,
        ]);

        return back()->with('success', 'Status akun berhasil diperbarui.');
    }
}
