<?php
declare(strict_types=1);

namespace App\Model\Enum;

/**
 * How far a set of papers travels before nobody is waiting for it any more.
 *
 * The steps are in the order they happen, and a purpose names the one it ends at. Everything else
 * about a paper's life follows from that: what counts as settled, what the state reads as, and
 * which of the steps are offered at all.
 *
 * Never stored. It is read off the purpose, which is what the record keeps.
 */
enum ProposalStep: int
{
    case Issued = 1;
    case Delivered = 2;
    case Signed = 3;
    case Applied = 4;

    /**
     * Whether a paper going this far goes through the given step as well.
     *
     * @param \App\Model\Enum\ProposalStep $step The step being asked about.
     * @return bool
     */
    public function goesThrough(self $step): bool
    {
        return $step->value <= $this->value;
    }
}
