<?php

namespace App\Traits;

use App\Models\AcademicLoad;
use Illuminate\Support\Facades\Auth;

trait ValidatesTeacherLoad
{
    /**
     * Verifica si el usuario actual tiene permiso para operar
     * sobre una sección y materia específicas.
     * 
     * Retorna true si tiene acceso (SuperAdmin/Admin o Docente asignado).
     * Lanza abort(403) si es un docente sin acceso.
     * 
     * @param int|string $sectionId
     * @param int|string $subjectId
     * @return bool
     */
    protected function authorizeLoad($sectionId, $subjectId): bool
    {
        $user = Auth::user();

        // Si no está autenticado, no pasa (aunque el middleware debería bloquearlo antes)
        if (!$user) {
            abort(403, 'No autorizado.');
        }

        // Si es docente, debemos verificar su carga académica
        if ($user->hasRole('Docente')) {
            $hasAccess = AcademicLoad::where('teacher_id', $user->id)
                ->where('section_id', $sectionId)
                ->where('subject_id', $subjectId)
                ->exists();

            if (!$hasAccess) {
                abort(403, 'No tienes carga académica asignada para esta sección y materia.');
            }
        }

        // Si es Admin, SuperAdmin u otro rol, se asume que los middlewares
        // (permission:grades.view, etc) ya hicieron su trabajo, así que lo dejamos pasar.
        return true;
    }
}
