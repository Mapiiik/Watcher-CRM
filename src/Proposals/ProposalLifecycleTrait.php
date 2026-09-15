<?php
declare(strict_types=1);

namespace App\Proposals;

use App\Model\Enum\ProposalStep;

/**
 * Where a set of papers stands, for the records that keep one.
 *
 * A proposal of a contract and a proposal put to a customer are different things and hold
 * different fields, but the road the papers travel is the same one: drawn up, sent, agreed to, or
 * given up on. That much is here so that the two cannot answer it differently.
 *
 * Where that road ends is not the same for both, and it is no longer the record's kind that says
 * so - the purpose is asked how far its papers go, and {@see \App\Proposals\Settlement} works the
 * rest out. Each record only has to hand over the fields it keeps.
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
     * Whether nobody is waiting for these papers any more.
     *
     * @return bool
     */
    public function hasBeenSettled(): bool
    {
        return $this->settlement()->isSettled();
    }

    /**
     * Where the papers stand, in one word for a listing to print.
     *
     * @return string
     */
    public function getState(): string
    {
        return $this->settlement()->state();
    }

    /**
     * Whether these papers ever go through the given step at all.
     *
     * @param \App\Model\Enum\ProposalStep $step The step being asked about.
     * @return bool
     */
    public function expects(ProposalStep $step): bool
    {
        return $this->settlement()->expects($step);
    }

    /**
     * Whether the given step is the one to take next.
     *
     * What a page offers goes through here rather than through the fields, so that a paper which
     * never gets signed is never asked for a signature.
     *
     * @param \App\Model\Enum\ProposalStep $step The step being asked about.
     * @return bool
     */
    public function isDueFor(ProposalStep $step): bool
    {
        return $this->settlement()->isDueFor($step);
    }

    /**
     * The fields this record keeps, handed to whatever reads them.
     *
     * @return \App\Proposals\Settlement
     */
    abstract protected function settlement(): Settlement;
}
