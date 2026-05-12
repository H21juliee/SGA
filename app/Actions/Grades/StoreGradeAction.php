<?php

namespace App\Actions\Grades;

use App\DTOs\GradeDTO;
use App\Models\Grade;
use Illuminate\Validation\ValidationException;

final class StoreGradeAction
{
    public function execute(GradeDTO $dto): Grade
    {
        // Validación de negocio: rango 1-20
        if ($dto->score < 1 || $dto->score > 20) {
            throw ValidationException::withMessages([
                'score' => 'La nota debe estar en el rango de 1 a 20.',
            ]);
        }

        return Grade::updateOrCreate(
            [
                'enrollment_id' => $dto->enrollmentId,
                'subject_id' => $dto->subjectId,
                'lapse_id' => $dto->lapseId,
            ],
            [
                'score' => $dto->score,
            ]
        );
    }
}
