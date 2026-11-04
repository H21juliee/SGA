<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware
{
    use LogsActivity;
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
            'Estudiantes — Directorio y Registro' => ['students.view', 'students.create', 'students.edit', 'students.delete'],
            'Estudiantes — Matriculación'         => ['enrollments.view', 'enrollments.create', 'enrollments.edit', 'enrollments.delete', 'enrollments.update_status', 'enrollments.transfer'],
            'Asistencia'                          => ['attendance.view', 'attendance.manage', 'attendance.lock'],
            'Calificaciones — Notas'              => ['grades.view', 'grades.edit'],
            'Calificaciones — Revisiones'         => ['revisions.view', 'revisions.edit'],
            'Calificaciones — Ajuste de Consejo'  => ['council.view', 'council.manage', 'council.batch_update'],
            'Planificación — Períodos Escolares'  => ['school_years.view', 'school_years.manage', 'school_years.toggle', 'school_years.toggle_lapse', 'school_years.promote'],
            'Planificación — Años y Secciones'    => ['sections.view', 'sections.manage'],
            'Planificación — Materias'            => ['subjects.view', 'subjects.manage'],
            'Planificación — Carga Académica'     => ['academic_load.view', 'academic_load.manage', 'academic_load.assign'],
            'Reportes y Estadísticas'             => ['reports.generate'],
            'Importación Masiva'                  => ['students.import', 'subjects.import', 'teachers.import'],
            'Administración — Usuarios'           => ['users.view', 'users.manage', 'users.reset_password'],
            'Administración — Roles y Permisos'   => ['roles.view', 'roles.manage'],
            'Administración — Configuración'      => ['settings.manage'],
            'Administración — Auditoría'          => ['audit.view'],
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
            // Estudiantes — Directorio y Registro
            'students.view' => 'Ver directorio de estudiantes',
            'students.create' => 'Registrar nuevo estudiante (Inscripción)',
            'students.edit' => 'Editar datos del estudiante',
            'students.delete' => 'Retirar / Eliminar estudiante',
            
            // Estudiantes — Matriculación
            'enrollments.view' => 'Ver matriculación por sección',
            'enrollments.create' => 'Matricular estudiante en sección',
            'enrollments.edit' => 'Editar matrícula',
            'enrollments.delete' => 'Eliminar matrícula',
            'enrollments.update_status' => 'Cambiar estado de matrícula (retirado, etc)',
            'enrollments.transfer' => 'Transferir de sección',
            
            // Asistencia
            'attendance.view' => 'Ver asistencia',
            'attendance.manage' => 'Registrar / Editar asistencia',
            'attendance.lock' => 'Bloquear asistencia',
            
            // Calificaciones
            'grades.view' => 'Ver notas',
            'grades.edit' => 'Cargar / Editar notas',
            'revisions.view' => 'Ver revisiones',
            'revisions.edit' => 'Cargar / Editar revisiones',
            'council.view' => 'Ver ajuste de consejo',
            'council.manage' => 'Ajustar nota de consejo',
            'council.batch_update' => 'Guardado masivo de ajustes de consejo',
            
            // Planificación
            'school_years.view' => 'Ver períodos escolares',
            'school_years.manage' => 'Crear / Editar períodos escolares',
            'school_years.toggle' => 'Cambiar período escolar activo',
            'school_years.toggle_lapse' => 'Abrir / Cerrar lapsos académicos',
            'school_years.promote' => 'Cierre de período y promoción masiva',
            
            'sections.view' => 'Ver años, secciones y coordinadores',
            'sections.manage' => 'Gestionar secciones y coordinadores de año',
            'subjects.view' => 'Ver materias',
            'subjects.manage' => 'Crear / Editar materias',
            'academic_load.view' => 'Ver carga académica docente',
            'academic_load.manage' => 'Crear / Editar carga académica',
            'academic_load.assign' => 'Asignación rápida de carga docente',
            
            // Reportes
            'reports.generate' => 'Generar y descargar reportes y estadísticas',

            // Importación Masiva
            'students.import' => 'Importar estudiantes desde Excel/CSV',
            'subjects.import' => 'Importar materias desde Excel/CSV',
            'teachers.import' => 'Importar docentes desde Excel/CSV',

            // Administración
            'users.view' => 'Ver usuarios',
            'users.manage' => 'Crear / Editar usuarios',
            'users.reset_password' => 'Restablecer contraseña de usuarios',
            'roles.view' => 'Ver roles y permisos',
            'roles.manage' => 'Crear / Editar / Eliminar roles',
            'settings.manage' => 'Configuración institucional del plantel',
            'audit.view' => 'Ver registro de auditoría y trazabilidad',
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

        $this->auditLog('roles', 'created', "Creó el rol \"{$validated['name']}\"", $role);

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

        $oldName = $role->name;
        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        $diff = $oldName !== $validated['name']
            ? ['old' => ['name' => $oldName], 'new' => ['name' => $validated['name']]]
            : [];
        $this->auditLog('roles', 'updated', "Editó el rol \"{$validated['name']}\"", $role, $diff);

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

        $this->auditLog('roles', 'deleted', "Eliminó el rol \"{$name}\"");

        return back()->with('success', "Rol \"{$name}\" eliminado.");
    }
}