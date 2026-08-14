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
 *         'name'            => 'Asseco Central Europe, a.s.',  // what the register calls it
 *         'company'         => 'Asseco Central Europe, a.s.',  // null for a sole trader
 *         'title'           => null,                           // the four below are a sole
 *         'first_name'      => null,                           // trader's name taken apart, and
 *         'last_name'       => null,                           // null for a company
 *         'suffix'          => null,
 *         'date_of_birth'   => null,                           // a person's, never a company's
 *         'officers'        => [],                             // who sits in the statutory body
 *         'identity_number' => '27074358',
 *         'vat_number'      => 'CZ27074358',                   // null when the register has none
 *         'address'         => 'Budějovická 778/3a, Praha 4',  // only ever a label, never stored
 *         'addresses'       => [],                             // the seat and where it trades
 *     ]
 *
 * `officers` are the people sitting in a company's statutory body, each with the same name parts
 * and a `key` naming them. A register that cannot say leaves it empty, and so does a search - it
 * is only worth asking about an entry that was actually picked. Where exactly one sits, the name
 * fields carry them as well, there being nothing to choose.
 *
 * A sole trader trades under their own name, so a register writes a person where it otherwise
 * writes a company. Such an entry fills the name fields and leaves `company` empty, which is how
 * the CRM tells a person trading from a legal entity. `name` is what the register calls the
 * entry either way, and is what a suggestion list and a customer's detail show.
 *
 * `addresses` are the places the subject is registered at - its seat, marked as such, and wherever
 * else it does business. Each carries a `key` naming it in the national address registry, in the
 * "source|reference" form an address form is filled in from, so the address is read from the
 * address registry rather than copied out of the business register and arrives parsed and
 * standardised. Only a register that hands over such a reference can offer any, which leaves the
 * list empty for a seat abroad as readily as for a register that knows of no such thing.
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
