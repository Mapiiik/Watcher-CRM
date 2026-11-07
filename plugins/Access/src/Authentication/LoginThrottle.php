<?php
declare(strict_types=1);

namespace Access\Authentication;

use Cake\Cache\Cache;

/**
 * Counts failed sign-ins and says when to stop accepting more.
 *
 * Failures are counted for an account from one address, and for the address whatever account it
 * tries. Nothing is counted for an account alone, so nobody can lock somebody else out by
 * guessing at their account from many places; that shows in the log instead. IPv6 addresses count
 * by their /64, which one subscriber usually holds.
 *
 * Asking for a link to set a new password counts as a failure of the address, so that nobody
 * sends mail without end from one place.
 *
 * Wrong one-time codes are counted apart from wrong passwords. A right password forgets the failures
 * of the password, and whoever has it would otherwise guess at the code without end.
 */
final class LoginThrottle
{
    /**
     * Where the counters are kept.
     */
    public const CACHE = 'login_throttle';

    /**
     * @param int $perAccountAndAddress Failures of one account from one address.
     * @param int $perAddress Failures from one address, any account.
     * @param int $minutes How long failures are remembered and how long a block lasts.
     * @param string $cacheConfig Where the counters are kept.
     */
    public function __construct(
        private int $perAccountAndAddress,
        private int $perAddress,
        private int $minutes,
        private string $cacheConfig = self::CACHE,
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
            SecurityPolicy::positive('login_attempts', 5),
            SecurityPolicy::positive('login_attempts_per_address', 20),
            SecurityPolicy::positive('login_block_minutes', 15),
        );
    }

    /**
     * Whether a sign-in to this account from this address is refused without looking at it.
     *
     * @param string $account The account as typed.
     * @param string $ip The address.
     * @return bool
     */
    public function blocked(string $account, string $ip): bool
    {
        foreach ($this->limits($account, $ip) as $key => $limit) {
            if ((int)Cache::pool($this->cacheConfig)->get($key, 0) >= $limit) {
                return true;
            }
        }

        return false;
    }

    /**
     * Counts a failed sign-in.
     *
     * @param string $account The account as typed.
     * @param string $ip The address.
     * @return void
     */
    public function failed(string $account, string $ip): void
    {
        $pool = Cache::pool($this->cacheConfig);
        foreach (array_keys($this->limits($account, $ip)) as $key) {
            $pool->set($key, (int)$pool->get($key, 0) + 1, $this->minutes * 60);
        }
    }

    /**
     * Whether a one-time code of this account from this address is refused without looking at it.
     *
     * @param string $account The account as typed.
     * @param string $ip The address.
     * @return bool
     */
    public function codeBlocked(string $account, string $ip): bool
    {
        $wrongCodes = (int)Cache::pool($this->cacheConfig)->get($this->codeKey($account, $ip), 0);

        return $wrongCodes >= $this->perAccountAndAddress || $this->blocked($account, $ip);
    }

    /**
     * Counts a wrong one-time code.
     *
     * @param string $account The account as typed.
     * @param string $ip The address.
     * @return void
     */
    public function codeFailed(string $account, string $ip): void
    {
        $pool = Cache::pool($this->cacheConfig);
        foreach ([$this->codeKey($account, $ip), $this->addressKey($ip)] as $key) {
            $pool->set($key, (int)$pool->get($key, 0) + 1, $this->minutes * 60);
        }
    }

    /**
     * Forgets the wrong one-time codes of this account from this address after a right one.
     *
     * @param string $account The account as typed.
     * @param string $ip The address.
     * @return void
     */
    public function codeAccepted(string $account, string $ip): void
    {
        Cache::pool($this->cacheConfig)->delete($this->codeKey($account, $ip));
    }

    /**
     * Whether more requests from this address are refused for now, whatever the account.
     *
     * @param string $ip The address.
     * @return bool
     */
    public function addressBlocked(string $ip): bool
    {
        return (int)Cache::pool($this->cacheConfig)->get($this->addressKey($ip), 0) >= $this->perAddress;
    }

    /**
     * Counts a request from this address that is limited like a failed sign-in.
     *
     * @param string $ip The address.
     * @return void
     */
    public function requested(string $ip): void
    {
        $pool = Cache::pool($this->cacheConfig);
        $key = $this->addressKey($ip);
        $pool->set($key, (int)$pool->get($key, 0) + 1, $this->minutes * 60);
    }

    /**
     * Forgets the failures of this account from this address after it signed in.
     *
     * @param string $account The account as typed.
     * @param string $ip The address.
     * @return void
     */
    public function succeeded(string $account, string $ip): void
    {
        Cache::pool($this->cacheConfig)->delete($this->key('pair', $this->pair($account, $ip)));
    }

    /**
     * The address a counter is kept for: IPv4 as it is, IPv6 by its /64.
     *
     * @param string $ip The address.
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
     * @param string $account The account as typed.
     * @param string $ip The address.
     * @return array<string, int>
     */
    private function limits(string $account, string $ip): array
    {
        return [
            $this->key('pair', $this->pair($account, $ip)) => $this->perAccountAndAddress,
            $this->addressKey($ip) => $this->perAddress,
        ];
    }

    /**
     * @param string $ip The address.
     * @return string
     */
    private function addressKey(string $ip): string
    {
        return $this->key('address', self::network($ip));
    }

    /**
     * @param string $account The account as typed.
     * @param string $ip The address.
     * @return string
     */
    private function codeKey(string $account, string $ip): string
    {
        return $this->key('code', $this->pair($account, $ip));
    }

    /**
     * @param string $account The account as typed.
     * @param string $ip The address.
     * @return string
     */
    private function pair(string $account, string $ip): string
    {
        return mb_strtolower(trim($account)) . '|' . self::network($ip);
    }

    /**
     * @param string $kind What is counted.
     * @param string $value For whom.
     * @return string
     */
    private function key(string $kind, string $value): string
    {
        return 'login_throttle_' . $kind . '_' . hash('sha256', $value);
    }
}
