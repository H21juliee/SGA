<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private function groupedPermissions(): array
    {
                $groups = [
            // Menú: Estudiantes
            'Estudiantes — Directorio'    => ['students.view', 'students.create', 'students.edit', 'students.delete'],
            'Estudiantes — Inscripciones' => ['enrollments.view', 'enrollments.create', 'enrollments.edit', 'enrollments.delete'],
            
            // Menú: Asistencia
            'Asistencia'                  => ['attendance.view', 'attendance.manage'],
            
            // Menú: Calificaciones
            'Calificaciones — Notas'      => ['grades.view', 'grades.edit'],
            'Calificaciones — Revisiones' => ['revisions.view', 'revisions.edit'],
            'Calificaciones — Consejo'    => ['council.view', 'council.manage'],
            
            // Menú: Planificación
            'Planif. — Años Escolares'    => ['school_years.view', 'school_years.manage'],
            'Planif. — Secciones'         => ['sections.view', 'sections.manage'],
            'Planif. — Materias'          => ['subjects.view', 'subjects.manage'],
            'Planif. — Carga Académica'   => ['academic_load.view', 'academic_load.manage'],
            
            // Menú: Reportes
            'Reportes'                    => ['reports.generate'],
            
            // Menú: Administración
            'Admin — Usuarios'            => ['users.view', 'users.manage'],
            'Admin — Roles y Permisos'    => ['roles.view', 'roles.manage'],
            'Admin — Configuración'       => ['settings.manage'],
        ];;

        $allPerms = Permission::orderBy('name')->pluck('name')->toArray();
        $assigned = array_merge(...array_values($groups));
        $others = array_diff($allPerms, $assigned);
        if (!empty($others)) {
            $groups['Otros'] = array_values($others);
        }

        return $groups;
    }

    public function index()
    {
        $roles = Role::withCount('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn($r) => [
                'id'                => $r->id,
                'name'              => $r->name,
                'permissions_count' => $r->permissions_count,
                'users_count'       => $r->users_count,
                'is_system'         => in_array($r->name, ['SuperAdmin']),
            ]);

        return Inertia::render('Admin/Roles/Index', [
            'roles'              => $roles,
            'groupedPermissions' => $this->groupedPermissions(),
        ]);
    }

    public function show(Role $role)
    {
        return response()->json([
            'role' => [
                'id'          => $role->id,
                'name'        => $role->name,
                'permissions' => $role->permissions->pluck('name'),
                'is_system'   => in_array($role->name, ['SuperAdmin']),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:60|unique:roles,name',
            'permissions'   => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
        $role->syncPermissions($validated['permissions'] ?? []);

        return back()->with('success', "Rol \"{$validated['name']}\" creado exitosamente.");
    }

    public function update(Request $request, Role $role)
    {
        if ($role->name === 'SuperAdmin') {
            return back()->with('error', 'El rol SuperAdmin no puede ser modificado.');
        }

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:60', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions'   => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return back()->with('success', "Rol \"{$validated['name']}\" actualizado exitosamente.");
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, ['SuperAdmin'])) {
            return back()->with('error', 'El rol SuperAdmin no puede eliminarse del sistema.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', "No se puede eliminar: {$role->users()->count()} usuario(s) tienen asignado este rol.");
        }

        $name = $role->name;
        $role->delete();

        return back()->with('success', "Rol \"{$name}\" eliminado.");
    }
}