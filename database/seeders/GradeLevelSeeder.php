<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use Illuminate\Database\Seeder;

class GradeLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => '1er Año', 'order_num' => 1],
            ['name' => '2do Año', 'order_num' => 2],
            ['name' => '3er Año', 'order_num' => 3],
            ['name' => '4to Año', 'order_num' => 4],
            ['name' => '5to Año', 'order_num' => 5],
        ];

        foreach ($levels as $level) {
            GradeLevel::create($level);
        }
    }
}
