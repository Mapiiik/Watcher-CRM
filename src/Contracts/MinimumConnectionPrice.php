<?php
declare(strict_types=1);

namespace App\Contracts;

use App\Contracts\Proposal\ProposalProjection;
use App\Model\Entity\Billing;
use App\Model\Entity\ContractProposal;
use Cake\I18n\Number;
use PhpCollective\DecimalObject\Decimal;

/**
 * The monthly price a contract's connection is not lowered below.
 *
 * The connection is the billing whose service has a queue, the same way the checks tell the line
 * from a fee standing beside it. A contract has one line at a time, so the price of that one
 * billing is the price of the connection.
 *
 * Asked in one place because two things ask it: a billing being saved, and a proposal whose lines
 * would put one in place later.
 */
final class MinimumConnectionPrice
{
    /**
     * Whether the billing is for the connection itself.
     *
     * @param \App\Model\Entity\Billing $billing The billing, with its service where it has one.
     * @return bool
     */
    public static function isConnection(Billing $billing): bool
    {
        $service = $billing->service ?? null;

        if ($service === null) {
            return false;
        }

        // a service read from the table carries the id, one kept by a proposal carries the queue
        return $service->get('queue_id') !== null || $service->get('queue') !== null;
    }

    /**
     * Whether the billing puts the connection below the minimum.
     *
     * @param \App\Model\Entity\Billing $billing The billing, with its service where it has one.
     * @param \PhpCollective\DecimalObject\Decimal|null $minimum The contract's minimum, if it has one.
     * @return bool
     */
    public static function fallsBelow(Billing $billing, ?Decimal $minimum): bool
    {
        return $minimum !== null
            && self::isConnection($billing)
            && $billing->total_price->lessThan($minimum);
    }

    /**
     * The lines of a proposal that would put the connection below the minimum, where nobody allowed it.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param \PhpCollective\DecimalObject\Decimal|null $minimum The contract's minimum, if it has one.
     * @param (callable(\App\Contracts\Proposal\ProposedBilling): bool)|null $asked Which lines to ask
     *   about; all of them when not given.
     * @return list<\App\Contracts\Proposal\ProposedBilling>
     */
    public static function linesBelow(ContractProposal $proposal, ?Decimal $minimum, ?callable $asked = null): array
    {
        if ($minimum === null || $proposal->effective_from === null) {
            return [];
        }

        $snapshot = $proposal->stateOfThings();
        $changes = $proposal->proposedChanges();
        $rows = (new ProposalProjection())->explain(
            $snapshot->hydrate()->billings,
            $changes,
            $proposal->effective_from,
            $snapshot->servicesChosenBy($changes),
        );

        $below = [];

        foreach ($rows as $row) {
            $line = $row['line'];

            if (
                $line !== null
                && ($asked === null || $asked($line))
                && $line->startsABilling()
                && !$line->below_minimum_allowed
                && self::fallsBelow($row['billing'], $minimum)
            ) {
                $below[] = $line;
            }
        }

        return $below;
    }

    /**
     * What the operator is told when the price is refused.
     *
     * @param \PhpCollective\DecimalObject\Decimal $minimum The contract's minimum.
     * @return string
     */
    public static function refusal(Decimal $minimum): string
    {
        return __(
            'The connection price may not go below {0}, the minimum agreed on the contract.',
            Number::currency($minimum->toString()),
        );
    }
}
