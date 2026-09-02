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
        Schema::table('subject_debts', function (Blueprint $table) {
            $table->decimal('score', 4, 2)->nullable()->after('status');
            $table->string('moment', 50)->nullable()->after('score');
            $table->string('acta_number', 50)->nullable()->after('moment');
            $table->text('notes')->nullable()->after('acta_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_debts', function (Blueprint $table) {
            $table->dropColumn(['score', 'moment', 'acta_number', 'notes']);
        });
    }
};
