<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'students.view', 'students.create', 'students.edit', 'students.delete', 'students.import',
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
            'school_years.manage', 'school_years.toggle', 'school_years.promote', 'school_years.toggle_lapse', 'academic_load.assign',
            'reports.generate',
            'users.view', 'users.manage', 'users.reset_password',
            'roles.view', 'roles.manage',
            'settings.manage',
            'audit.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // SuperAdmin
        $superAdmin = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // Administrador (Todo menos roles y auditoría — solo SuperAdmin ve el log)
        $admin = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $admin->syncPermissions(
            collect($permissions)->reject(fn($p) => in_array($p, ['roles.view', 'roles.manage', 'audit.view']))->toArray()
        );

        // Docente (Foco en lo académico)
        $docente = Role::firstOrCreate(['name' => 'Docente', 'guard_name' => 'web']);
        $docente->syncPermissions([
            'attendance.view', 'attendance.manage', 'attendance.lock',
            'grades.view', 'grades.edit',
        ]);

        // Secretaria (Foco en administración estudiantil e inscripciones)
        $secretaria = Role::firstOrCreate(['name' => 'Secretaria', 'guard_name' => 'web']);
        $secretaria->syncPermissions([
            'students.view', 'students.create', 'students.edit', 'students.delete', 'students.import',
            'enrollments.update_status', 'enrollments.transfer',
            'enrollments.view', 'enrollments.create', 'enrollments.edit', 'enrollments.delete',
            'attendance.view', 'attendance.manage', 'attendance.lock',
            'grades.view',
            'revisions.view',
            'school_years.view',
            'sections.view', 'sections.manage',
            'subjects.view', 'subjects.manage',
            'academic_load.view', 'academic_load.manage', 'academic_load.assign',
            'school_years.manage', 'school_years.toggle', 'school_years.promote', 'school_years.toggle_lapse', 'academic_load.assign',
            'reports.generate'
        ]);
    }
}