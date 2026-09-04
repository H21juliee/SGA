<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware
{
    use LogsActivity;
    public static function middleware(): array
    {
        return [
        new \Illuminate\Routing\Controllers\Middleware('permission:users.view', only: ['index', 'show']),
        new \Illuminate\Routing\Controllers\Middleware('permission:users.manage', only: ['store', 'update', 'destroy']),
        new \Illuminate\Routing\Controllers\Middleware('permission:users.reset_password', only: ['resetPassword']),
        ];
    }
    public function index(Request $request)
    {
        $search = $request->input('search');
        $roleFilter = $request->input('role');
        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');

        $allowedSorts = ['name', 'email', 'cedula', 'phone', 'is_active'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'name';
        }

        $users = User::query()
            ->with('roles')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('cedula', 'like', "%{$search}%");
                });
            })
            ->when($roleFilter, function ($query, $roleFilter) {
                $query->role($roleFilter);
            })
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function show(User $user)
    {
        $user->load('roles');
        
        $academicLoads = [];
        if ($user->hasRole('Docente')) {
            $academicLoads = $user->academicLoads()
                ->with(['subject', 'section.gradeLevel', 'schoolYear'])
                ->orderByDesc('school_year_id')
                ->get();
        }

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'academicLoads' => $academicLoads,
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'cedula' => ['nullable', 'string', 'max:20', 'regex:/^[VEP]-\d{6,10}$/i', 'unique:users'],
            'phone' => 'nullable|string|max:20',
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|exists:roles,name',
        ];

        $securityEnabled = config('app.security_questions_enabled', false);

        if (!$securityEnabled) {
            $rules['password'] = ['required', 'string', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()->symbols()];
        }

        $validated = $request->validate($rules);

        $password = $securityEnabled ? Hash::make($validated['cedula'] ?? '12345678') : Hash::make($validated['password']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'cedula' => $validated['cedula'],
            'phone' => $validated['phone'],
            'password' => $password,
            'is_active' => true,
            'must_change_password' => $securityEnabled,
        ]);

        $user->syncRoles($validated['roles']);

        $this->auditLog('usuarios', 'created', "Creó al usuario {$user->name} ({$user->email})", $user);

        return back()->with('success', 'Usuario creado exitosamente.');
    }

    public function update(Request $request, User $user)
    {
        if ($user->hasRole('SuperAdmin') && !auth()->user()->hasRole('SuperAdmin')) {
            return back()->with('error', 'No tienes permiso para modificar a este usuario.');
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'cedula' => ['nullable', 'string', 'max:20', 'regex:/^[VEP]-\d{6,10}$/i', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|exists:roles,name',
            'is_active' => 'required|boolean',
        ];

        $securityEnabled = config('app.security_questions_enabled', false);

        if (!$securityEnabled) {
            $rules['password'] = ['nullable', 'string', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()->symbols()];
        }

        $validated = $request->validate($rules);

        $before = $user->only(['name', 'email', 'cedula', 'phone', 'is_active']);

        $user->update([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'cedula'    => $validated['cedula'],
            'phone'     => $validated['phone'],
            'is_active' => $validated['is_active'],
        ]);

        if (!$securityEnabled && !empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $user->syncRoles($validated['roles']);

        $diff = $this->diffProperties($before, $user->only(['name', 'email', 'cedula', 'phone', 'is_active']));
        $this->auditLog('usuarios', 'updated', "Editó al usuario {$user->name}", $user, $diff);

        return back()->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy(User $user)
    {
        if ($user->hasRole('SuperAdmin') && !auth()->user()->hasRole('SuperAdmin')) {
            return back()->with('error', 'No tienes permiso para desactivar a este usuario.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $user->update(['is_active' => false]);

        $this->auditLog('usuarios', 'updated', "Desactivó al usuario {$user->name}", $user,
            ['old' => ['is_active' => true], 'new' => ['is_active' => false]]);

        return back()->with('success', 'Usuario desactivado.');
    }

    public function resetPassword(User $user)
    {
        if ($user->hasRole('SuperAdmin')) {
            return back()->with('error', 'No se puede resetear la contraseña del SuperAdmin.');
        }

        if (!$user->cedula) {
            return back()->with('error', 'Este usuario no tiene cédula registrada. Asígnele una cédula primero.');
        }

        $user->update([
            'password' => Hash::make($user->cedula),
            'must_change_password' => config('app.security_questions_enabled', false),
        ]);

        // Delete old security questions so user must set new ones
        $user->securityQuestions()->delete();

        $this->auditLog('usuarios', 'updated', "Reestableció la contraseña del usuario {$user->name}", $user);

        return back()->with('success', "Contraseña reseteada. La nueva clave temporal es la cédula del usuario ({$user->cedula}). Deberá cambiarla en su próximo inicio de sesión.");
    }
}


