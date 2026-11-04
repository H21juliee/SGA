<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('nationality', 10)->default('V')->after('cedula');
            $table->string('birth_place', 255)->nullable()->after('birth_date');
            $table->foreignId('community_id')->nullable()->after('address')->constrained('communities')->nullOnDelete();
            $table->string('email', 255)->nullable()->after('community_id');
            $table->string('phone_home', 20)->nullable()->after('email');
            $table->string('phone_mobile', 20)->nullable()->after('phone_home');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['community_id']);
            $table->dropColumn([
                'nationality',
                'birth_place',
                'community_id',
                'email',
                'phone_home',
                'phone_mobile',
            ]);
        });
    }
};
