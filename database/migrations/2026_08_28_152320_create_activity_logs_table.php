<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Quién realizó la acción (null si fue el sistema)
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Módulo: 'estudiantes', 'usuarios', 'roles', 'consejo', 'revisiones' ...
            $table->string('module', 60);

            // Tipo de acción: 'created', 'updated', 'deleted', 'imported',
            //                 'promoted', 'council_updated', 'revision_updated'
            $table->string('action', 40);

            // Modelo afectado (nullables para acciones sin registro específico)
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            // Descripción legible: "Editó al estudiante González, Juan"
            $table->text('description');

            // Before/after en formato JSON: {"old": {...}, "new": {...}}
            // Null cuando no aplica (ej. creación sin campos anteriores)
            $table->json('properties')->nullable();

            // IP del cliente (soporte IPv6)
            $table->string('ip_address', 45)->nullable();

            // Solo created_at — el log es append-only, nunca se modifica
            $table->timestamp('created_at')->useCurrent();
        });

        // Índices para las consultas más frecuentes
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(['module', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};

