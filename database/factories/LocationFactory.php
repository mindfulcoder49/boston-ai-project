<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->streetName(),
            'address' => fake()->streetAddress() . ', Boston, MA 02110, USA',
            'latitude' => 42.3601,
            'longitude' => -71.0589,
            'language' => 'en',
            'report' => null,
        ];
    }
}
