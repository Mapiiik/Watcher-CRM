<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

/**
 * What a proposal asks the contract itself to become.
 */
final class ProposedContract extends ProposedDates
{
    /**
     * The fields a proposal may ask a contract for.
     *
     * @var array<string>
     */
    protected const FIELDS = [
        'termination_date',
    ];

    /**
     * Whether the proposal puts an end date on the contract.
     *
     * @return bool
     */
    public function endsTheContract(): bool
    {
        return $this->sets('termination_date');
    }
}
