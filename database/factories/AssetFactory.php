<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Asset::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'asset_code' => 'AST-' . $this->faker->unique()->randomNumber(5),
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'category_id' => AssetCategory::factory(),
            'location_id' => Location::factory(),
            'department_id' => Department::factory(),
            'status' => $this->faker->randomElement(['available', 'in_use', 'maintenance', 'disposed']),
            'manufacturer' => $this->faker->company,
            'model' => $this->faker->word,
            'serial_number' => $this->faker->unique()->uuid,
            'purchase_date' => $this->faker->date(),
            'purchase_cost' => $this->faker->randomFloat(2, 100, 10000),
            'supplier_id' => null,
            'purchase_order_number' => 'PO-' . $this->faker->randomNumber(5),
            'asset_condition' => $this->faker->randomElement(['excellent', 'good', 'fair', 'poor']),
            'warranty_start_date' => now(),
            'warranty_expiry_date' => now()->addMonths($this->faker->numberBetween(12, 60)),
            'warranty_provider' => $this->faker->company,
            'warranty_details' => $this->faker->sentence,
            'depreciation_method' => 'straight_line',
            'expected_lifetime_years' => $this->faker->numberBetween(1, 10),
            'salvage_value' => $this->faker->randomFloat(2, 0, 1000),
            'current_value' => $this->faker->randomFloat(2, 100, 10000),
            'depreciation_rate' => $this->faker->randomFloat(2, 5, 20),
            'depreciation_start_date' => now(),
            'depreciation_frequency' => 'yearly',
            'insurer_company' => $this->faker->company,
            'policy_number' => 'POL-' . $this->faker->randomNumber(6),
            'coverage_details' => $this->faker->sentence,
            'insurance_start_date' => now(),
            'insurance_end_date' => now()->addYear(),
            'premium_amount' => $this->faker->randomFloat(2, 100, 1000),
            'barcode' => $this->faker->ean13,
            'qr_code' => $this->faker->uuid,
            'assigned_to' => User::factory(),
            'assigned_date' => now(),
            'notes' => $this->faker->paragraph,
            'created_by' => 1, // Assuming user with ID 1 exists
            'updated_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the asset is available.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function available()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'available',
                'assigned_to' => null,
            ];
        });
    }

    /**
     * Indicate that the asset is in use.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function inUse()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'in_use',
            ];
        });
    }

    /**
     * Indicate that the asset is under maintenance.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function underMaintenance()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'maintenance',
                'assigned_to' => null,
            ];
        });
    }

    /**
     * Indicate that the asset is disposed.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function disposed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'disposed',
                'assigned_to' => null,
            ];
        });
    }
}
