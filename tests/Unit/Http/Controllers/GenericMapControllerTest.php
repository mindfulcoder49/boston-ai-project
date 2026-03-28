<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\GenericMapController;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class GenericMapControllerTest extends TestCase
{
    public function test_cross_connection_models_use_deferred_source_lookup(): void
    {
        $controller = new class extends GenericMapController
        {
            public function shouldDefer(string $modelClass, string $queryConnectionName): bool
            {
                return $this->shouldDeferSourceLookup($modelClass, $queryConnectionName);
            }
        };

        $this->assertTrue($controller->shouldDefer(TestExternalCrimeModel::class, 'generic_map_query_test'));
    }
}

class TestExternalCrimeModel extends Model
{
    protected $connection = 'generic_map_source_test';

    protected $table = 'external_crimes';
}
