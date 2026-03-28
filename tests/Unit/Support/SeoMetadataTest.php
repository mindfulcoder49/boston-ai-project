<?php

namespace Tests\Unit\Support;

use App\Support\SeoMetadata;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class SeoMetadataTest extends TestCase
{
    public function test_home_metadata_matches_the_address_first_homepage(): void
    {
        $metadata = SeoMetadata::fromInertiaPage([
            'component' => 'Home',
            'props' => [],
        ], Request::create('https://publicdatawatch.com/', 'GET'));

        $this->assertSame(
            'PublicDataWatch | Know What Crime Is Happening Around Your Address',
            $metadata['title']
        );
        $this->assertStringContainsString('Search an address to see recent nearby crime', $metadata['description']);
        $this->assertSame('https://publicdatawatch.com/', $metadata['canonical']);
    }

    public function test_crime_address_metadata_is_specific_to_the_new_funnel(): void
    {
        $metadata = SeoMetadata::fromInertiaPage([
            'component' => 'CrimeAddress/Index',
            'props' => [],
        ], Request::create('https://publicdatawatch.com/crime-address?address=851+Broadway', 'GET'));

        $this->assertSame('Crime Around Your Address | PublicDataWatch', $metadata['title']);
        $this->assertStringContainsString('recent nearby crime', $metadata['description']);
        $this->assertSame('https://publicdatawatch.com/crime-address', $metadata['canonical']);
        $this->assertSame('index, follow', $metadata['robots']);
    }
}
