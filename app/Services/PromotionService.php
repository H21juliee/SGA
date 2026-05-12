<?php

namespace App\Services;

use App\Actions\Grades\CalculateAverageAction;
use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Lapse;
use App\Models\SchoolYear;
use App\Models\Section;
use Illuminate\Support\Facades\DB;

final class PromotionService
{
    private array $promoted = [];
    private array $failed = [];
    private array $graduated = [];

    public function __construct(
        private CalculateAverageAction $calcAverage,
    ) {}

    /**
     * Ejecuta la promoción masiva para un año escolar.
     */
    public function promoteAll(SchoolYear $currentYear, SchoolYear $nextYear): array
    {
        $this->validatePreConditions($currentYear);

        $this->promoted = [];
        $this->failed = [];
        $this->graduated = [];

        DB::beginTransaction();

        try {
            $enrollments = Enrollment::where('school_year_id', $currentYear->id)
                ->where('status', EnrollmentStatus::ACTIVE)
                ->with(['student', 'section.gradeLevel', 'grades'])
                ->get();

            foreach ($enrollments as $enrollment) {
                $this->processEnrollment($enrollment, $nextYear);
            }

            // Cerrar el año escolar actual
            $currentYear->update([
                'is_closed' => true,
                'is_active' => false,
            ]);

            // Activar el nuevo año
            $nextYear->update(['is_active' => true]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'promoted' => $this->promoted,
            'failed' => $this->failed,
            'graduated' => $this->graduated,
            'total_promoted' => count($this->promoted),
            'total_failed' => count($this->failed),
            'total_graduated' => count($this->graduated),
            'total_processed' => count($this->promoted) + count($this->failed) + count($this->graduated),
        ];
    }

    private function validatePreConditions(SchoolYear $current): void
    {
        if ($current->is_closed) {
            throw new \RuntimeException('Este año escolar ya fue cerrado.');
        }

        $openLapses = Lapse::where('school_year_id', $current->id)
            ->where('is_open', true)
            ->count();

        if ($openLapses > 0) {
            throw new \RuntimeException('Hay lapsos aún abiertos. Ciérrelos primero.');
        }
    }

    private function processEnrollment(Enrollment $enrollment, SchoolYear $nextYear): void
    {
        $gradeLevel = $enrollment->section->gradeLevel;
        $isApproved = $this->calcAverage->isApproved($enrollment);

        if ($isApproved) {
            $this->promoteStudent($enrollment, $gradeLevel, $nextYear);
        } else {
            $this->failStudent($enrollment, $gradeLevel, $nextYear);
        }
    }

    private function promoteStudent(Enrollment $enrollment, GradeLevel $currentLevel, SchoolYear $nextYear): void
    {
        $enrollment->update(['status' => EnrollmentStatus::PROMOTED]);

        // Si es 5to año → graduado
        if ($currentLevel->isLast()) {
            $this->graduated[] = $enrollment->student_id;
            return;
        }

        $nextLevel = $currentLevel->next();

        // Buscar o crear sección equivalente (misma letra)
        $nextSection = Section::firstOrCreate(
            [
                'school_year_id' => $nextYear->id,
                'grade_level_id' => $nextLevel->id,
                'name' => $enrollment->section->name,
            ],
            [
                'capacity' => $enrollment->section->capacity,
            ]
        );

        Enrollment::create([
            'student_id' => $enrollment->student_id,
            'section_id' => $nextSection->id,
            'school_year_id' => $nextYear->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => $nextYear->start_date,
        ]);

        $this->promoted[] = $enrollment->student_id;
    }

    private function failStudent(Enrollment $enrollment, GradeLevel $currentLevel, SchoolYear $nextYear): void
    {
        $enrollment->update(['status' => EnrollmentStatus::FAILED]);

        // Buscar o crear sección equivalente (misma letra)
        $sameSection = Section::firstOrCreate(
            [
                'school_year_id' => $nextYear->id,
                'grade_level_id' => $currentLevel->id,
                'name' => $enrollment->section->name,
            ],
            [
                'capacity' => $enrollment->section->capacity,
            ]
        );

        Enrollment::create([
            'student_id' => $enrollment->student_id,
            'section_id' => $sameSection->id,
            'school_year_id' => $nextYear->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => $nextYear->start_date,
        ]);

        $this->failed[] = $enrollment->student_id;
    }
}
