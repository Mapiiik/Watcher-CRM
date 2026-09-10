<?php
declare(strict_types=1);

namespace App\Proposals;

/**
 * Where a set of papers stands, for the records that keep one.
 *
 * A proposal of a contract and a proposal put to a customer are different things and hold
 * different fields, but the road the papers travel is the same one: drawn up, sent, agreed to, or
 * given up on. That much is here so that the two cannot answer it differently.
 *
 * What settles a proposal is not the same for both, so each says it for itself. A contract's is
 * settled by being carried over, because that is when what it asked for reaches the live records;
 * one put to a customer is settled by coming back signed, because nothing waits behind it.
 */
trait ProposalLifecycleTrait
{
    /**
     * Whether the papers have gone out.
     *
     * This is what locks a proposal: what stood behind a paper that has left the building is not
     * rewritten afterwards.
     *
     * @return bool
     */
    public function hasBeenSent(): bool
    {
        return $this->sent_date !== null;
    }

    /**
     * Whether the customer has agreed to it.
     *
     * @return bool
     */
    public function hasBeenConcluded(): bool
    {
        return $this->conclusion_date !== null;
    }

    /**
     * Whether it was given up on.
     *
     * @return bool
     */
    public function hasBeenRevoked(): bool
    {
        return $this->revoked !== null;
    }

    /**
     * Whether it is still waiting to be settled one way or the other.
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return !$this->hasBeenSettled();
    }

    /**
     * What counts as settled for this kind of proposal.
     *
     * @return bool
     */
    abstract protected function hasBeenSettled(): bool;
}
