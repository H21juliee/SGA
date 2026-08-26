<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new \Illuminate\Routing\Controllers\Middleware('permission:roles.view', only: ['index', 'show']),
            new \Illuminate\Routing\Controllers\Middleware('permission:roles.manage', only: ['store', 'update', 'destroy']),
        ];
    }
    private function groupedPermissions(): array
    {
        $groups = [
            'Estudiantes — Directorio'    => ['students.view', 'students.create', 'students.edit', 'students.delete'],
            'Estudiantes — Inscripciones' => ['enrollments.view', 'enrollments.create', 'enrollments.edit', 'enrollments.delete', 'enrollments.update_status', 'enrollments.transfer'],
            'Asistencia'                  => ['attendance.view', 'attendance.manage', 'attendance.lock'],
            'Calificaciones — Notas'      => ['grades.view', 'grades.edit'],
            'Calificaciones — Revisiones' => ['revisions.view', 'revisions.edit'],
            'Calificaciones — Consejo'    => ['council.view', 'council.manage', 'council.batch_update'],
            'Planif. — Años Escolares'    => ['school_years.view', 'school_years.manage', 'school_years.toggle', 'school_years.promote', 'school_years.toggle_lapse'],
            'Planif. — Secciones'         => ['sections.view', 'sections.manage'],
            'Planif. — Materias'          => ['subjects.view', 'subjects.manage'],
            'Planif. — Carga Académica'   => ['academic_load.view', 'academic_load.manage', 'academic_load.assign'],
            'Reportes'                    => ['reports.generate'],
            'Admin — Usuarios'            => ['users.view', 'users.manage', 'users.reset_password'],
            'Admin — Roles y Permisos'    => ['roles.view', 'roles.manage'],
            'Admin — Configuración'       => ['settings.manage'],
        ];

        $allPerms = Permission::orderBy('name')->pluck('name')->toArray();
        $assigned = array_merge(...\array_values($groups));
        $others = array_diff($allPerms, $assigned);
        if (!empty($others)) {
            $groups['Otros'] = array_values($others);
        }

        return $groups;
    }

    private function permissionLabels(): array
    {
        return [
            // Estudiantes
            'students.view' => 'Ver estudiantes',
            'students.create' => 'Crear estudiante',
            'students.edit' => 'Editar estudiante',
            'students.delete' => 'Eliminar estudiante',
            
            // Inscripciones
            'enrollments.view' => 'Ver inscripciones',
            'enrollments.create' => 'Crear inscripción',
            'enrollments.edit' => 'Editar inscripción',
            'enrollments.delete' => 'Eliminar inscripción',
            'enrollments.update_status' => 'Cambiar estado (retirado, etc)',
            'enrollments.transfer' => 'Transferir de sección',
            
            // Asistencia
            'attendance.view' => 'Ver asistencia',
            'attendance.manage' => 'Registrar/Editar asistencia',
            'attendance.lock' => 'Bloquear asistencia',
            
            // Calificaciones
            'grades.view' => 'Ver notas',
            'grades.edit' => 'Editar notas',
            'revisions.view' => 'Ver revisiones',
            'revisions.edit' => 'Editar revisiones',
            'council.view' => 'Ver ajuste de consejo',
            'council.manage' => 'Ajustar nota',
            'council.batch_update' => 'Guardado masivo de ajustes',
            
            // Planificación
            'school_years.view' => 'Ver años escolares',
            'school_years.manage' => 'Crear/Editar/Eliminar años',
            'school_years.toggle' => 'Cambiar año activo',
            'school_years.promote' => 'Cierre y Promoción masiva',
            'school_years.toggle_lapse' => 'Abrir/Cerrar lapsos',
            
            'sections.view' => 'Ver secciones',
            'sections.manage' => 'Crear/Editar/Eliminar secciones',
            'subjects.view' => 'Ver materias',
            'subjects.manage' => 'Crear/Editar/Eliminar materias',
            'academic_load.view' => 'Ver carga académica',
            'academic_load.manage' => 'Crear/Editar/Eliminar carga',
            'academic_load.assign' => 'Asignación rápida de carga',
            
            // Reportes
            'reports.generate' => 'Generar y descargar reportes',
            
            // Admin
            'users.view' => 'Ver usuarios',
            'users.manage' => 'Crear/Editar/Eliminar usuarios',
            'users.reset_password' => 'Restablecer contraseña',
            'roles.view' => 'Ver roles y permisos',
            'roles.manage' => 'Administrar roles',
            'settings.manage' => 'Configuración institucional',
        ];
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
            'permissionLabels'   => $this->permissionLabels(),
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