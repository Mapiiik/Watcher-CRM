<?php
declare(strict_types=1);

namespace Maps\Geocoder;

use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Log\Log;
use Maps\Position;
use Override;

/**
 * Addresses out of a national address registry.
 *
 * Official data rather than a crowd sourced map, which is the point of using it: a point picked
 * here lands where the registry says the address is. Each registry covers one country, so a search
 * without one to look in has nothing to answer.
 *
 * This asks the registry only what a map needs. An application matching its own addresses against
 * the same registry has its own, fuller way of talking to it.
 */
class AddressRegistryGeocoder implements GeocoderInterface
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function search(string $query, ?string $country = null, int $limit = 5): array
    {
        if ($country === null || $country === '') {
            return [];
        }

        $matches = $this->ask('v1/search', [
            'country' => strtolower($country),
            'q' => $query,
            'limit' => $limit,
        ]);

        $suggestions = [];

        foreach ($matches as $match) {
            $suggestion = $this->suggestionFrom(is_array($match) ? $match : []);

            if ($suggestion !== null) {
                $suggestions[] = $suggestion;
            }
        }

        return $suggestions;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function reverse(Position $position, ?string $country = null): ?Suggestion
    {
        if ($country === null || $country === '') {
            return null;
        }

        $matches = $this->ask('v1/reverse', [
            'country' => strtolower($country),
            'lat' => $position->lat,
            'lon' => $position->lng,
            'limit' => 1,
        ]);

        $match = $matches[0] ?? null;

        return is_array($match) ? $this->suggestionFrom($match) : null;
    }

    /**
     * Asks the registry and hands back the list it answered with, empty when it would not answer.
     *
     * @param array<string, mixed> $parameters
     * @return list<mixed>
     */
    protected function ask(string $path, array $parameters): array
    {
        $url = Configure::read('Maps.addressRegistry.url');

        if (!is_string($url) || $url === '') {
            return [];
        }

        $key = Configure::read('Maps.addressRegistry.key');

        $client = new Client(['headers' => array_filter([
            'Accept' => 'application/json',
            'X-API-Key' => is_string($key) && $key !== '' ? $key : null,
        ])]);

        $response = $client->get(rtrim($url, '/') . '/' . $path, $parameters);

        if (!$response->isOk()) {
            Log::warning(sprintf('The address registry answered with HTTP %d.', $response->getStatusCode()));

            return [];
        }

        $body = $response->getJson();

        return is_array($body) ? array_values($body) : [];
    }

    /**
     * Reads one match of the registry's into a suggestion, dropping the ones it cannot place.
     *
     * @param array<string, mixed> $match
     */
    protected function suggestionFrom(array $match): ?Suggestion
    {
        $coordinates = $match['geometry']['coordinates'] ?? null;
        $label = $match['formatted_address'] ?? null;

        if (!is_array($coordinates) || !isset($coordinates[0], $coordinates[1])) {
            return null;
        }

        if (!is_string($label) || $label === '') {
            return null;
        }

        $source = $match['source'] ?? null;
        $registryReference = $match['registry_ref'] ?? null;

        // The GeoJSON point names the coordinates the other way round.
        return new Suggestion(
            label: $label,
            position: new Position((float)$coordinates[1], (float)$coordinates[0]),
            reference: is_string($source) && is_string($registryReference)
                ? $source . '|' . $registryReference
                : null,
        );
    }
}
