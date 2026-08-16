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
            $table->foreignId('guardian_id')->nullable()->constrained('guardians')->nullOnDelete();
        });

        // Data migration
        $students = DB::table('students')->whereNotNull('guardian_name')->get();
        foreach ($students as $student) {
            if (empty(trim($student->guardian_name))) continue;
            
            $guardian = DB::table('guardians')->where('name', $student->guardian_name)->first();
            if (!$guardian) {
                $guardianId = DB::table('guardians')->insertGetId([
                    'name' => $student->guardian_name,
                    'phone' => $student->guardian_phone,
                    'email' => $student->guardian_email,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $guardianId = $guardian->id;
            }
            DB::table('students')->where('id', $student->id)->update(['guardian_id' => $guardianId]);
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['guardian_name', 'guardian_phone', 'guardian_email']);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('guardian_name')->nullable();
            $table->string('guardian_phone')->nullable();
            $table->string('guardian_email')->nullable();
        });

        // Reverse data migration
        $students = DB::table('students')->whereNotNull('guardian_id')->get();
        foreach ($students as $student) {
            $guardian = DB::table('guardians')->where('id', $student->guardian_id)->first();
            if ($guardian) {
                DB::table('students')->where('id', $student->id)->update([
                    'guardian_name' => $guardian->name,
                    'guardian_phone' => $guardian->phone,
                    'guardian_email' => $guardian->email,
                ]);
            }
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['guardian_id']);
            $table->dropColumn('guardian_id');
        });
    }
};
