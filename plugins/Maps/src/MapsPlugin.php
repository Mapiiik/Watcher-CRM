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

        Configure::write('Maps', Hash::merge($defaults['Maps'], (array)Configure::read('Maps')));
    }
}
