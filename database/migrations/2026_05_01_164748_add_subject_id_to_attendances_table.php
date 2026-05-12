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
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('uq_attendance_enrollment_date');
            
            $table->foreignId('subject_id')->after('enrollment_id')->constrained('subjects');
            
            $table->unique(['enrollment_id', 'subject_id', 'date'], 'uq_attendance_enrollment_subject_date');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('uq_attendance_enrollment_subject_date');
            $table->dropForeign(['subject_id']);
            $table->dropColumn('subject_id');
            
            $table->unique(['enrollment_id', 'date'], 'uq_attendance_enrollment_date');
        });
    }
};
