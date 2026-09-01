<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use App\Models\SchoolYear;
use App\Models\Section;
use Illuminate\Database\Seeder;

class LargeDataSeeder extends Seeder
{
    public function run(): void
    {
        $activeYear = SchoolYear::active()->first();

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
                    'is_open' => false,
                    'start_date' => $activeYear->start_date,
                    'end_date' => $activeYear->end_date,
                ]);
            }
        }

        $levels = GradeLevel::orderBy('order_num')->get();
        $sections = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
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

        $this->command->info('Año Escolar, Lapsos y Secciones (A,B,C) creados con éxito.');
    }
}
