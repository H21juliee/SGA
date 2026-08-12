<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('revision_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects');
            $table->decimal('score', 4, 2);
            $table->string('status', 20)->default('pending'); // pending | approved | failed
            $table->date('evaluated_at')->nullable();
            $table->timestamps();

            $table->index('enrollment_id', 'idx_revision_grades_enrollment');
            $table->index('subject_id', 'idx_revision_grades_subject');
            $table->unique(['enrollment_id', 'subject_id'], 'uq_revision_enrollment_subject');
        });

        DB::statement('ALTER TABLE revision_grades ADD CONSTRAINT chk_revision_score CHECK (score >= 1 AND score <= 20)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revision_grades');
    }
};
