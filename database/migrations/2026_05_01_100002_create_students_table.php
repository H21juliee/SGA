<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('cedula', 20)->unique()->nullable();
            $table->date('birth_date');
            $table->enum('gender', ['M', 'F']);
            $table->text('address')->nullable();
            $table->string('guardian_name', 255)->nullable();
            $table->string('guardian_phone', 20)->nullable();
            $table->string('guardian_email', 255)->nullable();
            $table->string('photo_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('cedula', 'idx_students_cedula');
            $table->index(['last_name', 'first_name'], 'idx_students_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
