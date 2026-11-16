<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            new \Illuminate\Routing\Controllers\Middleware('permission:users.manage', only: ['store', 'update', 'destroy', 'transferSuperAdmin']),
            new \Illuminate\Routing\Controllers\Middleware('permission:users.reset_password', only: ['resetPassword']),
        ];
    }

    /**
     * Genera una contraseña temporal aleatoria criptográficamente segura (ej. Sga#8492Kp)
     */
    private function generateTemporaryPassword(): string
    {
        $upper   = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower   = 'abcdefghijkmnopqrstuvwxyz';
        $digits  = '23456789';
        $symbols = '#$@!%*';

        $randomChars = [
            $upper[random_int(0, strlen($upper) - 1)],
            $lower[random_int(0, strlen($lower) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
        ];

        shuffle($randomChars);
        $symbol = $symbols[random_int(0, strlen($symbols) - 1)];

        return 'Sga' . $symbol . implode('', $randomChars);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $roleFilter = $request->input('role');
        $statusFilter = $request->input('status', 'all');
        $sort = $request->input('sort', 'name');
        $direction = $request->input('direction', 'asc');

        $allowedSorts = ['name', 'email', 'cedula', 'phone', 'is_active'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        $users = User::query()
            ->with('roles')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('cedula', 'like', "%{$search}%");
                });
            })
            ->when($roleFilter, function ($query, $roleFilter) {
                $query->role($roleFilter);
            })
            ->when($statusFilter === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($statusFilter === 'inactive', function ($query) {
                $query->where('is_active', false);
            })
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search'    => $search,
                'role'      => $roleFilter,
                'status'    => $statusFilter,
                'sort'      => $sort,
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
        $request->merge([
            'cedula' => strtoupper(trim((string) $request->input('cedula', ''))),
            'email'  => strtolower(trim((string) $request->input('email', ''))),
        ]);

        $rules = [
            'first_name' => ['required', 'string', 'max:120', 'regex:/^[\pL\s\-\']+$/u'],
            'last_name'  => ['required', 'string', 'max:120', 'regex:/^[\pL\s\-\']+$/u'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'cedula'     => ['required', 'string', 'max:20', 'regex:/^(?:[VE]-\d{6,9}|P-[A-Z0-9]{6,15})$/', 'unique:users,cedula'],
            'phone'      => ['nullable', 'string', 'max:20', 'regex:/^(0412|0414|0424|0416|0426|02\d{2})-\d{7}$/'],
            'roles'      => ['required', 'array', 'min:1'],
            'roles.*'    => ['string', 'exists:roles,name', Rule::notIn(['SuperAdmin'])],
        ];

        $messages = [
            'first_name.required' => 'Los nombres son obligatorios.',
            'first_name.regex'    => 'Los nombres solo deben contener letras y espacios.',
            'last_name.required'  => 'Los apellidos son obligatorios.',
            'last_name.regex'     => 'Los apellidos solo deben contener letras y espacios.',
            'email.required'      => 'El correo electrónico es obligatorio.',
            'email.email'         => 'Ingresa un correo electrónico válido (ej. usuario@colegio.edu.ve).',
            'email.unique'        => 'Este correo electrónico ya está registrado en el sistema.',
            'cedula.required'     => 'La cédula o documento de identidad es obligatorio.',
            'cedula.regex'        => 'El formato del documento es inválido (6 a 9 dígitos para V/E o 6 a 15 caracteres para Pasaporte).',
            'cedula.unique'       => 'Esta cédula o documento ya se encuentra registrado en otro usuario.',
            'phone.regex'         => 'El teléfono debe tener un formato venezolano válido (ej. 0414-1234567).',
            'roles.required'      => 'Debe asignar al menos un rol al usuario.',
            'roles.min'           => 'Debe asignar al menos un rol al usuario.',
            'roles.*.not_in'      => 'El rol SuperAdmin es exclusivo y no puede asignarse desde este formulario.',
        ];

        $securityEnabled = config('app.security_questions_enabled', false);

        if (!$securityEnabled) {
            $rules['password'] = ['required', 'string', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()->symbols()];
        }

        $validated = $request->validate($rules, $messages);

        $tempPassword = null;
        if ($securityEnabled) {
            $tempPassword = $this->generateTemporaryPassword();
            $passwordHash = Hash::make($tempPassword);
        } else {
            $passwordHash = Hash::make($validated['password']);
        }

        $firstName = trim($validated['first_name']);
        $lastName  = trim($validated['last_name']);
        $fullName  = trim("{$firstName} {$lastName}");

        $user = User::create([
            'name'                 => $fullName,
            'first_name'           => $firstName,
            'last_name'            => $lastName,
            'email'                => $validated['email'],
            'cedula'               => $validated['cedula'],
            'phone'                => $validated['phone'] ?: null,
            'password'             => $passwordHash,
            'is_active'            => true,
            'must_change_password' => $securityEnabled,
        ]);

        $user->syncRoles($validated['roles']);

        $this->auditLog('usuarios', 'created', "Creó al usuario {$user->name} ({$user->email})", $user);

        $redirect = back()->with('success', 'Usuario creado exitosamente.');

        if ($tempPassword !== null) {
            $redirect->with('temp_credentials', [
                'action'   => 'created',
                'name'     => $user->name,
                'email'    => $user->email,
                'cedula'   => $user->cedula,
                'password' => $tempPassword,
            ]);
        }

        return $redirect;
    }

    public function update(Request $request, User $user)
    {
        $isTargetSuperAdmin = $user->hasRole('SuperAdmin');

        if ($isTargetSuperAdmin && !$request->user()->hasRole('SuperAdmin')) {
            return back()->with('error', 'No tienes permiso para modificar al SuperAdmin.');
        }

        $request->merge([
            'cedula' => strtoupper(trim((string) $request->input('cedula', ''))),
            'email'  => strtolower(trim((string) $request->input('email', ''))),
        ]);

        $rules = [
            'first_name' => ['required', 'string', 'max:120', 'regex:/^[\pL\s\-\']+$/u'],
            'last_name'  => ['required', 'string', 'max:120', 'regex:/^[\pL\s\-\']+$/u'],
            'email'      => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'cedula'     => ['required', 'string', 'max:20', 'regex:/^(?:[VE]-\d{6,9}|P-[A-Z0-9]{6,15})$/', Rule::unique('users', 'cedula')->ignore($user->id)],
            'phone'      => ['nullable', 'string', 'max:20', 'regex:/^(0412|0414|0424|0416|0426|02\d{2})-\d{7}$/'],
            'roles'      => $isTargetSuperAdmin ? ['nullable', 'array'] : ['required', 'array', 'min:1'],
            'roles.*'    => ['string', 'exists:roles,name', Rule::notIn(['SuperAdmin'])],
            'is_active'  => ['required', 'boolean'],
        ];

        $messages = [
            'first_name.required' => 'Los nombres son obligatorios.',
            'first_name.regex'    => 'Los nombres solo deben contener letras y espacios.',
            'last_name.required'  => 'Los apellidos son obligatorios.',
            'last_name.regex'     => 'Los apellidos solo deben contener letras y espacios.',
            'email.required'      => 'El correo electrónico es obligatorio.',
            'email.email'         => 'Ingresa un correo electrónico válido (ej. usuario@colegio.edu.ve).',
            'email.unique'        => 'Este correo electrónico ya está registrado en otro usuario.',
            'cedula.required'     => 'La cédula o documento de identidad es obligatorio.',
            'cedula.regex'        => 'El formato del documento es inválido (6 a 9 dígitos para V/E o 6 a 15 caracteres para Pasaporte).',
            'cedula.unique'       => 'Esta cédula o documento ya se encuentra registrado en otro usuario.',
            'phone.regex'         => 'El teléfono debe tener un formato venezolano válido (ej. 0414-1234567).',
            'roles.required'      => 'Debe asignar al menos un rol al usuario.',
            'roles.min'           => 'Debe asignar al menos un rol al usuario.',
            'roles.*.not_in'      => 'El rol SuperAdmin es exclusivo y no puede asignarse desde este formulario.',
        ];

        $securityEnabled = config('app.security_questions_enabled', false);

        if (!$securityEnabled) {
            $rules['password'] = ['nullable', 'string', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()->symbols()];
        }

        $validated = $request->validate($rules, $messages);

        $before = $user->only(['name', 'first_name', 'last_name', 'email', 'cedula', 'phone', 'is_active']);

        $firstName = trim($validated['first_name']);
        $lastName  = trim($validated['last_name']);
        $fullName  = trim("{$firstName} {$lastName}");

        $user->update([
            'name'       => $fullName,
            'first_name' => $firstName,
            'last_name'  => $lastName,
            'email'      => $validated['email'],
            'cedula'     => $validated['cedula'],
            'phone'      => $validated['phone'] ?: null,
            'is_active'  => $isTargetSuperAdmin ? true : $validated['is_active'],
        ]);

        if (!$securityEnabled && !empty($validated['password'])) {
            $user->update(['password' => Hash::make($validated['password'])]);
        }

        $rolesToSync = $validated['roles'] ?? [];
        if ($isTargetSuperAdmin) {
            $rolesToSync = array_values(array_unique(array_merge(['SuperAdmin'], $rolesToSync)));
        }

        $user->syncRoles($rolesToSync);

        $diff = $this->diffProperties($before, $user->only(['name', 'first_name', 'last_name', 'email', 'cedula', 'phone', 'is_active']));
        $this->auditLog('usuarios', 'updated', "Editó al usuario {$user->name}", $user, $diff);

        return back()->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy(User $user)
    {
        if ($user->hasRole('SuperAdmin')) {
            return back()->with('error', 'El usuario con rol SuperAdmin no puede ser desactivado. Transfiera primero el rol si desea cambiar de titular.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes desactivar tu propia cuenta.');
        }

        $oldStatus = (bool) $user->is_active;
        $newStatus = !$oldStatus;

        $user->update(['is_active' => $newStatus]);

        $actionText = $newStatus ? 'Reactivó' : 'Desactivó';
        $this->auditLog(
            'usuarios',
            'updated',
            "{$actionText} al usuario {$user->name}",
            $user,
            ['old' => ['is_active' => $oldStatus], 'new' => ['is_active' => $newStatus]]
        );

        return back()->with('success', $newStatus ? 'Usuario reactivado exitosamente.' : 'Usuario desactivado.');
    }

    public function resetPassword(User $user)
    {
        if ($user->hasRole('SuperAdmin')) {
            return back()->with('error', 'No se puede restablecer la contraseña del SuperAdmin por esta vía.');
        }

        $tempPassword = $this->generateTemporaryPassword();

        $user->update([
            'password'             => Hash::make($tempPassword),
            'must_change_password' => config('app.security_questions_enabled', false),
        ]);

        // Eliminar preguntas de seguridad anteriores para que el usuario las configure de nuevo
        $user->securityQuestions()->delete();

        $this->auditLog('usuarios', 'updated', "Restableció la contraseña del usuario {$user->name}", $user);

        return back()
            ->with('success', "Contraseña restablecida para {$user->name}.")
            ->with('temp_credentials', [
                'action'   => 'reset',
                'name'     => $user->name,
                'email'    => $user->email,
                'cedula'   => $user->cedula,
                'password' => $tempPassword,
            ]);
    }

    /**
     * Transfiere la titularidad única del rol SuperAdmin a otro usuario activo.
     */
    public function transferSuperAdmin(Request $request, User $user)
    {
        $currentUser = $request->user();

        if (!$currentUser || !$currentUser->hasRole('SuperAdmin')) {
            abort(403, 'Solo el SuperAdmin actual puede transferir este rol.');
        }

        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Ya posees el rol de SuperAdmin.');
        }

        if (!$user->is_active) {
            return back()->with('error', 'No se puede transferir el rol SuperAdmin a un usuario inactivo. Reactívelo primero.');
        }

        DB::transaction(function () use ($currentUser, $user) {
            $superAdminHolders = User::role('SuperAdmin')->get();
            $adminRoleExists   = Role::where('name', 'Administrador')->exists();

            foreach ($superAdminHolders as $holder) {
                if ($holder->id !== $user->id) {
                    $holder->removeRole('SuperAdmin');
                    if ($holder->roles()->count() === 0 && $adminRoleExists) {
                        $holder->assignRole('Administrador');
                    }
                }
            }

            $user->assignRole('SuperAdmin');
            $user->update([
                'is_active'            => true,
                'must_change_password' => false,
            ]);
        });

        $this->auditLog(
            'usuarios',
            'updated',
            "Transfirió la titularidad del rol SuperAdmin de {$currentUser->name} a {$user->name}",
            $user
        );

        return back()->with('success', "Se transfirió el rol SuperAdmin a {$user->name} exitosamente. Ahora tu cuenta tiene el rol de Administrador.");
    }
}
