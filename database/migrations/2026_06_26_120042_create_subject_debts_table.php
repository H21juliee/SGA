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
        Schema::create('subject_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->foreignId('subject_id')->constrained('subjects');
            $table->foreignId('origin_school_year_id')->constrained('school_years');
            $table->foreignId('origin_enrollment_id')->constrained('enrollments');
            $table->foreignId('resolution_enrollment_id')->nullable()->constrained('enrollments');
            $table->string('status', 20)->default('pending'); // pending | resolved
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index('student_id', 'idx_debts_student');
            $table->index('subject_id', 'idx_debts_subject');
            $table->index('status', 'idx_debts_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_debts');
    }
};
