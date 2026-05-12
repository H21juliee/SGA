<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_level_id')->constrained('grade_levels');
            $table->foreignId('school_year_id')->constrained('school_years');
            $table->string('name', 10);
            $table->unsignedInteger('capacity')->default(40);
            $table->timestamps();

            $table->index('grade_level_id', 'idx_sections_grade_level');
            $table->index('school_year_id', 'idx_sections_school_year');
            $table->unique(['school_year_id', 'grade_level_id', 'name'], 'uq_sections_year_level_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
