<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Crear permisos
        $permissions = [
            'students.view',
            'students.create',
            'students.edit',
            'students.delete',
            'enrollments.view',
            'enrollments.create',
            'enrollments.edit',
            'enrollments.delete',
            'grades.view',
            'grades.edit',
            'attendance.view',
            'attendance.manage',
            'reports.generate',
            'school_years.view',
            'school_years.manage',
            'sections.manage',
            'subjects.manage',
            'academic_load.view',
            'academic_load.manage',
            'promotion.execute',
            'roles.manage',
            'users.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // SuperAdmin — todos los permisos
        $superAdmin = Role::create(['name' => 'SuperAdmin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Administrador — todo excepto gestión de roles
        $admin = Role::create(['name' => 'Administrador']);
        $admin->givePermissionTo(
            collect($permissions)->reject(fn($p) => $p === 'roles.manage')->toArray()
        );

        // Docente — solo su carga académica (filtrado por Policies)
        $docente = Role::create(['name' => 'Docente']);
        $docente->givePermissionTo([
            'grades.view',
            'grades.edit',
            'attendance.view',
            'attendance.manage',
            'reports.generate',
            'academic_load.view',
        ]);

        // Secretaria — gestión administrativa
        $secretaria = Role::create(['name' => 'Secretaria']);
        $secretaria->givePermissionTo([
            'students.view',
            'students.create',
            'students.edit',
            'enrollments.view',
            'enrollments.create',
            'enrollments.edit',
            'reports.generate',
            'school_years.view',
            'academic_load.view',
        ]);
    }
}
