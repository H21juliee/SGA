<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->tinyInteger('order_num')->unsigned();
            $table->timestamps();

            $table->unique('order_num', 'uq_grade_levels_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_levels');
    }
};
