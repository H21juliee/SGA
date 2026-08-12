<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->tinyInteger('council_adjustment')->default(0)->after('score');
        });

        // CHECK constraint para rango -5 a +5
        DB::statement('ALTER TABLE grades ADD CONSTRAINT chk_grades_council CHECK (council_adjustment >= -5 AND council_adjustment <= 5)');
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn('council_adjustment');
        });
    }
};
