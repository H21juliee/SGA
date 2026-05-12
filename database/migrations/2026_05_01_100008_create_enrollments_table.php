<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('section_id')->constrained('sections');
            $table->foreignId('school_year_id')->constrained('school_years');
            $table->string('status', 20)->default('active');
            $table->date('enrolled_at');
            $table->timestamps();

            $table->index('student_id', 'idx_enrollments_student');
            $table->index('section_id', 'idx_enrollments_section');
            $table->index('school_year_id', 'idx_enrollments_school_year');
            $table->unique(['student_id', 'school_year_id'], 'uq_enrollment_student_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
