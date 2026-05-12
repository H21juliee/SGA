<?php

namespace App\Actions\Attendance;

use App\DTOs\AttendanceDTO;
use App\Models\Attendance;

final class RecordAttendanceAction
{
    public function execute(AttendanceDTO $dto): Attendance
    {
        return Attendance::updateOrCreate(
            [
                'enrollment_id' => $dto->enrollmentId,
                'subject_id' => $dto->subjectId,
                'date' => $dto->date,
            ],
            [
                'status' => $dto->status,
                'lapse_id' => $dto->lapseId,
                'notes' => $dto->notes,
            ]
        );
    }
}
