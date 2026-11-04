<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();

            // Información Adicional
            $table->json('lives_with')->nullable(); // e.g. ["Madre", "Padre", "Otros"]
            $table->unsignedSmallInteger('family_group_count')->nullable();
            $table->string('religion', 100)->nullable();
            $table->boolean('extracurricular_activities')->default(false);
            $table->string('extracurricular_activities_detail', 255)->nullable();
            $table->boolean('has_canaima')->default(false);
            $table->boolean('uses_canteen')->default(false);

            // Información Médica
            $table->boolean('has_medical_condition')->default(false);
            $table->string('medical_condition_detail', 255)->nullable();
            $table->boolean('has_allergies')->default(false);
            $table->string('allergies_detail', 255)->nullable();
            $table->boolean('requires_pe_medication')->default(false);
            $table->boolean('has_medical_certificate')->default(false);
            $table->string('blood_type', 10)->nullable();
            $table->string('height', 20)->nullable();
            $table->string('weight', 20)->nullable();
            $table->string('doctor_visit_frequency', 50)->nullable();

            // Información de Habitación
            $table->string('housing_type', 50)->nullable();
            $table->string('housing_tenure', 50)->nullable();
            $table->string('housing_condition', 50)->nullable();
            $table->boolean('has_water')->default(false);
            $table->boolean('has_electricity')->default(false);
            $table->boolean('has_internet')->default(false);
            $table->text('additional_services')->nullable();

            // Información de Comunidad
            $table->string('community_name', 150)->nullable();
            $table->boolean('belongs_to_communal_council')->default(false);
            $table->json('functioning_services')->nullable();
            $table->string('services_quality', 50)->nullable();
            $table->json('community_institutions')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
