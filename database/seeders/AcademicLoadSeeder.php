<?php

namespace Database\Seeders;

use App\Models\AcademicLoad;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AcademicLoadSeeder extends Seeder
{
    public function run(): void
    {
        $activeYear = SchoolYear::active()->first();
        if (!$activeYear) return;

        $this->command->info('Creando 30 docentes...');
        
        $teachers = [];
        for ($i = 1; $i <= 30; $i++) {
            $teacher = User::create([
                'name' => fake()->name(),
                'email' => "docente{$i}@sge.test",
                'password' => Hash::make('password'),
                'cedula' => 'V-' . fake()->unique()->numberBetween(10000000, 25000000),
                'is_active' => true,
            ]);
            $teacher->assignRole('Docente');
            $teachers[] = $teacher;
        }

        $sections = Section::where('school_year_id', $activeYear->id)->get();
        $this->command->info('Asignando carga académica a los docentes...');

        $teacherIndex = 0;
        $totalLoads = 0;

        foreach ($sections as $section) {
            // Obtener materias de este nivel
            $subjects = Subject::where('grade_level_id', $section->grade_level_id)->get();

            foreach ($subjects as $subject) {
                // Seleccionar un docente (round-robin)
                $teacher = $teachers[$teacherIndex % count($teachers)];
                
                AcademicLoad::updateOrCreate(
                    [
                        'subject_id' => $subject->id,
                        'section_id' => $section->id,
                        'school_year_id' => $activeYear->id,
                    ],
                    [
                        'teacher_id' => $teacher->id,
                    ]
                );

                $teacherIndex++;
                $totalLoads++;
            }
        }

        $this->command->info("Se han creado {$totalLoads} asignaciones académicas.");
    }
}
