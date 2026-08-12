<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Insertar valores por defecto
        $defaults = [
            'school_name'         => 'Nombre del Plantel',
            'school_code'         => '',
            'municipality'        => '',
            'state'               => '',
            'director_name'       => '',
            'control_study_name'  => '',
            'logo_path'           => null,
        ];

        foreach ($defaults as $key => $value) {
            DB::table('school_settings')->insert([
                'key'        => $key,
                'value'      => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};
