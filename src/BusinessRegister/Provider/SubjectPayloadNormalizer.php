<?php
declare(strict_types=1);

namespace App\BusinessRegister\Provider;

use App\BusinessRegister\Dto\Officer;
use App\BusinessRegister\Dto\RegisteredAddress;
use App\BusinessRegister\Dto\Subject;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;

/**
 * The shape the registers share, turned into entries.
 *
 * Each register reads its own answer into that shape and this reads the shape into a subject, so a
 * register's own vocabulary stops at its own class. What is not forgiven is an entry with no
 * reference: that is what a register is asked with to get the entry again, and without it the
 * entry cannot be picked from a list at all.
 */
final class SubjectPayloadNormalizer
{
    /**
     * The entries of a listing.
     *
     * @param array<mixed> $entries The listing in the shape the registers share.
     * @return \Cake\Collection\CollectionInterface<int, \App\BusinessRegister\Dto\Subject>
     */
    public static function subjects(array $entries): CollectionInterface
    {
        /** @var array<int, \App\BusinessRegister\Dto\Subject> $subjects */
        $subjects = [];

        foreach ($entries as $entry) {
            $subject = is_array($entry) ? self::subject($entry) : null;

            if ($subject !== null) {
                $subjects[] = $subject;
            }
        }

        return new Collection($subjects);
    }

    /**
     * One entry.
     *
     * @param array<mixed> $entry The entry in the shape the registers share.
     * @return \App\BusinessRegister\Dto\Subject|null
     */
    public static function subject(array $entry): ?Subject
    {
        $reference = self::stringOrNull($entry['reference'] ?? null);

        if ($reference === null) {
            return null;
        }

        /** @var array<string, mixed> $entry */
        return new Subject(
            reference: $reference,
            name: self::stringOrNull($entry['name'] ?? null),
            company: self::stringOrNull($entry['company'] ?? null),
            title: self::stringOrNull($entry['title'] ?? null),
            firstName: self::stringOrNull($entry['first_name'] ?? null),
            lastName: self::stringOrNull($entry['last_name'] ?? null),
            suffix: self::stringOrNull($entry['suffix'] ?? null),
            dateOfBirth: self::stringOrNull($entry['date_of_birth'] ?? null),
            officers: self::officers($entry['officers'] ?? null),
            identityNumber: self::stringOrNull($entry['identity_number'] ?? null),
            vatNumber: self::stringOrNull($entry['vat_number'] ?? null),
            address: self::stringOrNull($entry['address'] ?? null),
            addresses: self::addresses($entry['addresses'] ?? null),
            raw: $entry,
        );
    }

    /**
     * The people sitting in the statutory body.
     *
     * @param mixed $entries The people as the register named them.
     * @return list<\App\BusinessRegister\Dto\Officer>
     */
    private static function officers(mixed $entries): array
    {
        $officers = [];

        foreach (is_array($entries) ? $entries : [] as $entry) {
            $key = is_array($entry) ? self::stringOrNull($entry['key'] ?? null) : null;

            if ($key === null) {
                continue;
            }

            $officers[] = new Officer(
                key: $key,
                title: self::stringOrNull($entry['title'] ?? null),
                firstName: self::stringOrNull($entry['first_name'] ?? null),
                lastName: self::stringOrNull($entry['last_name'] ?? null),
                suffix: self::stringOrNull($entry['suffix'] ?? null),
                dateOfBirth: self::stringOrNull($entry['date_of_birth'] ?? null),
            );
        }

        return $officers;
    }

    /**
     * The places the subject is registered at.
     *
     * @param mixed $entries The places as the register named them.
     * @return list<\App\BusinessRegister\Dto\RegisteredAddress>
     */
    private static function addresses(mixed $entries): array
    {
        $addresses = [];

        foreach (is_array($entries) ? $entries : [] as $entry) {
            $key = is_array($entry) ? self::stringOrNull($entry['key'] ?? null) : null;

            if ($key === null) {
                continue;
            }

            $addresses[] = new RegisteredAddress(
                key: $key,
                label: self::stringOrNull($entry['label'] ?? null),
                seat: (bool)($entry['seat'] ?? false),
            );
        }

        return $addresses;
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
