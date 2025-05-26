<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition()
    {
        return [
            'name' => $this->faker->city,
            'code' => strtoupper($this->faker->unique()->lexify('LOC????')),
            'address' => $this->faker->address,
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'country' => $this->faker->country,
            'zip_code' => $this->faker->postcode,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
