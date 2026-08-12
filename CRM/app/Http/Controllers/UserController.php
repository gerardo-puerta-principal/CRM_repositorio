<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return view('users.index', [
            'users' => User::query()
                ->with('supervisor')
                ->orderBy('role')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create()
    {
        return view('users.create', [
            'roles' => $this->availableRoles(),
            'supervisors' => $this->supervisors(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateUser($request, null, true);

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'supervisor_id' => $this->normalizedSupervisorId($validated),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('users.index')
            ->with('status', 'Usuario creado correctamente.');
    }

    public function edit(User $user)
    {
        return view('users.edit', [
            'userModel' => $user,
            'roles' => $this->availableRoles(),
            'supervisors' => $this->supervisors($user->id),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $this->validateUser($request, $user, false);
        $currentUser = $request->user();

        if (
            $currentUser instanceof User
            && $currentUser->id === $user->id
            && (
                ! $request->boolean('is_active')
                || $validated['role'] !== User::ROLE_SUPER_ADMIN
            )
        ) {
            return back()
                ->withErrors(['email' => 'No puedes desactivar tu propia cuenta ni quitarte el rol de Super Admin.'])
                ->withInput();
        }

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'supervisor_id' => $this->normalizedSupervisorId($validated),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('users.edit', $user)
            ->with('status', 'Usuario actualizado correctamente.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => $validated['password'],
        ]);

        return redirect()
            ->route('users.edit', $user)
            ->with('status', 'Contrasena actualizada correctamente.');
    }

    public function impersonate(Request $request, User $user)
    {
        $currentUser = $request->user();

        if (
            ! $currentUser instanceof User
            || $currentUser->id === $user->id
            || ! $user->is_active
            || $user->role === User::ROLE_SUPER_ADMIN
        ) {
            abort(403);
        }

        $request->session()->put([
            'impersonator_id' => $currentUser->id,
            'impersonator_name' => $currentUser->name,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Ahora estas dentro de la cuenta de '.$user->name.'.');
    }

    public function leaveImpersonation(Request $request)
    {
        $impersonatorId = (int) $request->session()->get('impersonator_id', 0);

        if ($impersonatorId <= 0) {
            return redirect()->route('dashboard');
        }

        $impersonator = User::query()->findOrFail($impersonatorId);

        Auth::login($impersonator);
        $request->session()->forget(['impersonator_id', 'impersonator_name']);
        $request->session()->regenerate();

        return redirect()
            ->route('users.index')
            ->with('status', 'Sesion original restaurada correctamente.');
    }

    private function validateUser(Request $request, ?User $user, bool $creating): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'role' => ['required', Rule::in(array_keys($this->availableRoles()))],
            'supervisor_id' => ['nullable', 'integer'],
        ];

        if ($creating) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        $validated = $request->validate($rules);

        $supervisorId = (int) ($validated['supervisor_id'] ?? 0);
        $supervisorIds = $this->supervisors($user?->id)->pluck('id')->all();

        if ($validated['role'] === User::ROLE_AGENT && ! in_array($supervisorId, $supervisorIds, true)) {
            $request->validate([
                'supervisor_id' => ['required', Rule::in($supervisorIds)],
            ]);
        }

        return $validated;
    }

    private function normalizedSupervisorId(array $validated): ?int
    {
        if (($validated['role'] ?? null) !== User::ROLE_AGENT) {
            return null;
        }

        $supervisorId = (int) ($validated['supervisor_id'] ?? 0);

        return $supervisorId > 0 ? $supervisorId : null;
    }

    private function supervisors(?int $exceptUserId = null)
    {
        return User::query()
            ->where('role', User::ROLE_SUPERVISOR)
            ->when($exceptUserId !== null, fn ($query) => $query->whereKeyNot($exceptUserId))
            ->orderBy('name')
            ->get();
    }

    private function availableRoles(): array
    {
        return [
            User::ROLE_SUPER_ADMIN => 'Super Admin',
            User::ROLE_SUPERVISOR => 'Supervisor',
            User::ROLE_AGENT => 'Agente',
        ];
    }
}
