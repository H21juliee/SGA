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
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'cedula' => 'V-' . $this->faker->unique()->numberBetween(10000000, 35000000),
            'birth_date' => $this->faker->date('Y-m-d', '-10 years'),
            'gender' => $this->faker->randomElement(['M', 'F']),
            'address' => $this->faker->address(),
            'guardian_name' => $this->faker->name(),
            'guardian_phone' => $this->faker->phoneNumber(),
            'guardian_email' => $this->faker->safeEmail(),
            'is_active' => true,
        ];
    }
}
