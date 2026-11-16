<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
        });

        // Backfill first_name y last_name para usuarios existentes a partir de name
        $users = DB::table('users')->select('id', 'name', 'first_name', 'last_name')->get();

        foreach ($users as $user) {
            if (!empty($user->first_name) || !empty($user->last_name)) {
                continue;
            }

            $fullName = trim((string) $user->name);
            if ($fullName === '') {
                continue;
            }

            $parts = preg_split('/\s+/', $fullName) ?: [];
            $count = count($parts);

            if ($count === 1) {
                $firstName = $parts[0];
                $lastName = '';
            } elseif ($count === 2) {
                $firstName = $parts[0];
                $lastName = $parts[1];
            } elseif ($count === 3) {
                $firstName = $parts[0];
                $lastName = $parts[1] . ' ' . $parts[2];
            } else {
                $half = (int) ceil($count / 2);
                $firstName = implode(' ', array_slice($parts, 0, $half));
                $lastName = implode(' ', array_slice($parts, $half));
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('users', 'first_name')) {
                $columns[] = 'first_name';
            }
            if (Schema::hasColumn('users', 'last_name')) {
                $columns[] = 'last_name';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
