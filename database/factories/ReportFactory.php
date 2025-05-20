<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ReportFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Report::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $types = ['asset', 'user', 'audit'];
        $formats = ['xlsx', 'csv', 'pdf'];
        $statuses = ['pending', 'processing', 'completed', 'failed'];

        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'type' => $this->faker->randomElement($types),
            'format' => $this->faker->randomElement($formats),
            'filters' => json_encode([
                'status' => $this->faker->randomElement(['active', 'inactive', 'all']),
                'date_from' => $this->faker->dateTimeThisYear->format('Y-m-d'),
                'date_to' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            ]),
            'status' => $this->faker->randomElement($statuses),
            'file_path' => null,
            'file_generated_at' => null,
            'error_message' => null,
            'created_by' => User::factory(),
        ];
    }

    public function assetsType()
    {
        return $this->state([
            'type' => 'assets',
            'columns' => ['id', 'name', 'asset_tag', 'status', 'purchase_date'],
        ]);
    }

    public function maintenanceType()
    {
        return $this->state([
            'type' => 'maintenance',
            'columns' => ['id', 'title', 'status', 'start_date', 'completion_date', 'cost'],
        ]);
    }

    public function depreciationType()
    {
        return $this->state([
            'type' => 'depreciation',
            'columns' => ['id', 'name', 'purchase_date', 'purchase_cost', 'current_value'],
        ]);
    }

    public function public()
    {
        return $this->state([
            'is_public' => true,
        ]);
    }

    public function private()
    {
        return $this->state([
            'is_public' => false,
        ]);
    }
}
