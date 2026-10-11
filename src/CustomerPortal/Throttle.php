<?php
declare(strict_types=1);

namespace App\CustomerPortal;

use Cake\Cache\Cache;
use Settings\Utility\Settings;

/**
 * Counts failed customer portal sign-ins and says when to stop accepting more.
 *
 * Failures are counted for the login from one address, for the address whatever login it tries,
 * and for the login from anywhere. The last limit is the highest, so a guesser spread over many
 * addresses is slowed down too, while a customer is not locked out by somebody else's attempts
 * from one place. IPv6 addresses count by their /64, which one subscriber usually holds.
 */
final class Throttle
{
    public const SETTINGS_PATH = 'core.customer_portal';

    /**
     * @param int $perLoginAndAddress Failures of one login from one address.
     * @param int $perAddress Failures from one address, any login.
     * @param int $perLogin Failures of one login, any address.
     * @param int $minutes How long failures are remembered and how long a block lasts.
     * @param string $cacheConfig Where the counters are kept.
     */
    public function __construct(
        private int $perLoginAndAddress,
        private int $perAddress,
        private int $perLogin,
        private int $minutes,
        private string $cacheConfig = 'default',
    ) {
    }

    /**
     * The limits the settings ask for.
     *
     * @return self
     */
    public static function fromSettings(): self
    {
        return new self(
            self::setting('login_attempts', 5),
            self::setting('login_attempts_per_address', 20),
            self::setting('login_attempts_per_login', 30),
            self::setting('login_block_minutes', 15),
        );
    }

    /**
     * A limit from the settings. Nought or less falls back to the default, as it would refuse
     * every sign-in.
     *
     * @param string $key The setting.
     * @param int $default Its default.
     * @return int
     */
    private static function setting(string $key, int $default): int
    {
        $value = (int)Settings::get(self::SETTINGS_PATH . '.' . $key, $default);

        return $value > 0 ? $value : $default;
    }

    /**
     * Whether a sign-in of this login from this address is refused without looking at the password.
     *
     * @param string $login The login as typed.
     * @param string $ip The customer's address.
     * @return bool
     */
    public function blocked(string $login, string $ip): bool
    {
        foreach ($this->limits($login, $ip) as $key => $limit) {
            if ((int)Cache::pool($this->cacheConfig)->get($key, 0) >= $limit) {
                return true;
            }
        }

        return false;
    }

    /**
     * Counts a failed sign-in.
     *
     * @param string $login The login as typed.
     * @param string $ip The customer's address.
     * @return void
     */
    public function failed(string $login, string $ip): void
    {
        $pool = Cache::pool($this->cacheConfig);
        foreach (array_keys($this->limits($login, $ip)) as $key) {
            $pool->set($key, (int)$pool->get($key, 0) + 1, $this->minutes * 60);
        }
    }

    /**
     * Forgets the failures of this login from this address after it signed in.
     *
     * @param string $login The login as typed.
     * @param string $ip The customer's address.
     * @return void
     */
    public function succeeded(string $login, string $ip): void
    {
        $pair = $this->normalizeLogin($login) . '|' . self::network($ip);
        Cache::pool($this->cacheConfig)->delete($this->key('pair', $pair));
    }

    /**
     * The address a counter is kept for: IPv4 as it is, IPv6 by its /64.
     *
     * @param string $ip The customer's address.
     * @return string
     */
    public static function network(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            return $ip;
        }

        return inet_ntop(substr((string)inet_pton($ip), 0, 8) . str_repeat("\0", 8)) . '/64';
    }

    /**
     * The counters a sign-in touches and the limit of each.
     *
     * @param string $login The login as typed.
     * @param string $ip The customer's address.
     * @return array<string, int>
     */
    private function limits(string $login, string $ip): array
    {
        $login = $this->normalizeLogin($login);
        $network = self::network($ip);

        return [
            $this->key('pair', $login . '|' . $network) => $this->perLoginAndAddress,
            $this->key('address', $network) => $this->perAddress,
            $this->key('login', $login) => $this->perLogin,
        ];
    }

    /**
     * @param string $login The login as typed.
     * @return string
     */
    private function normalizeLogin(string $login): string
    {
        return mb_strtolower(trim($login));
    }

    /**
     * @param string $kind What is counted.
     * @param string $value For whom.
     * @return string
     */
    private function key(string $kind, string $value): string
    {
        return 'customer_portal_throttle_' . $kind . '_' . hash('sha256', $value);
    }
}
