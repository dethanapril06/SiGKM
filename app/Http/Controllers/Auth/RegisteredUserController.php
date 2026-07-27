<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Dosen;
use App\Models\Role;
use App\Models\User;
use App\Support\RoleSlug;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nip' => ['nullable', 'string', 'max:30', 'unique:dosens,nip'],
            'nidn' => ['nullable', 'string', 'max:20', 'unique:dosens,nidn'],
            'nama_dosen' => ['required', 'string', 'max:255'],
            'file_penelitian' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:2048'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($request) {
            $dosenRole = Role::query()->updateOrCreate(
                ['slug' => RoleSlug::DOSEN],
                [
                    'name' => 'Dosen',
                    'description' => 'Akun untuk Dosen',
                ]
            );

            $dosenData = $request->only(['nip', 'nidn', 'nama_dosen']);

            if ($request->hasFile('file_penelitian')) {
                $dosenData['file_penelitian'] = $request
                    ->file('file_penelitian')
                    ->store('file-penelitian', 'public');
            }

            $dosen = Dosen::create($dosenData);

            return User::create([
                'role_id' => $dosenRole->id,
                'name' => $dosen->nama_dosen,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_active' => true,
            ]);
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
