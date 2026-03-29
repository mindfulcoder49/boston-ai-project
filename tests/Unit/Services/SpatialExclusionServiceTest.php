<?php

namespace Tests\Unit\Services;

use App\Models\Concerns\Mappable;
use App\Services\SpatialExclusionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class SpatialExclusionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'spatial_exclusions' => [
                SpatialExclusionTestCrimeModel::class => [
                    [
                        'latitude' => 42.1000000,
                        'longitude' => -71.2000000,
                    ],
                ],
            ],
        ]);

        Schema::dropIfExists('spatial_exclusion_test_records');
        Schema::create('spatial_exclusion_test_records', function (Blueprint $table): void {
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
        Schema::dropIfExists('spatial_exclusion_test_records');

        parent::tearDown();
    }

    public function test_apply_to_query_excludes_configured_coordinates(): void
    {
        SpatialExclusionTestCrimeModel::query()->insert([
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
                'lat' => 42.1000100,
                'long' => -71.2000200,
                'occurred_on_date' => '2026-03-29 13:00:00',
            ],
        ]);

        $query = SpatialExclusionTestCrimeModel::query();
        app(SpatialExclusionService::class)->applyToQuery($query, SpatialExclusionTestCrimeModel::class);

        $this->assertSame(['kept-1'], $query->orderBy('incident_number')->pluck('incident_number')->all());
    }

    public function test_is_excluded_coordinate_uses_normalized_exact_matches(): void
    {
        $service = app(SpatialExclusionService::class);

        $this->assertTrue($service->isExcludedCoordinate(SpatialExclusionTestCrimeModel::class, '42.1000000', '-71.2000000'));
        $this->assertFalse($service->isExcludedCoordinate(SpatialExclusionTestCrimeModel::class, 42.1001, -71.2));
        $this->assertFalse($service->isExcludedCoordinate(SpatialExclusionTestCrimeModel::class, null, -71.2));
    }
}

class SpatialExclusionTestCrimeModel extends Model
{
    use Mappable;

    protected $table = 'spatial_exclusion_test_records';

    public $timestamps = false;

    protected $guarded = [];

    public static function getHumanName(): string
    {
        return 'Spatial Exclusion Test Crime';
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
        return (string) $this->occurred_on_date;
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
