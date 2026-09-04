<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Model\Entity\ContractVersionProposal;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * What carrying a proposal over would run into, said before anybody presses the button.
 *
 * A proposal is drawn up against how things stood, and then it waits - for the papers to go out, to
 * come back signed, to be got round to. Meanwhile somebody may have changed a billing by hand,
 * ended one, added one, or moved the version's dates. None of that stops the transfer, because the
 * operator may well know about it and want to go ahead anyway; it is said out loud instead.
 */
final class TransferPreview
{
    use LocatorAwareTrait;

    /**
     * The proposal has not been signed, so there is nothing to carry over yet.
     */
    public const NOT_CONCLUDED = 'not_concluded';

    /**
     * A billing the proposal acts on is no longer on the contract.
     */
    public const BILLING_GONE = 'billing_gone';

    /**
     * A billing the proposal acts on has been changed since the snapshot was taken.
     */
    public const BILLING_MOVED = 'billing_moved';

    /**
     * A billing has appeared on the contract that the proposal knows nothing about.
     */
    public const BILLING_APPEARED = 'billing_appeared';

    /**
     * The version's dates are no longer what they were when the proposal was drawn up.
     */
    public const VERSION_MOVED = 'version_moved';

    /**
     * The day it takes effect has been invoiced for already.
     */
    public const CLOSED_PERIOD = 'closed_period';

    /**
     * What stands in the way of carrying the proposal over, if anything.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @return array<int, array{what: string, said: string}> In the order they are worth reading.
     */
    public function of(ContractVersionProposal $proposal): array
    {
        $found = [];

        if (!$proposal->hasBeenConcluded()) {
            $found[] = [
                'what' => self::NOT_CONCLUDED,
                'said' => __('Nobody has signed this proposal yet, so there is nothing to carry over.'),
            ];
        }

        $found = array_merge($found, $this->whatMovedInTheBillings($proposal));
        $found = array_merge($found, $this->whatMovedOnTheVersion($proposal));

        return array_merge($found, $this->whatHasBeenInvoicedFor($proposal));
    }

    /**
     * Whether anything here would stop the transfer rather than merely be worth knowing.
     *
     * @param array<int, array{what: string, said: string}> $found What the preview found.
     * @return bool
     */
    public function anythingStopsIt(array $found): bool
    {
        foreach ($found as $one) {
            if (in_array($one['what'], [self::NOT_CONCLUDED, self::BILLING_GONE], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * What has happened to the billings since the snapshot was taken.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @return array<int, array{what: string, said: string}>
     */
    private function whatMovedInTheBillings(ContractVersionProposal $proposal): array
    {
        $snapshot = $proposal->stateOfThings();
        /** @var array<string, \App\Model\Entity\Billing> $live */
        $live = $this->fetchTable('Billings')
            ->find()
            ->where(['Billings.contract_id' => $proposal->contract_id])
            ->all()
            ->indexBy('id')
            ->toArray();

        $found = [];

        foreach ($proposal->proposedChanges()->billingsByBillingId() as $id => $_line) {
            /** @var \App\Model\Entity\Billing|null $one */
            $one = $live[$id] ?? null;

            if ($one === null) {
                $found[] = [
                    'what' => self::BILLING_GONE,
                    'said' => __(
                        'A billing this proposal changes is no longer on the contract.'
                        . ' Take the snapshot again and look the proposal over.',
                    ),
                ];

                continue;
            }

            $moved = $snapshot->billingTermsThatMoved((string)$id, $one);

            if ($moved !== []) {
                $found[] = [
                    'what' => self::BILLING_MOVED,
                    'said' => __(
                        'A billing this proposal changes has itself been changed since:'
                        . ' {0}. Carrying the proposal over will overwrite that.',
                        implode(', ', $moved),
                    ),
                ];
            }
        }

        foreach ($snapshot->billingsAddedSince($live) as $_id) {
            $found[] = [
                'what' => self::BILLING_APPEARED,
                'said' => __(
                    'A billing has been added to the contract that this proposal knows nothing'
                    . ' about. It will be left as it is.',
                ),
            ];
        }

        return $found;
    }

    /**
     * Whether the version still says what it said when the proposal was drawn up.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @return array<int, array{what: string, said: string}>
     */
    private function whatMovedOnTheVersion(ContractVersionProposal $proposal): array
    {
        $taken = $proposal->stateOfThings()->part('version');
        $version = $this->fetchTable('ContractVersions')
            ->find()
            ->where(['ContractVersions.id' => $proposal->contract_version_id])
            ->first();

        if ($version === null) {
            return [];
        }

        foreach (['valid_until', 'obligation_until', 'conclusion_date'] as $field) {
            if ((string)($taken[$field] ?? '') !== (string)($version->get($field) ?? '')) {
                return [[
                    'what' => self::VERSION_MOVED,
                    'said' => __(
                        'The contract version has been changed since this proposal was drawn up.',
                    ),
                ]];
            }
        }

        return [];
    }

    /**
     * Whether the day it takes effect has already been invoiced for.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @return array<int, array{what: string, said: string}>
     */
    private function whatHasBeenInvoicedFor(ContractVersionProposal $proposal): array
    {
        /** @var \App\Model\Table\BillingsTable $billings */
        $billings = $this->fetchTable('Billings');

        if ($proposal->effective_from >= $billings->firstOpenPeriodStart()) {
            return [];
        }

        return [[
            'what' => self::CLOSED_PERIOD,
            'said' => __(
                'This proposal takes effect on a day that has already been invoiced for.'
                . ' Only an administrator may write into an invoiced period, and only deliberately.',
            ),
        ]];
    }

    /**
     * What the transfer would leave the billings looking like, for the operator to read against
     * what is there now.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @return array<\App\Model\Entity\Billing>
     */
    public function billingsAfterwards(ContractVersionProposal $proposal): array
    {
        $snapshot = $proposal->stateOfThings();
        $changes = $proposal->proposedChanges();

        return (new ProposalProjection())->projectBillings(
            $snapshot->hydrate()->billings,
            $changes,
            $proposal->effective_from,
            $snapshot->servicesChosenBy($changes),
        );
    }

    /**
     * The billings as they stand now.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @return array<\App\Model\Entity\Billing>
     */
    public function billingsNow(ContractVersionProposal $proposal): array
    {
        /** @var array<\App\Model\Entity\Billing> $billings */
        $billings = $this->fetchTable('Billings')
            ->find()
            ->contain(['Services'])
            ->where(['Billings.contract_id' => $proposal->contract_id])
            ->orderBy(['Billings.billing_from' => 'ASC'])
            ->all()
            ->toList();

        return $billings;
    }
}
