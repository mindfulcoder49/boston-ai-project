<?php

namespace Tests\Unit\Database\Seeders;

use Database\Seeders\MontgomeryCountyMdCrimeSeeder;
use Tests\TestCase;

class MontgomeryCountyMdCrimeSeederTest extends TestCase
{
    public function test_it_normalizes_current_csv_headers(): void
    {
        $seeder = new class extends MontgomeryCountyMdCrimeSeeder {
            public function normalizeHeader(string $column): string
            {
                return $this->normalizeHeaderColumn($column);
            }
        };

        $this->assertSame('dispatch_date_time', $seeder->normalizeHeader('Dispatch Date / Time'));
        $this->assertSame('crime_name1', $seeder->normalizeHeader('Crime Name1'));
        $this->assertSame('council_districts_7', $seeder->normalizeHeader('Council Districts 7'));
    }

    public function test_it_maps_current_source_keys_to_canonical_schema_keys(): void
    {
        $seeder = new class extends MontgomeryCountyMdCrimeSeeder {
            public function mapKeys(array $record): array
            {
                return $this->mapSourceRecordKeys($record);
            }
        };

        $mapped = $seeder->mapKeys([
            'cr_number' => 'CR-123',
            'dispatch_date_time' => '2026-03-26T00:00:00.000',
            'start_date_time' => '2026-03-26T00:10:00.000',
            'end_date_time' => '2026-03-26T00:20:00.000',
            'crime_name1' => 'Crime Against Person',
            'crime_name2' => 'Simple Assault',
            'crime_name3' => 'ASSAULT - 2ND DEGREE',
            'police_district_name' => 'WHEATON',
            'street_prefix' => 'N',
            'street_name' => 'GEORGIA',
            'block_address' => '11100 BLK GEORGIA AVE',
        ]);

        $this->assertSame('CR-123', $mapped['case_number']);
        $this->assertSame('2026-03-26T00:00:00.000', $mapped['date']);
        $this->assertSame('2026-03-26T00:10:00.000', $mapped['start_date']);
        $this->assertSame('2026-03-26T00:20:00.000', $mapped['end_date']);
        $this->assertSame('Crime Against Person', $mapped['crimename1']);
        $this->assertSame('Simple Assault', $mapped['crimename2']);
        $this->assertSame('ASSAULT - 2ND DEGREE', $mapped['crimename3']);
        $this->assertSame('WHEATON', $mapped['district']);
        $this->assertSame('N', $mapped['street_prefix_dir']);
        $this->assertSame('GEORGIA', $mapped['address_street']);
        $this->assertArrayHasKey('block_address', $mapped);
    }
}
