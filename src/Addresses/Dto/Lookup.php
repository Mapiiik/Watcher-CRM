<?php
declare(strict_types=1);

namespace App\Addresses\Dto;

use Cake\Collection\CollectionInterface;

/**
 * What came of asking a registry which address a written one means.
 *
 * The Czech registry works down a ladder of ever looser matches and says which rung answered, so a
 * caller can tell an address that matched exactly from one that matched only once the house number
 * was dropped. Croatia matches once or not at all and leaves the rung empty.
 *
 * More than one match is the answer being useless rather than absent: the registry knows several
 * addresses the line could mean and will not choose. That is what {@see $ambiguous} says, and it is
 * why a caller counts the matches instead of taking the first.
 */
final readonly class Lookup
{
    /**
     * @param \Cake\Collection\CollectionInterface<int, \App\Addresses\Dto\Address> $matches Addresses it could mean.
     * @param bool $ambiguous Whether the registry found several and would not choose.
     * @param int|null $fallbackStep Which rung of the ladder answered, where the registry has one.
     * @param array<string, mixed> $raw The answer as it arrived.
     */
    public function __construct(
        public CollectionInterface $matches,
        public bool $ambiguous = false,
        public ?int $fallbackStep = null,
        public array $raw = [],
    ) {
    }

    /**
     * The one address this can only mean, or nothing where the registry found none or several.
     *
     * @return \App\Addresses\Dto\Address|null
     */
    public function only(): ?Address
    {
        return $this->matches->count() === 1 ? $this->matches->first() : null;
    }
}
