<?php
declare(strict_types=1);

namespace Maps\Geocoder;

use Cake\Log\Log;
use Maps\Position;
use Override;
use Throwable;

/**
 * Several geocoders asked in turn, until one of them answers.
 *
 * They are good at different things: a national registry is official and exact but knows one
 * country, a map of the world knows everywhere and guesses. Asked in that order, an address at
 * home is answered precisely and one abroad is answered at all.
 *
 * One that fails is passed over rather than allowed to end the search, so a registry that is down
 * costs precision rather than the address search itself.
 */
final class FirstAnsweringGeocoder implements GeocoderInterface
{
    /**
     * @param list<\Maps\Geocoder\GeocoderInterface> $geocoders Asked in the order given.
     */
    public function __construct(private readonly array $geocoders)
    {
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function search(string $query, ?string $country = null, int $limit = 5): array
    {
        foreach ($this->geocoders as $geocoder) {
            $found = $this->attempt($geocoder, fn(): array => $geocoder->search($query, $country, $limit)) ?? [];

            if ($found !== []) {
                return $found;
            }
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function reverse(Position $position, ?string $country = null): ?Suggestion
    {
        foreach ($this->geocoders as $geocoder) {
            $found = $this->attempt($geocoder, fn(): ?Suggestion => $geocoder->reverse($position, $country));

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Asks one of them, and takes silence for an answer when it fails.
     *
     * @template T
     * @param \Maps\Geocoder\GeocoderInterface $geocoder Which one is being asked.
     * @param \Closure(): T $ask What to ask it.
     * @return T|null
     */
    private function attempt(GeocoderInterface $geocoder, callable $ask): mixed
    {
        try {
            return $ask();
        } catch (Throwable $e) {
            Log::warning(sprintf('%s could not answer (%s), asking the next one.', $geocoder::class, $e->getMessage()));

            return null;
        }
    }
}
