<?php

namespace App\DTOs;

use App\Http\Requests\StoreGradeRequest;

final readonly class GradeDTO
{
    public function __construct(
        public int $enrollmentId,
        public int $subjectId,
        public int $lapseId,
        public float $score,
    ) {}

    public static function fromRequest(StoreGradeRequest $request): self
    {
        return new self(
            enrollmentId: $request->validated('enrollment_id'),
            subjectId: $request->validated('subject_id'),
            lapseId: $request->validated('lapse_id'),
            score: $request->validated('score'),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            enrollmentId: $data['enrollment_id'],
            subjectId: $data['subject_id'],
            lapseId: $data['lapse_id'],
            score: $data['score'],
        );
    }
}
