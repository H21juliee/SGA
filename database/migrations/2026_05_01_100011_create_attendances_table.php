<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 20);
            $table->foreignId('lapse_id')->constrained('lapses');
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->index('enrollment_id', 'idx_attendances_enrollment');
            $table->index('date', 'idx_attendances_date');
            $table->index('lapse_id', 'idx_attendances_lapse');
            $table->unique(['enrollment_id', 'date'], 'uq_attendance_enrollment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
