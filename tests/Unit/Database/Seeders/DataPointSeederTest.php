<?php

namespace Tests\Unit\Database\Seeders;

use App\Models\ConstructionOffHour;
use Database\Seeders\DataPointSeeder;
use Tests\TestCase;

class DataPointSeederTest extends TestCase
{
    public function test_it_includes_configured_shared_data_point_models(): void
    {
        $seeder = new class extends DataPointSeeder
        {
            public function exposedModelsToProcess(): array
            {
                return $this->modelsToProcess();
            }
        };

        $models = $seeder->exposedModelsToProcess();

        $this->assertContains(ConstructionOffHour::class, $models);
        $this->assertSame($models, array_values(array_unique($models)));
    }
}
