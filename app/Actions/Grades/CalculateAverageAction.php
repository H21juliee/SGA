<?php

namespace App\Actions\Grades;

use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Subject;

final class CalculateAverageAction
{
    /**
     * Calcula la nota final de una materia para un enrollment (promedio de 3 lapsos).
     */
    public function forSubject(Enrollment $enrollment, Subject $subject): ?float
    {
        $grades = Grade::where('enrollment_id', $enrollment->id)
            ->where('subject_id', $subject->id)
            ->get();

        if ($grades->isEmpty()) {
            return null;
        }

        return round($grades->avg('score'), 2);
    }

    /**
     * Calcula el promedio general ponderado de todas las materias.
     */
    public function overall(Enrollment $enrollment): ?float
    {
        $section = $enrollment->section()->with('gradeLevel')->first();
        $subjects = Subject::where('grade_level_id', $section->grade_level_id)->get();

        if ($subjects->isEmpty()) {
            return null;
        }

        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($subjects as $subject) {
            $finalGrade = $this->forSubject($enrollment, $subject);
            if ($finalGrade === null) {
                continue;
            }
            $weightedSum += $finalGrade * (float) $subject->weight;
            $totalWeight += (float) $subject->weight;
        }

        if ($totalWeight === 0) {
            return null;
        }

        return round($weightedSum / $totalWeight, 2);
    }

    /**
     * Verifica si el estudiante aprobó todas las materias (nota final >= 10).
     */
    public function isApproved(Enrollment $enrollment): bool
    {
        $section = $enrollment->section()->with('gradeLevel')->first();
        $subjects = Subject::where('grade_level_id', $section->grade_level_id)->get();

        foreach ($subjects as $subject) {
            $finalGrade = $this->forSubject($enrollment, $subject);
            if ($finalGrade === null || $finalGrade < 10) {
                return false;
            }
        }

        return true;
    }
}
