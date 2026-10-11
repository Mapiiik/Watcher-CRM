<?php
declare(strict_types=1);

namespace App\CustomerPortal;

use Cake\Cache\Cache;
use Cake\Chronos\Chronos;

/**
 * How many addresses a service looks up in a minute.
 *
 * Recognising a customer by their address is a handful of lookups a minute. A service going
 * through whole ranges asks for many more, so it is refused over the limit, and the log shows it.
 */
final class Lookups
{
    /**
     * @param int $perMinute Lookups of one caller in one minute.
     * @param string $cacheConfig Cache configuration holding the counters.
     */
    public function __construct(
        private int $perMinute,
        private string $cacheConfig = 'default',
    ) {
    }

    /**
     * The limit the settings set.
     *
     * @return self
     */
    public static function fromSettings(): self
    {
        return new self(Throttle::setting('identify_per_minute', 30));
    }

    /**
     * Counts a lookup of a caller.
     *
     * @param string $caller Who asks.
     * @return bool Whether it is within the limit of this minute.
     */
    public function allow(string $caller): bool
    {
        $minute = intdiv(Chronos::now()->getTimestamp(), 60);
        $key = 'customer_portal_lookups_' . hash('sha256', $caller) . '_' . $minute;
        $pool = Cache::pool($this->cacheConfig);
        $count = (int)$pool->get($key, 0) + 1;
        $pool->set($key, $count, 120);

        return $count <= $this->perMinute;
    }

    /**
     * The limit.
     *
     * @return int
     */
    public function perMinute(): int
    {
        return $this->perMinute;
    }
}
