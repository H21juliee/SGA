<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('must_change_password')->default(true)->after('is_active');
        });

        // All existing users must configure security questions, except SuperAdmin
        $superAdminRoleId = DB::table('roles')->where('name', 'SuperAdmin')->value('id');

        if ($superAdminRoleId) {
            DB::table('users')->update(['must_change_password' => true]);
            
            $superAdminUserIds = DB::table('model_has_roles')
                ->where('role_id', $superAdminRoleId)
                ->where('model_type', 'App\\Models\\User')
                ->pluck('model_id');

            if ($superAdminUserIds->isNotEmpty()) {
                DB::table('users')->whereIn('id', $superAdminUserIds)->update(['must_change_password' => false]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('must_change_password');
        });
    }
};