<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Enrollment;
use App\Enums\EnrollmentStatus;
use Illuminate\Database\Seeder;

class LargeDataSeeder extends Seeder
{
    public function run(): void
    {
        $activeYear = SchoolYear::active()->first();

        // Evitar duplicar datos si el seeder ya corrió parcialmente
        if (Student::count() >= 100) {
            $this->command->info('Los datos masivos ya parecen estar cargados. Saltando...');
            return;
        }

        if (!$activeYear) {
            $activeYear = SchoolYear::firstOrCreate(
                ['name' => '2025-2026'],
                [
                    'start_date' => '2025-09-01',
                    'end_date' => '2026-07-15',
                    'is_active' => true,
                ]
            );
            
            // Create lapses for this year
            for ($i = 1; $i <= 3; $i++) {
                $activeYear->lapses()->create([
                    'name' => "{$i}er Lapso",
                    'number' => $i,
                    'is_open' => true,
                    'start_date' => $activeYear->start_date,
                    'end_date' => $activeYear->end_date,
                ]);
            }
        }

        $levels = GradeLevel::orderBy('order_num')->get();
        $sections = ['A', 'B', 'C'];
        $allCreatedSections = [];

        foreach ($levels as $level) {
            foreach ($sections as $sectionName) {
                $allCreatedSections[] = Section::firstOrCreate([
                    'school_year_id' => $activeYear->id,
                    'grade_level_id' => $level->id,
                    'name' => $sectionName,
                ], [
                    'capacity' => 40,
                ]);
            }
        }

        $this->command->info('Creando 700 alumnos...');
        $students = Student::factory()->count(700)->create();

        $this->command->info('Inscribiendo alumnos en las secciones...');
        $sectionCount = count($allCreatedSections);
        
        foreach ($students as $index => $student) {
            $section = $allCreatedSections[$index % $sectionCount];
            
            Enrollment::create([
                'student_id' => $student->id,
                'section_id' => $section->id,
                'school_year_id' => $activeYear->id,
                'status' => EnrollmentStatus::ACTIVE,
                'enrolled_at' => $activeYear->start_date,
            ]);
        }

        $this->command->info('Seeder completado con éxito.');
    }
}
