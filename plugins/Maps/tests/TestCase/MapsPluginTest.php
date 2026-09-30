<?php
declare(strict_types=1);

namespace Maps\Test\TestCase;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Maps\MapsPlugin;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Maps\MapsPlugin Test Case
 */
#[UsesClass(MapsPlugin::class)]
class MapsPluginTest extends TestCase
{
    /**
     * @inheritDoc
     */
    #[Override]
    protected function tearDown(): void
    {
        $this->clearPlugins();

        parent::tearDown();
    }

    /**
     * An application that says nothing about the layers gets the plugin's ones.
     *
     * @return void
     */
    public function testTheLayersDefaultToThePlugins(): void
    {
        Configure::write('Maps', ['provider' => 'osm']);

        $this->loadPlugins(['Maps']);

        $this->assertSame(['osm', 'cuzk', 'esri'], array_keys(Configure::read('Maps.baseLayers')));
    }

    /**
     * A layer the application names is taken whole, so nothing of the plugin's one is left in it.
     *
     * @return void
     */
    public function testALayerIsReplacedWhole(): void
    {
        Configure::write('Maps.baseLayers.cuzk', [
            'name' => 'Orthophoto',
            'type' => 'xyz',
            'url' => 'https://maps.example.com/tiles/cz-orthophoto/webmercator/{z}/{x}/{y}.jpeg',
            'options' => ['maxZoom' => 20],
        ]);

        $this->loadPlugins(['Maps']);

        $this->assertSame(
            ['name', 'type', 'url', 'options'],
            array_keys(Configure::read('Maps.baseLayers.cuzk')),
        );
        $this->assertSame(['maxZoom' => 20], Configure::read('Maps.baseLayers.cuzk.options'));
    }

    /**
     * The application's layers come first and in its order, the plugin's others after them, and a
     * layer set to false is left out.
     *
     * @return void
     */
    public function testTheApplicationOrdersAndRemovesLayers(): void
    {
        $layer = ['name' => 'Own', 'type' => 'xyz', 'url' => 'https://maps.example.com/{z}/{x}/{y}.png'];
        Configure::write('Maps.baseLayers', [
            'own' => $layer,
            'cuzk' => $layer,
            'esri' => false,
        ]);

        $this->loadPlugins(['Maps']);

        $this->assertSame(['own', 'cuzk', 'osm'], array_keys(Configure::read('Maps.baseLayers')));
    }
}
