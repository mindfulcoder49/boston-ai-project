<?php

namespace Database\Factories;

use App\Models\CoverageRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoverageRequest>
 */
class CoverageRequestFactory extends Factory
{
    protected $model = CoverageRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'email' => fake()->safeEmail(),
            'requested_address' => fake()->streetAddress() . ', Los Angeles, CA 90012, USA',
            'normalized_address' => fake()->streetAddress() . ', Los Angeles, CA 90012, USA',
            'latitude' => 34.0522,
            'longitude' => -118.2437,
            'source_page' => '/crime-address',
            'status' => 'pending',
            'notes' => null,
            'request_count' => 1,
        ];
    }

    public function forUser(?User $user = null): static
    {
        return $this->state(fn () => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }
}
