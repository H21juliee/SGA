<?php

namespace App\Policies;

use App\Models\AcademicLoad;
use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attendance.view');
    }

    public function manage(User $user, int $sectionId, int $schoolYearId): bool
    {
        if ($user->hasRole(['SuperAdmin', 'Administrador'])) {
            return true;
        }

        if (!$user->can('attendance.manage')) {
            return false;
        }

        // Docente: solo si tiene alguna materia asignada en esa sección
        return AcademicLoad::where('teacher_id', $user->id)
            ->where('section_id', $sectionId)
            ->where('school_year_id', $schoolYearId)
            ->exists();
    }
}
