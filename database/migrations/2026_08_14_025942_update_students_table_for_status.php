<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('status', 30)->default('regular')->after('is_active');
        });

        // Migrate existing data
        DB::table('students')->where('is_active', true)->update(['status' => 'regular']);
        DB::table('students')->where('is_active', false)->update(['status' => 'withdrawn']);

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('status');
        });

        DB::table('students')->where('status', 'regular')->update(['is_active' => true]);
        DB::table('students')->where('status', 'withdrawn')->update(['is_active' => false]);
        DB::table('students')->whereNotIn('status', ['regular', 'withdrawn'])->update(['is_active' => false]);

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
