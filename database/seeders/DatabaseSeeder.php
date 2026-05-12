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
            AcademicLoadSeeder::class,
            MassGradeSeeder::class,
        ]);

        // Crear SuperAdmin por defecto
        $admin = User::create([
            'name' => 'Administrador SGE',
            'email' => 'admin@sge.test',
            'password' => bcrypt('password'),
            'cedula' => 'V-00000000',
            'is_active' => true,
        ]);
        $admin->assignRole('SuperAdmin');

        // Crear Docente de prueba
        $docente = User::create([
            'name' => 'María Rodríguez',
            'email' => 'docente@sge.test',
            'password' => bcrypt('password'),
            'cedula' => 'V-12345678',
            'is_active' => true,
        ]);
        $docente->assignRole('Docente');

        // Crear Secretaria de prueba
        $secretaria = User::create([
            'name' => 'Ana García',
            'email' => 'secretaria@sge.test',
            'password' => bcrypt('password'),
            'cedula' => 'V-87654321',
            'is_active' => true,
        ]);
        $secretaria->assignRole('Secretaria');
    }
}
