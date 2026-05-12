<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'first_name' => \fake()->firstName(),
            'last_name' => \fake()->lastName(),
            'cedula' => 'V-' . \fake()->unique()->numberBetween(10000000, 35000000),
            'birth_date' => \fake()->date('Y-m-d', '-10 years'),
            'gender' => \fake()->randomElement(['M', 'F']),
            'address' => \fake()->address(),
            'guardian_name' => \fake()->name(),
            'guardian_phone' => \fake()->phoneNumber(),
            'guardian_email' => \fake()->safeEmail(),
            'is_active' => true,
        ];
    }
}
