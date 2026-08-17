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
            'students.view', 'students.create', 'students.edit', 'students.delete',
            'enrollments.view', 'enrollments.create', 'enrollments.edit', 'enrollments.delete',
            'attendance.view', 'attendance.manage',
            'grades.view', 'grades.edit',
            'revisions.view', 'revisions.edit',
            'council.view', 'council.manage',
            'school_years.view', 'school_years.manage',
            'sections.view', 'sections.manage',
            'subjects.view', 'subjects.manage',
            'academic_load.view', 'academic_load.manage',
            'reports.generate',
            'users.view', 'users.manage',
            'roles.view', 'roles.manage',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // SuperAdmin
        $superAdmin = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // Administrador (Todo menos roles)
        $admin = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $admin->syncPermissions(
            collect($permissions)->reject(fn($p) => in_array($p, ['roles.view', 'roles.manage']))->toArray()
        );

        // Docente (Foco en lo académico)
        $docente = Role::firstOrCreate(['name' => 'Docente', 'guard_name' => 'web']);
        $docente->syncPermissions([
            'attendance.view', 'attendance.manage',
            'grades.view', 'grades.edit',
        ]);

        // Secretaria (Foco en administración estudiantil e inscripciones)
        $secretaria = Role::firstOrCreate(['name' => 'Secretaria', 'guard_name' => 'web']);
        $secretaria->syncPermissions([
            'students.view', 'students.create', 'students.edit', 'students.delete',
            'enrollments.view', 'enrollments.create', 'enrollments.edit', 'enrollments.delete',
            'attendance.view', 'attendance.manage',
            'grades.view',
            'revisions.view',
            'school_years.view',
            'sections.view', 'sections.manage',
            'subjects.view', 'subjects.manage',
            'academic_load.view', 'academic_load.manage',
            'reports.generate'
        ]);
    }
}