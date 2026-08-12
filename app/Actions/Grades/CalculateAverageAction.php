<?php

namespace App\Actions\Grades;

use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\RevisionGrade;
use App\Models\Subject;

final class CalculateAverageAction
{
    /**
     * Calcula la nota final de una materia para un enrollment (promedio de 3 lapsos).
     */
    public function forSubject(Enrollment $enrollment, Subject $subject): ?float
    {
        // Materias cualitativas no tienen nota numérica
        if ($subject->isQualitative()) {
            return null;
        }

        $grades = Grade::where('enrollment_id', $enrollment->id)
            ->where('subject_id', $subject->id)
            ->get();

        if ($grades->isEmpty()) {
            return null;
        }

        // Usar la nota definitiva (score + council_adjustment) para cada lapso
        $definitives = $grades->map(fn($g) => $g->definitive);
        return round($definitives->avg(), 2);
    }

    /**
     * Calcula el promedio general ponderado de todas las materias.
     */
    public function overall(Enrollment $enrollment): ?float
    {
        $section = $enrollment->section()->with('gradeLevel')->first();
        // Excluir materias cualitativas del promedio general
        $subjects = Subject::where('grade_level_id', $section->grade_level_id)
            ->where('grading_type', 'numeric')
            ->get();

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
        return $this->getFailedSubjects($enrollment)->isEmpty();
    }

    /**
     * Retorna la colección de materias que el estudiante aplazó.
     * Toma en cuenta las notas de revisión: si aprobó la revisión, no se considera aplazada.
     */
    public function getFailedSubjects(Enrollment $enrollment)
    {
        $section = $enrollment->section()->with('gradeLevel')->first();
        // Solo materias numéricas pueden aplazarse
        $subjects = Subject::where('grade_level_id', $section->grade_level_id)
            ->where('grading_type', 'numeric')
            ->get();
        $failedSubjects = collect();

        foreach ($subjects as $subject) {
            $finalGrade = $this->forSubject($enrollment, $subject);
            if ($finalGrade === null || $finalGrade < 10) {
                // Verificar si tiene nota de revisión aprobada
                $revision = RevisionGrade::where('enrollment_id', $enrollment->id)
                    ->where('subject_id', $subject->id)
                    ->first();

                if ($revision && $revision->score >= 10) {
                    continue;
                }

                $failedSubjects->push($subject);
            }
        }

        return $failedSubjects;
    }

    /**
     * Retorna la nota de revisión de una materia si existe.
     */
    public function getRevisionGrade(Enrollment $enrollment, Subject $subject): ?RevisionGrade
    {
        return RevisionGrade::where('enrollment_id', $enrollment->id)
            ->where('subject_id', $subject->id)
            ->first();
    }
}
