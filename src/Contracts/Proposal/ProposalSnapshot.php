<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Model\Entity\Billing;
use InvalidArgumentException;

/**
 * How everything stood when a proposal was drawn up.
 *
 * Documents print from this rather than from the live records, so the same paper printed twice is
 * the same paper even after the price list has moved. It is the live state at the moment it was
 * taken, not a projection - what the proposal asks for is applied to it only at printing, which is
 * what lets the changes be edited without the snapshot having to be recomputed.
 *
 * It doubles as the record of what the terms were before, so nothing has to be kept twice: before
 * carrying a proposal over, the billings here are held up against the live ones to see what moved
 * in the meantime.
 */
final class ProposalSnapshot
{
    /**
     * The parts a snapshot always carries.
     *
     * @var array<string>
     */
    public const REQUIRED = [
        'contract',
        'customer',
        'addresses',
        'version',
        'billings',
    ];

    /**
     * The terms that are held up against the live billing before a proposal is carried over.
     *
     * The same list the billings table refuses to have rewritten once it has been invoiced for,
     * plus the days the line runs between.
     *
     * @var array<string>
     */
    public const BILLING_TERMS = [
        'service_id',
        'quantity',
        'price',
        'fixed_discount',
        'percentage_discount',
        'billing_from',
        'billing_until',
    ];

    /**
     * @param array<string, mixed> $taken What was on record at the time.
     */
    private function __construct(private readonly array $taken)
    {
    }

    /**
     * Reads a snapshot back from the stored shape.
     *
     * @param array<string, mixed> $stored The stored snapshot.
     * @return self
     * @throws \InvalidArgumentException When a part the documents rely on is missing.
     */
    public static function fromArray(array $stored): self
    {
        foreach (self::REQUIRED as $part) {
            if (!array_key_exists($part, $stored)) {
                throw new InvalidArgumentException(sprintf('The snapshot says nothing about %s.', $part));
            }
        }

        return new self($stored);
    }

    /**
     * Writes the snapshot out in the shape it is stored in.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->taken;
    }

    /**
     * One part of the snapshot.
     *
     * @param string $part Which part.
     * @return array<string, mixed>
     */
    public function part(string $part): array
    {
        return (array)($this->taken[$part] ?? []);
    }

    /**
     * The billings as they stood, by their id.
     *
     * @return array<string, array<string, mixed>>
     */
    public function billings(): array
    {
        $found = [];

        foreach ($this->part('billings') as $billing) {
            $billing = (array)$billing;
            $id = $billing['id'] ?? null;

            if ($id !== null) {
                $found[(string)$id] = $billing;
            }
        }

        return $found;
    }

    /**
     * Whether the snapshot knows the given billing.
     *
     * @param string $billing_id Which billing.
     * @return bool
     */
    public function knowsBilling(string $billing_id): bool
    {
        return array_key_exists($billing_id, $this->billings());
    }

    /**
     * Which terms of the given billing have moved since the snapshot was taken.
     *
     * @param string $billing_id Which billing.
     * @param \App\Model\Entity\Billing $live The billing as it stands now.
     * @return array<string> The terms that no longer agree.
     */
    public function billingTermsThatMoved(string $billing_id, Billing $live): array
    {
        $taken = $this->billings()[$billing_id] ?? null;

        if ($taken === null) {
            return self::BILLING_TERMS;
        }

        $moved = [];

        foreach (self::BILLING_TERMS as $term) {
            $before = $taken[$term] ?? null;
            $now = $live->get($term);

            if ((string)$before !== (string)($now ?? '')) {
                $moved[] = $term;
            }
        }

        return $moved;
    }

    /**
     * The billings that are on the contract now but were not when the snapshot was taken.
     *
     * @param iterable<\App\Model\Entity\Billing> $live The billings as they stand now.
     * @return array<string> Their ids.
     */
    public function billingsAddedSince(iterable $live): array
    {
        $known = $this->billings();
        $added = [];

        foreach ($live as $billing) {
            if (!array_key_exists((string)$billing->id, $known)) {
                $added[] = (string)$billing->id;
            }
        }

        return $added;
    }
}
