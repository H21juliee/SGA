<?php

namespace App\Policies;

use App\Models\AcademicLoad;
use App\Models\Grade;
use App\Models\User;

class GradePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('grades.view');
    }

    public function update(User $user, Grade $grade): bool
    {
        if ($user->hasRole(['SuperAdmin', 'Administrador'])) {
            return true;
        }

        if (!$user->can('grades.edit')) {
            return false;
        }

        // Docente: solo si la materia+sección está en su carga académica
        $enrollment = $grade->enrollment;

        return AcademicLoad::where('teacher_id', $user->id)
            ->where('subject_id', $grade->subject_id)
            ->where('section_id', $enrollment->section_id)
            ->where('school_year_id', $enrollment->school_year_id)
            ->exists();
    }
}
