<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects');
            $table->foreignId('lapse_id')->constrained('lapses');
            $table->decimal('score', 4, 2);
            $table->timestamps();

            $table->index('enrollment_id', 'idx_grades_enrollment');
            $table->index('subject_id', 'idx_grades_subject');
            $table->index('lapse_id', 'idx_grades_lapse');
            $table->unique(['enrollment_id', 'subject_id', 'lapse_id'], 'uq_grades_enrollment_subject_lapse');
        });

        // CHECK constraint para rango 1-20
        DB::statement('ALTER TABLE grades ADD CONSTRAINT chk_grades_score CHECK (score >= 1 AND score <= 20)');
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
