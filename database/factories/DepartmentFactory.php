<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->company,
            'description' => $this->faker->sentence,
            'manager_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
