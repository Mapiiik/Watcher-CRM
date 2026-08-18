<?php
declare(strict_types=1);

namespace Maps\Test\TestCase;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Maps\MapProvider;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Maps\MapProvider Test Case
 */
#[UsesClass(MapProvider::class)]
class MapProviderTest extends TestCase
{
    /**
     * The provider is named in the configuration, whatever case it is written in.
     *
     * @return void
     */
    public function testTheConfiguredProviderIsTheOneUsed(): void
    {
        Configure::write('Maps.provider', 'OSM');

        $this->assertSame(MapProvider::Osm, MapProvider::current());
    }

    /**
     * A provider nobody knows must not leave the maps unrendered.
     *
     * @return void
     */
    public function testAnUnknownProviderFallsBackInsteadOfFailing(): void
    {
        Configure::write('Maps.provider', 'a provider that went away');

        $this->assertSame(MapProvider::Osm, MapProvider::current());
    }

    /**
     * Each provider draws out of a directory of its own, which is not its own name.
     *
     * @return void
     */
    public function testEachProviderNamesTheDirectoryItDrawsFrom(): void
    {
        $this->assertSame('leaflet', MapProvider::Osm->elementDirectory());
    }
}
