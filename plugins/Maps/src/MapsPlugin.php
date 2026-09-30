<?php
declare(strict_types=1);

namespace Maps;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Core\PluginApplicationInterface;
use Cake\Utility\Hash;
use Override;

/**
 * Plugin for Maps
 */
class MapsPlugin extends BasePlugin
{
    /**
     * Fills in the settings the application did not state itself.
     *
     * The base layers and the map options are the same wherever the maps are drawn, so the plugin
     * carries them and an application only says what is its own - the provider, the geocoder, and
     * the addresses of the services it talks to.
     *
     * @param \Cake\Core\PluginApplicationInterface $app The host application
     * @return void
     */
    #[Override]
    public function bootstrap(PluginApplicationInterface $app): void
    {
        /** @var array<string, array<string, mixed>> $defaults */
        $defaults = include Plugin::configPath('Maps') . 'maps.php';
        $own = (array)Configure::read('Maps');

        $maps = Hash::merge($defaults['Maps'], $own);
        $maps['baseLayers'] = $this->baseLayers(
            (array)$defaults['Maps']['baseLayers'],
            (array)($own['baseLayers'] ?? []),
        );

        Configure::write('Maps', $maps);
    }

    /**
     * The layers to offer: the application's own ones first and whole, then the plugin's others.
     *
     * A layer is replaced rather than merged, as a layer half from one server and half from
     * another would be no layer at all. One set to anything but an array is left out.
     *
     * @param array<array-key, mixed> $defaults The plugin's layers
     * @param array<array-key, mixed> $own The application's layers
     * @return array<array-key, array<string, mixed>>
     */
    protected function baseLayers(array $defaults, array $own): array
    {
        $layers = array_merge(array_fill_keys(array_keys($own), null), $defaults, $own);

        return array_filter($layers, is_array(...));
    }
}
