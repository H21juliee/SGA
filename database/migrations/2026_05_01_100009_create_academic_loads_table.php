<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_loads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users');
            $table->foreignId('subject_id')->constrained('subjects');
            $table->foreignId('section_id')->constrained('sections');
            $table->foreignId('school_year_id')->constrained('school_years');
            $table->timestamps();

            $table->index('teacher_id', 'idx_academic_loads_teacher');
            $table->index('subject_id', 'idx_academic_loads_subject');
            $table->index('section_id', 'idx_academic_loads_section');
            $table->index('school_year_id', 'idx_academic_loads_school_year');
            $table->unique(
                ['teacher_id', 'subject_id', 'section_id', 'school_year_id'],
                'uq_load_teacher_subject_section_year'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_loads');
    }
};
