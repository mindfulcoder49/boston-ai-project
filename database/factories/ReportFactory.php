<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'location_id' => Location::factory(),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
            'generated_at' => now(),
        ];
    }
}
