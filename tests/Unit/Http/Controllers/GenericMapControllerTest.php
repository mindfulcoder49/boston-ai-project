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

            public function sanitize(array $attributes): array
            {
                return $this->sanitizeSourceRecord($attributes);
            }

            public function deferredFields(string $modelClass): array
            {
                $model = new $modelClass();

                return $this->getDeferredPreviewSelectFields($modelClass, $model);
            }
        };

        $this->assertTrue($controller->shouldDefer(TestExternalCrimeModel::class, 'generic_map_query_test'));
        $this->assertSame(
            ['incident_id', 'date', 'crimename2', 'crimename3', 'address_number'],
            $controller->deferredFields(TestExternalCrimeModel::class),
        );
    }

    public function test_sanitize_source_record_strips_non_utf8_strings(): void
    {
        $controller = new class extends GenericMapController
        {
            public function sanitize(array $attributes): array
            {
                return $this->sanitizeSourceRecord($attributes);
            }
        };

        $sanitized = $controller->sanitize([
            'safe_text' => 'Main St',
            'binary_blob' => "\xC3\x28",
        ]);

        $this->assertSame('Main St', $sanitized['safe_text']);
        $this->assertNull($sanitized['binary_blob']);
    }
}

class TestExternalCrimeModel extends Model
{
    protected $connection = 'generic_map_source_test';

    protected $table = 'external_crimes';

    protected $primaryKey = 'incident_id';

    protected $fillable = ['crimename2', 'crimename3', 'address_number', 'geolocation'];

    public static function getDateField(): string
    {
        return 'date';
    }
}
