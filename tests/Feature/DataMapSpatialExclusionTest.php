<?php

namespace Tests\Feature;

use App\Models\Concerns\Mappable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DataMapSpatialExclusionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'data_map.models' => [
                'test_crime' => DataMapSpatialExclusionCrimeModel::class,
            ],
            'spatial_exclusions' => [
                DataMapSpatialExclusionCrimeModel::class => [
                    [
                        'latitude' => 42.1000000,
                        'longitude' => -71.2000000,
                    ],
                ],
            ],
        ]);

        Schema::dropIfExists('data_map_spatial_exclusion_records');
        Schema::create('data_map_spatial_exclusion_records', function (Blueprint $table): void {
            $table->id();
            $table->string('incident_number');
            $table->string('offense_description')->nullable();
            $table->string('offense_code_group')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('long', 10, 7)->nullable();
            $table->dateTime('occurred_on_date')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('data_map_spatial_exclusion_records');

        parent::tearDown();
    }

    public function test_data_map_endpoint_excludes_spatially_flagged_incidents(): void
    {
        DataMapSpatialExclusionCrimeModel::query()->insert([
            [
                'incident_number' => 'excluded-1',
                'offense_description' => 'Administrative anchor',
                'offense_code_group' => 'Service',
                'lat' => 42.1000000,
                'long' => -71.2000000,
                'occurred_on_date' => '2026-03-29 12:00:00',
            ],
            [
                'incident_number' => 'kept-1',
                'offense_description' => 'Legitimate incident',
                'offense_code_group' => 'Larceny',
                'lat' => 42.1000500,
                'long' => -71.2000500,
                'occurred_on_date' => '2026-03-29 12:30:00',
            ],
        ]);

        $response = $this->postJson('/api/data/test_crime', [
            'filters' => ['limit' => 10],
        ]);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.incident_number', 'kept-1');
    }
}

class DataMapSpatialExclusionCrimeModel extends Model
{
    use Mappable;

    protected $table = 'data_map_spatial_exclusion_records';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'occurred_on_date' => 'datetime',
    ];

    public static function getHumanName(): string
    {
        return 'Data Map Spatial Exclusion Crime';
    }

    public static function getIconClass(): string
    {
        return 'crime-div-icon';
    }

    public static function getAlcivartechTypeForStyling(): string
    {
        return 'Crime';
    }

    public static function getLatitudeField(): string
    {
        return 'lat';
    }

    public static function getLongitudeField(): string
    {
        return 'long';
    }

    public static function getDateField(): string
    {
        return 'occurred_on_date';
    }

    public function getDate(): ?string
    {
        return $this->occurred_on_date?->toDateTimeString();
    }

    public static function getExternalIdName(): string
    {
        return 'incident_number';
    }

    public function getExternalId(): string
    {
        return (string) $this->incident_number;
    }

    public static function getPopupConfig(): array
    {
        return [];
    }
}
