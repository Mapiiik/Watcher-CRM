<?php
declare(strict_types=1);

namespace App\Addresses\Dto;

use Cake\Collection\CollectionInterface;

/**
 * What came of asking a registry about a whole set of addresses at once.
 *
 * The matches arrive in no particular order, so they are found again by their key rather than by
 * their position. What the registry has never heard of is listed apart instead of being left out,
 * which is the difference between an address that is gone from the registry and one this
 * application never asked about.
 */
final readonly class Batch
{
    /**
     * @param \Cake\Collection\CollectionInterface<int, \App\Addresses\Dto\Address> $matches What it knew.
     * @param list<array<string, mixed>> $notFound What it had never heard of, as it was asked.
     * @param array<string, mixed> $raw The answer as it arrived.
     */
    public function __construct(
        public CollectionInterface $matches,
        public array $notFound = [],
        public array $raw = [],
    ) {
    }

    /**
     * The matches under the key this application stores them by.
     *
     * @return array<string, \App\Addresses\Dto\Address>
     */
    public function byKey(): array
    {
        $byKey = [];

        foreach ($this->matches as $address) {
            $byKey[$address->key()] = $address;
        }

        return $byKey;
    }
}
