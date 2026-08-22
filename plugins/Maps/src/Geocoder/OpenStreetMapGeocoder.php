<?php
declare(strict_types=1);

namespace Maps\Geocoder;

use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Log\Log;
use Maps\Position;
use Override;

/**
 * Addresses out of OpenStreetMap.
 *
 * Two services, because they are good at different things: Photon is built for answering while
 * somebody types, Nominatim for saying what a pair of coordinates falls on. Both are free of
 * charge, both are self hostable, and the public ones ask for a User-Agent naming the caller.
 */
class OpenStreetMapGeocoder implements GeocoderInterface
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function search(string $query, ?string $country = null, int $limit = 5): array
    {
        $url = (string)Configure::read('Maps.photon.url');

        if ($url === '') {
            return [];
        }

        $parameters = ['q' => $query, 'limit' => $limit];

        // Photon answers in the local language unless told otherwise, and refuses outright a
        // language it does not carry - so it is asked for one only when somebody said which.
        $language = Configure::read('Maps.photon.language');
        if (is_string($language) && $language !== '') {
            $parameters['lang'] = $language;
        }

        $body = $this->ask(rtrim($url, '/') . '/api/', $parameters, 'Photon');

        if ($body === null) {
            return [];
        }

        $features = isset($body['features']) && is_array($body['features']) ? $body['features'] : [];
        $suggestions = [];

        foreach ($features as $feature) {
            $position = $this->positionOf(is_array($feature) ? $feature : []);

            if ($position === null) {
                continue;
            }

            $suggestions[] = new Suggestion(
                label: $this->describe((array)($feature['properties'] ?? [])),
                position: $position,
            );
        }

        return $suggestions;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function reverse(Position $position, ?string $country = null): ?Suggestion
    {
        $url = Configure::read('Maps.nominatim.url');
        $url = is_string($url) && $url !== '' ? rtrim($url, '/') : 'https://nominatim.openstreetmap.org';

        $body = $this->ask($url . '/reverse', [
            'lat' => $position->lat,
            'lon' => $position->lng,
            'format' => 'jsonv2',
            'accept-language' => (string)Configure::read('App.defaultLocale'),
        ], 'Nominatim');

        $label = $body['display_name'] ?? null;

        return is_string($label) && $label !== '' ? new Suggestion(label: $label, position: $position) : null;
    }

    /**
     * Asks one of the services and hands back what it said, or nothing when it would not say.
     *
     * The public servers want a User-Agent naming the installation, so every request carries one.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>|null
     */
    protected function ask(string $url, array $parameters, string $service): ?array
    {
        $userAgent = Configure::read('Maps.nominatim.userAgent');
        $referer = Configure::read('Maps.nominatim.referer');

        // Somebody is typing while this is asked, so a service that has stopped answering is given
        // up on rather than waited out for the half minute a client waits by default.
        $client = new Client([
            'headers' => array_filter([
                'User-Agent' => is_string($userAgent) && $userAgent !== '' ? $userAgent : null,
                'Referer' => is_string($referer) && $referer !== '' ? $referer : null,
            ]),
            'timeout' => 10,
        ]);

        $response = $client->get($url, $parameters);

        if (!$response->isOk()) {
            Log::warning(sprintf('%s answered with HTTP %d.', $service, $response->getStatusCode()));

            return null;
        }

        $body = $response->getJson();

        return is_array($body) ? $body : null;
    }

    /**
     * Reads the GeoJSON point of a feature, which names the coordinates the other way round.
     *
     * @param array<string, mixed> $feature
     */
    protected function positionOf(array $feature): ?Position
    {
        $coordinates = $feature['geometry']['coordinates'] ?? null;

        if (!is_array($coordinates) || !isset($coordinates[0], $coordinates[1])) {
            return null;
        }

        return new Position((float)$coordinates[1], (float)$coordinates[0]);
    }

    /**
     * Folds a Photon feature into a single address line, dropping the parts it repeats.
     *
     * @param array<string, mixed> $properties
     */
    protected function describe(array $properties): string
    {
        $street = implode(' ', array_filter([
            $properties['street'] ?? null,
            $properties['housenumber'] ?? null,
        ]));

        $place = implode(' ', array_filter([
            $properties['postcode'] ?? null,
            $properties['city'] ?? $properties['county'] ?? null,
        ]));

        return implode(', ', array_unique(array_filter([
            $properties['name'] ?? null,
            $street,
            $place,
            $properties['country'] ?? null,
        ])));
    }
}
