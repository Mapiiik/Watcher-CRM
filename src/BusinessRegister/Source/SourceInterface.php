<?php
declare(strict_types=1);

namespace App\BusinessRegister\Source;

/**
 * A register a company can be looked up in.
 *
 * Implementations only read, and each hands back the same shape whatever it asks upstream,
 * so nothing above them has to know which register an entry came from:
 *
 *     [
 *         'reference'       => '27074358',                     // what byReference() is asked with
 *         'company'         => 'Asseco Central Europe, a.s.',
 *         'identity_number' => '27074358',
 *         'vat_number'      => 'CZ27074358',                   // null when the register has none
 *         'address'         => 'Budějovická 778/3a, Praha 4',  // only ever a label, never stored
 *     ]
 */
interface SourceInterface
{
    /**
     * The name the register is known by, in a route and in its settings alike.
     *
     * @return string
     */
    public function key(): string;

    /**
     * What the register is called in the form, country first so a list of them reads as a list
     * of countries.
     *
     * @return string
     */
    public function label(): string;

    /**
     * Whether the register is turned on and has everything it needs to answer.
     *
     * A register that would only fail is not offered at all, rather than offered and then
     * apologised for.
     *
     * @return bool
     */
    public function isConfigured(): bool;

    /**
     * Entries matching what was typed, at most $limit of them.
     *
     * A register that cannot be searched the way it was asked answers with nothing rather than
     * with an error - VIES knows numbers and not names, and being handed a name is not a fault.
     *
     * @param string $query What was typed into the search field.
     * @param int $limit How many entries to ask for.
     * @return list<array<string, mixed>>
     * @throws \RuntimeException When the register cannot be reached or refuses the request.
     */
    public function search(string $query, int $limit = 25): array;

    /**
     * A single entry, or null when the register does not hold it.
     *
     * @param string $reference The reference a search result carried.
     * @return array<string, mixed>|null
     * @throws \RuntimeException When the register cannot be reached or refuses the request.
     */
    public function byReference(string $reference): ?array;
}
