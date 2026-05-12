<?php

namespace App\DTOs;

use App\Enums\AttendanceStatus;

final readonly class AttendanceDTO
{
    public function __construct(
        public int $enrollmentId,
        public int $subjectId,
        public string $date,
        public AttendanceStatus $status,
        public int $lapseId,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            enrollmentId: $data['enrollment_id'],
            subjectId: $data['subject_id'],
            date: $data['date'],
            status: AttendanceStatus::from($data['status']),
            lapseId: $data['lapse_id'],
            notes: $data['notes'] ?? null,
        );
    }
}
