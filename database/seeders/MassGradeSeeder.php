<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Lapse;
use App\Models\SchoolYear;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class MassGradeSeeder extends Seeder
{
    public function run(): void
    {
        $activeYear = SchoolYear::active()->first();
        if (!$activeYear) {
            $this->command->error('No hay un año escolar activo.');
            return;
        }

        $openLapse = Lapse::where('school_year_id', $activeYear->id)->where('is_open', true)->first();
        
        if (!$openLapse) {
            $this->command->info('No hay lapsos abiertos. Abriendo el 1er Lapso...');
            $openLapse = Lapse::where('school_year_id', $activeYear->id)->where('number', 1)->first();
            if ($openLapse) {
                $openLapse->update(['is_open' => true]);
            } else {
                $this->command->error('No se encontró el 1er Lapso.');
                return;
            }
        }

        $this->command->info("Cargando notas para el: {$openLapse->name} ({$activeYear->name})");

        $enrollments = Enrollment::where('school_year_id', $activeYear->id)->with('section.gradeLevel')->get();
        $this->command->info("Procesando " . $enrollments->count() . " inscripciones...");

        $totalGrades = 0;
        foreach ($enrollments as $enrollment) {
            $subjects = Subject::where('grade_level_id', $enrollment->section->grade_level_id)->get();
            
            foreach ($subjects as $subject) {
                // Generar nota entre 10 y 20 (mayoría aprobados para pruebas reales)
                // Usamos updateOrCreate para evitar duplicados si se corre dos veces
                Grade::updateOrCreate(
                    [
                        'enrollment_id' => $enrollment->id,
                        'subject_id' => $subject->id,
                        'lapse_id' => $openLapse->id,
                    ],
                    [
                        'score' => rand(10, 20),
                    ]
                );
                $totalGrades++;
            }
        }

        $this->command->info("Se han cargado {$totalGrades} notas exitosamente.");
    }
}
