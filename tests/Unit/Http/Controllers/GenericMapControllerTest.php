<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\GenericMapController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class GenericMapControllerTest extends TestCase
{
    public function test_resolve_join_metadata_qualifies_cross_database_mysql_tables(): void
    {
        Config::set('database.connections.generic_map_query_test', [
            'driver' => 'mysql',
            'host' => 'mysql.test',
            'port' => 3306,
            'database' => 'query_db',
            'unix_socket' => null,
        ]);

        Config::set('database.connections.generic_map_source_test', [
            'driver' => 'mysql',
            'host' => 'mysql.test',
            'port' => 3306,
            'database' => 'source_db',
            'unix_socket' => null,
        ]);

        $controller = new class extends GenericMapController
        {
            public function joinMetadata(string $modelClass, string $queryConnectionName): array
            {
                return $this->resolveJoinMetadata($modelClass, $queryConnectionName);
            }
        };

        $metadata = $controller->joinMetadata(TestExternalCrimeModel::class, 'generic_map_query_test');

        $this->assertSame('`source_db`.`external_crimes`', $metadata['table_reference']);
        $this->assertSame('test_external_crime_model_source', $metadata['table_alias']);
    }
}

class TestExternalCrimeModel extends Model
{
    protected $connection = 'generic_map_source_test';

    protected $table = 'external_crimes';
}
