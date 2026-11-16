<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'entry_type')) {
                $table->string('entry_type', 20)->default('new')->after('status');
            }
            if (!Schema::hasColumn('students', 'target_grade_level_id')) {
                $table->foreignId('target_grade_level_id')
                    ->nullable()
                    ->after('entry_type')
                    ->constrained('grade_levels')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('students', 'origin_school')) {
                $table->string('origin_school')->nullable()->after('target_grade_level_id');
            }
        });

        // Backfill de target_grade_level_id para estudiantes existentes
        $firstGradeLevel = DB::table('grade_levels')->orderBy('order_num')->first();
        $gradeLevelsByOrder = DB::table('grade_levels')->orderBy('order_num')->get()->keyBy('order_num');
        $gradeLevelsById = DB::table('grade_levels')->get()->keyBy('id');

        if ($firstGradeLevel) {
            $students = DB::table('students')->select('id', 'target_grade_level_id')->get();

            foreach ($students as $student) {
                if ($student->target_grade_level_id !== null) {
                    continue;
                }

                // Buscar la última matrícula del estudiante
                $latestEnrollment = DB::table('enrollments')
                    ->join('sections', 'enrollments.section_id', '=', 'sections.id')
                    ->where('enrollments.student_id', $student->id)
                    ->orderByDesc('enrollments.school_year_id')
                    ->orderByDesc('enrollments.id')
                    ->select('enrollments.status', 'sections.grade_level_id')
                    ->first();

                $targetLevelId = $firstGradeLevel->id;

                if ($latestEnrollment) {
                    $currentLevel = $gradeLevelsById->get($latestEnrollment->grade_level_id);
                    if ($currentLevel) {
                        if (in_array($latestEnrollment->status, ['promoted', 'promoted_pending'], true)) {
                            $nextLevel = $gradeLevelsByOrder->get(((int) $currentLevel->order_num) + 1);
                            $targetLevelId = $nextLevel ? $nextLevel->id : $currentLevel->id;
                        } else {
                            $targetLevelId = $currentLevel->id;
                        }
                    }
                }

                DB::table('students')
                    ->where('id', $student->id)
                    ->update([
                        'target_grade_level_id' => $targetLevelId,
                        'entry_type'            => 'new',
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'target_grade_level_id')) {
                $table->dropForeign(['target_grade_level_id']);
                $table->dropColumn('target_grade_level_id');
            }
            if (Schema::hasColumn('students', 'entry_type')) {
                $table->dropColumn('entry_type');
            }
            if (Schema::hasColumn('students', 'origin_school')) {
                $table->dropColumn('origin_school');
            }
        });
    }
};
