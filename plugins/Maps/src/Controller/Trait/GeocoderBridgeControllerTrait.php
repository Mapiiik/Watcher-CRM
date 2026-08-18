<?php
declare(strict_types=1);

namespace Maps\Controller\Trait;

use Cake\Log\Log;
use Maps\Geocoder\GeocoderFactory;
use Maps\Geocoder\Suggestion;
use Throwable;

/**
 * The address search behind the maps.
 *
 * The browser asks this rather than the geocoder itself, because a geocoder of ours is reached with
 * a key that has no business leaving the server, and because what answers is then the application's
 * choice rather than something the script has to know.
 *
 * @psalm-require-extends \Cake\Controller\Controller
 */
trait GeocoderBridgeControllerTrait
{
    /**
     * Search method
     *
     * @return void Renders view
     */
    public function search(): void
    {
        $query = trim((string)$this->getRequest()->getQuery('q'));
        $country = $this->getRequest()->getQuery('country');
        $country = is_string($country) && $country !== '' ? $country : null;

        $suggestions = [];
        $geocoder = GeocoderFactory::create();

        if ($geocoder !== null && $query !== '') {
            try {
                $suggestions = array_map(
                    fn(Suggestion $suggestion): array => $suggestion->toArray(),
                    $geocoder->search($query, $country),
                );
            } catch (Throwable $e) {
                // A geocoder that is down must not take the form with it - the point can still be
                // picked by hand.
                Log::warning('Address search failed: ' . $e->getMessage());
            }
        }

        $this->set('suggestions', $suggestions);
        $this->viewBuilder()->setOption('serialize', ['suggestions']);
    }
}
