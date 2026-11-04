<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->string('nationality', 5)->default('V')->after('last_name');
            $table->string('birth_place', 255)->nullable()->after('nationality');
            $table->date('birth_date')->nullable()->after('birth_place');
            $table->string('education_level', 100)->nullable()->after('birth_date');
            $table->text('address')->nullable()->after('education_level');
            $table->boolean('is_working')->default(false)->after('address');
            $table->string('work_sector', 50)->nullable()->after('is_working');
            $table->string('work_type', 50)->nullable()->after('work_sector');
            $table->string('occupation', 150)->nullable()->after('work_type');
            $table->string('phone_home', 30)->nullable()->after('phone');
            $table->string('relationship', 50)->nullable()->after('occupation');
        });
    }

    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table) {
            $table->dropColumn([
                'nationality',
                'birth_place',
                'birth_date',
                'education_level',
                'address',
                'is_working',
                'work_sector',
                'work_type',
                'occupation',
                'phone_home',
                'relationship',
            ]);
        });
    }
};
