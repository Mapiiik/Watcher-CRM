<?php
declare(strict_types=1);

namespace App\BusinessRegister\Source;

use App\Http\Answer;

/**
 * A register a company can be looked up in.
 *
 * Implementations only read, and each hands back a {@see \App\BusinessRegister\Dto\Subject}
 * whatever it asks upstream, so nothing above them has to know which register an entry came from.
 *
 * Every reading comes back as an {@see \App\Http\Answer}, so a register being down is told apart
 * from its holding nothing - the first is worth saying out loud, the second is the answer. The
 * caller says what a failure is worth: a search shows the operator what went wrong, a check run
 * over every register passes the broken one over and asks the next.
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
     * @return \App\Http\Answer Answering with {@see \App\BusinessRegister\Dto\Subject} entries.
     */
    public function search(string $query, int $limit = 25): Answer;

    /**
     * A single entry, or null when the register does not hold it.
     *
     * @param string $reference The reference a search result carried.
     * @return \App\Http\Answer Answering with a {@see \App\BusinessRegister\Dto\Subject} or null.
     */
    public function byReference(string $reference): Answer;
}
