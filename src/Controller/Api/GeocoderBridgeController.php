<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Maps\Controller\Trait\GeocoderBridgeControllerTrait;

/**
 * GeocoderBridge Controller
 *
 * Bridges the maps to whichever geocoder `Maps.geocoder` names, so the browser never talks to it
 * directly and the key it is reached with stays here.
 */
class GeocoderBridgeController extends AppController
{
    use GeocoderBridgeControllerTrait;
}
