<?php
declare(strict_types=1);

namespace App\SledovaniTV\Provider;

use App\SledovaniTV\Dto\TvUser;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;

/**
 * What the television service answers with, turned into viewers.
 *
 * A viewer with no number of its own cannot be suspended or put back, so it is passed over rather
 * than kept half-read. Everything else is forgiving: the service is not ours and writes its flags
 * as whichever of nought, one, true or false it feels like.
 */
final class TvUserPayloadNormalizer
{
    /**
     * The viewers of a listing.
     *
     * @param array<mixed> $entries The listing as it arrived.
     * @return \Cake\Collection\CollectionInterface<int, \App\SledovaniTV\Dto\TvUser>
     */
    public static function users(array $entries): CollectionInterface
    {
        /** @var array<int, \App\SledovaniTV\Dto\TvUser> $users */
        $users = [];

        foreach ($entries as $entry) {
            $user = is_array($entry) ? self::user($entry) : null;

            if ($user !== null) {
                $users[] = $user;
            }
        }

        return new Collection($users);
    }

    /**
     * One viewer.
     *
     * @param array<mixed> $entry The viewer as they arrived.
     * @return \App\SledovaniTV\Dto\TvUser|null
     */
    public static function user(array $entry): ?TvUser
    {
        $id = self::stringOrNull($entry['id'] ?? null);

        if ($id === null) {
            return null;
        }

        /** @var array<string, mixed> $entry */
        return new TvUser(
            id: $id,
            partnerNumber: self::stringOrNull($entry['partnerid'] ?? null),
            active: self::flag($entry['active'] ?? null),
            suspended: self::flag($entry['suspended'] ?? null),
            raw: $entry,
        );
    }

    /**
     * A flag as the service writes it, which is any of nought, one, true or false.
     *
     * @param mixed $value Value to read.
     * @return bool
     */
    private static function flag(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param mixed $value Value to read.
     * @return string|null
     */
    private static function stringOrNull(mixed $value): ?string
    {
        return is_scalar($value) && trim((string)$value) !== '' ? trim((string)$value) : null;
    }
}
