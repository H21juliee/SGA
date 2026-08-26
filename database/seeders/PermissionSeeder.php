<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

/**
 * PermissionSeeder — Registro seguro de permisos del sistema.
 *
 * ✅ SEGURO de ejecutar en cualquier momento y cuantas veces quieras.
 * ✅ Solo CREA permisos nuevos (firstOrCreate), NUNCA modifica existentes.
 * ✅ No toca la asignación de permisos a roles.
 * ✅ No afecta roles personalizados creados desde la UI.
 *
 * Uso:
 *   php artisan db:seed --class=PermissionSeeder
 *
 * INSTRUCCIONES para agregar permisos de un módulo nuevo:
 * 1. Agrega las entradas en la lista $permissions de este archivo.
 * 2. Ejecuta: php artisan db:seed --class=PermissionSeeder
 * 3. El nuevo permiso estará disponible en la pantalla de Roles y Permisos
 *    para asignarlo manualmente a los roles que corresponda.
 * 4. Opcionalmente, agrega el grupo en RoleController::groupedPermissions()
 *    para que aparezca organizado en la UI de gestión de roles.
 */
class PermissionSeeder extends Seeder
{
    /**
     * Lista COMPLETA de permisos del sistema.
     * Agrega aquí los permisos de cada nuevo módulo.
     *
     * Convención de nombres: {módulo}.{acción}
     * Ejemplos: teachers.view, teachers.create, guardians.view
     */
        protected array $permissions = [
        'students.view', 'students.create', 'students.edit', 'students.delete',
        'enrollments.update_status', 'enrollments.transfer',
        'enrollments.view', 'enrollments.create', 'enrollments.edit', 'enrollments.delete',
        'attendance.view', 'attendance.manage', 'attendance.lock',
        'grades.view', 'grades.edit',
        'revisions.view', 'revisions.edit',
        'council.view', 'council.manage', 'council.batch_update',
        'school_years.view', 'school_years.manage', 'school_years.toggle', 'school_years.promote', 'school_years.toggle_lapse',
        'sections.view', 'sections.manage',
        'subjects.view', 'subjects.manage',
        'academic_load.view', 'academic_load.manage', 'academic_load.assign',
        'reports.generate',
        'users.view', 'users.manage', 'users.reset_password',
        'roles.view', 'roles.manage',
        'settings.manage',
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $created = 0;
        $skipped = 0;

        foreach ($this->permissions as $permission) {
            $result = Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );

            if ($result->wasRecentlyCreated) {
                $created++;
                $this->command?->line("  <fg=green>CREADO</> → {$permission}");
            } else {
                $skipped++;
            }
        }

        $this->command?->info("\n  ✅ {$created} permiso(s) nuevo(s) creado(s). {$skipped} ya existían.");
        $this->command?->comment("  Los permisos nuevos ya están disponibles en la pantalla de Roles y Permisos.");
    }
}