<?php
declare(strict_types=1);

namespace Maps\Test\TestCase;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Maps\Geocoder\GeocoderFactory;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;

/**
 * Maps\Geocoder\GeocoderFactory Test Case
 */
#[UsesClass(GeocoderFactory::class)]
class GeocoderFactoryTest extends TestCase
{
    /**
     * Naming no geocoder is a fair answer - the maps then offer no address search.
     *
     * @return void
     */
    public function testNamingNoGeocoderIsNotAnError(): void
    {
        Configure::write('Maps.geocoder', null);

        $this->assertNull(GeocoderFactory::create());
    }

    /**
     * Naming something that cannot geocode is worth saying out loud rather than ignoring.
     *
     * @return void
     */
    public function testNamingSomethingThatIsNotAGeocoderSaysSo(): void
    {
        Configure::write('Maps.geocoder', self::class);

        $this->expectException(RuntimeException::class);

        GeocoderFactory::create();
    }
}
