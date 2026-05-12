<?php

use App\Models\SchoolYear;
use App\Models\Enrollment;
use App\Enums\EnrollmentStatus;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$currentYearId = 1; // 2025-2026
$nextYearId = 3;    // 2026-2027

DB::transaction(function () use ($currentYearId, $nextYearId) {
    // 1. Reset year statuses
    SchoolYear::find($currentYearId)->update(['is_active' => true, 'is_closed' => false]);
    SchoolYear::find($nextYearId)->update(['is_active' => false, 'is_closed' => false]);

    // 2. Delete enrollments created in the next year
    Enrollment::where('school_year_id', $nextYearId)->delete();

    // 3. Reset student statuses in the current year
    Enrollment::where('school_year_id', $currentYearId)
        ->whereIn('status', [EnrollmentStatus::PROMOTED, EnrollmentStatus::FAILED])
        ->update(['status' => EnrollmentStatus::ACTIVE]);
        
    echo "Promoción revertida exitosamente. Ahora puedes volver a intentar el proceso desde la interfaz.\n";
});
