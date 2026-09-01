<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            GradeLevelSeeder::class,
            SubjectSeeder::class,
            LargeDataSeeder::class,
        ]);

        // Crear SuperAdmin por defecto
        $admin = User::updateOrCreate(
            ['email' => 'admin@sge.test'],
            [
                'name' => 'Administrador SGA',
                'password' => bcrypt('password'),
                'cedula' => 'V-00000000',
                'is_active' => true,
            ]
        );
        $admin->assignRole('SuperAdmin');

        // Crear Administrador (Rol secundario)
        $administrador = User::updateOrCreate(
            ['email' => 'admin_secundario@sge.test'],
            [
                'name' => 'Director SGE',
                'password' => bcrypt('password'),
                'cedula' => 'V-11111111',
                'is_active' => true,
            ]
        );
        $administrador->assignRole('Administrador');

        // Crear Docente de prueba
        $docente = User::updateOrCreate(
            ['email' => 'docente@sge.test'],
            [
                'name' => 'María Rodríguez',
                'password' => bcrypt('password'),
                'cedula' => 'V-12345678',
                'is_active' => true,
            ]
        );
        $docente->assignRole('Docente');

        // Crear Secretaria de prueba
        $secretaria = User::updateOrCreate(
            ['email' => 'secretaria@sge.test'],
            [
                'name' => 'Ana García',
                'password' => bcrypt('password'),
                'cedula' => 'V-87654321',
                'is_active' => true,
            ]
        );
        $secretaria->assignRole('Secretaria');
    }
}
