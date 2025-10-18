<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(),
            'reporter_name' => $this->faker->name(),
            'status' => $this->faker->randomElement(['Chưa xử lý', 'Đã xử lý']),
            'created_at' => $this->faker->dateTimeBetween('-60 days', 'now'),
            'updated_at' => now(),
        ];
    }
}
