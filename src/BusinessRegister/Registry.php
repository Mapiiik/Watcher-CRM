<?php
declare(strict_types=1);

namespace App\BusinessRegister;

use App\BusinessRegister\Source\SourceInterface;
use App\BusinessRegister\Source\VatNumberCheckInterface;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Log\Log;
use RuntimeException;

/**
 * The registers this installation can look a company up in.
 *
 * Which classes exist is settled in the configuration; which of them are offered is settled in
 * the settings, by each register saying whether it has what it needs. A register that would only
 * fail never reaches the form.
 */
class Registry
{
    /**
     * The registers that can answer right now, keyed by name and in the order configured.
     *
     * @return array<string, \App\BusinessRegister\Source\SourceInterface>
     */
    public static function sources(): array
    {
        $sources = [];

        foreach ((array)Configure::read('BusinessRegister.sources', []) as $key => $className) {
            if (!is_string($className) || !is_subclass_of($className, SourceInterface::class)) {
                continue;
            }

            $source = new $className();
            if ($source->isConfigured()) {
                $sources[(string)$key] = $source;
            }
        }

        return $sources;
    }

    /**
     * One register by name, null when it does not exist or cannot answer.
     *
     * @param string $key The name the register is known by.
     * @return \App\BusinessRegister\Source\SourceInterface|null
     */
    public static function get(string $key): ?SourceInterface
    {
        return self::sources()[$key] ?? null;
    }

    /**
     * The registers as a form offers them - name to label.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_map(
            fn(SourceInterface $source): string => $source->label(),
            self::sources(),
        );
    }

    /**
     * The register to offer first: the one asked for while it can answer, otherwise whichever
     * can. Null when none can.
     *
     * Which register is preferred is the caller's to know - it is a setting, and reading settings
     * here would tie choosing between registers to a service the choice does not need.
     *
     * @param string|null $preferred The register to offer if it can answer.
     * @return string|null
     */
    public static function defaultKey(?string $preferred = null): ?string
    {
        $sources = self::sources();
        if ($sources === []) {
            return null;
        }

        if ($preferred !== null && isset($sources[$preferred])) {
            return $preferred;
        }

        return array_key_first($sources);
    }

    /**
     * Entries matching what was typed. Not cached - a suggestion list is read once and a stale
     * one would be worse than a second request.
     *
     * @param string $key The name the register is known by.
     * @param string $query What was typed into the search field.
     * @param int $limit How many entries to ask for.
     * @return list<array<string, mixed>>
     */
    public static function search(string $key, string $query, int $limit = 25): array
    {
        return self::get($key)?->search($query, $limit) ?? [];
    }

    /**
     * A single entry, kept for as long as the `business_register` cache says. A company's name
     * and numbers are what the register was asked for, and they do not change by the hour.
     *
     * @param string $key The name the register is known by.
     * @param string $reference The reference a search result carried.
     * @return array<string, mixed>|null
     */
    public static function byReferenceFromCache(string $key, string $reference): ?array
    {
        // A register not holding the reference is an answer too, and is kept as one - `false`
        // rather than null, which a cache cannot tell from having nothing.
        $cacheKey = sprintf('subject_%s_%s', $key, md5($reference));
        $cached = Cache::read($cacheKey, 'business_register');
        if (is_array($cached)) {
            return $cached;
        }
        if ($cached === false) {
            return null;
        }

        $subject = self::get($key)?->byReference($reference);
        Cache::write($cacheKey, $subject ?? false, 'business_register');

        return $subject;
    }

    /**
     * What the registers say about the identification number, null when none of them could be
     * asked - none is configured, or the one that covers the number could not be reached.
     *
     * The registers are asked in turn and the first that holds the number answers. Which one
     * that is follows from the number itself - a register hands back nothing for a number that
     * is not its country's - so there is no country logic here to keep in step with them.
     *
     * @param string|null $identityNumber The number as it is stored.
     * @return \App\BusinessRegister\IdentityNumberCheck|null
     */
    public static function identityNumberCheck(?string $identityNumber): ?IdentityNumberCheck
    {
        $identityNumber = trim((string)$identityNumber);
        if ($identityNumber === '') {
            return null;
        }

        $keys = array_keys(self::sources());
        if ($keys === []) {
            return null;
        }

        foreach ($keys as $key) {
            try {
                $subject = self::byReferenceFromCache($key, $identityNumber);
            } catch (RuntimeException $e) {
                Log::error('Could not look the identification number up: ' . $e->getMessage());

                return null;
            }

            $company = trim((string)($subject['name'] ?? ''));
            if ($company !== '') {
                $addressKey = trim((string)($subject['address_key'] ?? ''));

                return new IdentityNumberCheck(
                    IdentityNumberStatus::Found,
                    $company,
                    $addressKey !== '' ? $addressKey : null,
                );
            }
        }

        // every register was asked and none holds it, which is an answer of its own
        return new IdentityNumberCheck(IdentityNumberStatus::NotFound);
    }

    /**
     * What the registers say about the VAT number, null when none of them can say - none that
     * checks VAT numbers is configured, none covers the country the number carries, or the one
     * that does could not be reached.
     *
     * The registers are asked in the order they are configured, and one that cannot be asked
     * about this particular number is passed over rather than taken for an answer. That is what
     * lets a national register answer for its own country - it can tell a company that never
     * registered for VAT from a number nobody holds - while VIES answers for the rest.
     *
     * A register that could not be reached is not remembered as an answer, so an outage lasts as
     * long as the outage rather than as long as the cache.
     *
     * @param string|null $vatNumber The number as it is stored, prefix included.
     * @return \App\BusinessRegister\VatNumberCheck|null
     */
    public static function vatNumberCheck(?string $vatNumber): ?VatNumberCheck
    {
        $vatNumber = trim((string)$vatNumber);
        if ($vatNumber === '') {
            return null;
        }

        $cacheKey = 'vat_' . md5($vatNumber);
        $cached = Cache::read($cacheKey, 'business_register');
        if (is_array($cached)) {
            $status = VatNumberStatus::tryFrom((string)($cached['status'] ?? ''));
            if ($status !== null) {
                return new VatNumberCheck($status, $cached['company'] ?? null);
            }
        }

        foreach (self::sources() as $source) {
            if (!$source instanceof VatNumberCheckInterface) {
                continue;
            }

            try {
                $check = $source->vatNumberCheck($vatNumber);
            } catch (RuntimeException $e) {
                Log::error('Could not check the VAT number: ' . $e->getMessage());

                return null;
            }

            // the register has nothing to say about this number - the next one may
            if ($check === null) {
                continue;
            }

            Cache::write(
                $cacheKey,
                ['status' => $check->status->value, 'company' => $check->company],
                'business_register',
            );

            return $check;
        }

        return null;
    }
}
