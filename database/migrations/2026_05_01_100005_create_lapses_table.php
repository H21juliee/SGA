<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lapses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->tinyInteger('number')->unsigned();
            $table->string('name', 50);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_open')->default(false);
            $table->timestamps();

            $table->index('school_year_id', 'idx_lapses_school_year');
            $table->unique(['school_year_id', 'number'], 'uq_lapses_year_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lapses');
    }
};
