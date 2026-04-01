<?php

namespace Tests\Unit;

use Database\Seeders\SeattleCrimeSeeder;
use ReflectionMethod;
use Tests\TestCase;

class SeattleCrimeSeederTest extends TestCase
{
    public function test_it_maps_current_source_headers_to_canonical_schema_columns(): void
    {
        $seeder = new SeattleCrimeSeeder();

        $normalizeHeaderColumn = new ReflectionMethod($seeder, 'normalizeHeaderColumn');
        $normalizeHeaderColumn->setAccessible(true);

        $mapSourceRecordKeys = new ReflectionMethod($seeder, 'mapSourceRecordKeys');
        $mapSourceRecordKeys->setAccessible(true);

        $normalizedHeader = $normalizeHeaderColumn->invoke($seeder, 'NIBRS Group AB');
        $mappedRecord = $mapSourceRecordKeys->invoke($seeder, [
            $normalizedHeader => 'A',
            'offense_id' => '123',
        ]);

        $this->assertSame('nibrs_group_ab', $normalizedHeader);
        $this->assertSame('A', $mappedRecord['nibrs_group_a_b'] ?? null);
        $this->assertArrayNotHasKey('nibrs_group_ab', $mappedRecord);
        $this->assertSame('123', $mappedRecord['offense_id'] ?? null);
    }
}
