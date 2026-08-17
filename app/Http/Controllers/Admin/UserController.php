<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'cedula' => 'nullable|string|max:20|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'cedula' => $validated['cedula'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        $user->syncRoles($validated['roles']);

        return back()->with('success', 'Usuario creado exitosamente.');
    }

    public function update(Request $request, User $user)
    {
        if ($user->hasRole('SuperAdmin') && !auth()->user()->hasRole('SuperAdmin')) {
            return back()->with('error', 'No tienes permiso para modificar a este usuario.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'cedula' => ['nullable', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8',
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|exists:roles,name',
            'is_active' => 'required|boolean',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'cedula' => $validated['cedula'],
            'phone' => $validated['phone'],
            'is_active' => $validated['is_active'],
        ]);

        if (!empty($validated['password'])) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        $user->syncRoles($validated['roles']);

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

        return back()->with('success', 'Usuario desactivado.');
    }
}


