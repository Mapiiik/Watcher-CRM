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
        $scored = [];

        foreach ($this->countries($country) as $inCountry) {
            $matches = $this->ask('v1/search', [
                'country' => $inCountry,
                'q' => $query,
                'limit' => $limit,
            ]);

            foreach ($matches as $match) {
                $match = is_array($match) ? $match : [];
                $suggestion = $this->suggestionFrom($match);

                if ($suggestion !== null) {
                    $scored[] = [is_numeric($match['score'] ?? null) ? (float)$match['score'] : 0.0, $suggestion];
                }
            }
        }

        // Each registry scores its own matches, so what came from two of them is put back in one
        // order rather than one country's worth after the other's.
        usort($scored, fn(array $a, array $b): int => $b[0] <=> $a[0]);

        return array_slice(array_column($scored, 1), 0, $limit);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function reverse(Position $position, ?string $country = null): ?Suggestion
    {
        // A point lies in one country, so the registries are asked in turn until one places it.
        foreach ($this->countries($country) as $inCountry) {
            $matches = $this->ask('v1/reverse', [
                'country' => $inCountry,
                'lat' => $position->lat,
                'lon' => $position->lng,
                'limit' => 1,
            ]);

            $match = $matches[0] ?? null;
            $suggestion = is_array($match) ? $this->suggestionFrom($match) : null;

            if ($suggestion !== null) {
                return $suggestion;
            }
        }

        return null;
    }

    /**
     * Which registries to ask.
     *
     * A caller that knows the country says which, and then that is the only one asked. One that
     * does not - a map of our own masts, say - has every country the installation works in asked,
     * because a registry knows one country and an operator may work in several.
     *
     * @return list<string>
     */
    protected function countries(?string $country): array
    {
        if ($country !== null && $country !== '') {
            return [strtolower($country)];
        }

        $configured = Configure::read('Maps.addressRegistry.defaultCountries');
        $configured = is_string($configured) ? explode(',', $configured) : $configured;

        if (!is_array($configured)) {
            return [];
        }

        $countries = [];

        foreach ($configured as $one) {
            $one = is_string($one) ? strtolower(trim($one)) : '';

            if ($one !== '') {
                $countries[] = $one;
            }
        }

        return array_values(array_unique($countries));
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
            // Named as the geocoder but never given a registry, which is worth saying out loud -
            // the search would otherwise just answer nothing, over and over.
            Log::warning('`Maps.geocoder` names the address registry, but `Maps.addressRegistry.url` is empty.');

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
