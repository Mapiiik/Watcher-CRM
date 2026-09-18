<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Contracts\MinimumConnectionPrice;
use App\Model\Entity\ContractProposal;
use App\Model\Enum\ContractDocumentType;
use App\Model\Enum\DocumentVariant;
use App\Service\ContractPrint\ContractDocuments;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * What applying a proposal would run into, said before anybody presses the button.
 *
 * A proposal is drawn up against how things stood, and then it waits - for the papers to go out, to
 * come back signed, to be got round to. Meanwhile somebody may have changed a billing by hand,
 * ended one, added one, or moved the version's dates. None of that stops applying the changes, because the
 * operator may well know about it and want to go ahead anyway; it is said out loud instead.
 */
final class ChangePreview
{
    use LocatorAwareTrait;

    /**
     * The proposal has not been signed, so there is nothing to apply yet.
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
     * A line prices the connection below the contract's minimum and nobody allowed it.
     */
    public const BELOW_MINIMUM = 'below_minimum';

    /**
     * The signature is written down and the signed papers have not been filed.
     */
    public const NOTHING_SIGNED_ON_FILE = 'nothing_signed_on_file';

    /**
     * What stands in the way of applying the proposal, if anything.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return array<int, array{what: string, said: string}> In the order they are worth reading.
     */
    public function of(ContractProposal $proposal): array
    {
        $found = [];

        if (!$proposal->hasBeenConcluded()) {
            $found[] = [
                'what' => self::NOT_CONCLUDED,
                'said' => __('Nobody has signed this proposal yet, so there are no changes to'
                    . ' apply.'),
            ];
        }

        $found = array_merge($found, $this->whatIsNotOnFile($proposal));
        $found = array_merge($found, $this->whatMovedInTheBillings($proposal));
        $found = array_merge($found, $this->whatMovedOnTheVersion($proposal));

        $found = array_merge($found, $this->whatHasBeenInvoicedFor($proposal));

        return array_merge($found, $this->whatFallsBelowTheMinimum($proposal));
    }

    /**
     * Whether anything here would stop applying the changes rather than merely be worth knowing.
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
     * Whether the signed papers ever arrived.
     *
     * Stops nothing on purpose - holding the records back for a scan would leave the service
     * waiting on the scanner - but this is the moment somebody is looking.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return array<int, array{what: string, said: string}>
     */
    private function whatIsNotOnFile(ContractProposal $proposal): array
    {
        // Nothing of ours is signed for a contract that keeps no versions, so nothing is missing.
        if (!$proposal->hasBeenConcluded() || !$proposal->keepsVersions()) {
            return [];
        }

        $filed = (new ContractDocuments())->filedAgainst([$proposal])[(string)$proposal->id] ?? [];

        foreach ($filed as $document_type => $byVariant) {
            $type = ContractDocumentType::tryFrom((string)$document_type);

            // A notice or a certificate is the answer by itself, however it came back.
            if ($type?->speaksForItself() ?? false) {
                return [];
            }

            // Otherwise it is a signed copy of the agreement; a signed protocol does not stand in.
            if (!($type?->isTheAgreement() ?? false)) {
                continue;
            }

            foreach (array_keys($byVariant) as $variant) {
                if (DocumentVariant::tryFrom((string)$variant)?->carriesTheCustomersSignature() ?? false) {
                    return [];
                }
            }
        }

        return [[
            'what' => self::NOTHING_SIGNED_ON_FILE,
            'said' => __('The signature is recorded, but no signed documents have been filed'
                . ' against this proposal.'),
        ]];
    }

    /**
     * What has happened to the billings since the snapshot was taken.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return array<int, array{what: string, said: string}>
     */
    private function whatMovedInTheBillings(ContractProposal $proposal): array
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
                        'A billing this proposal changes has itself been changed since: {0}.'
                        . ' Applying the changes will overwrite that.',
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
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return array<int, array{what: string, said: string}>
     */
    private function whatMovedOnTheVersion(ContractProposal $proposal): array
    {
        // Papers that bring their version into being name none, and nothing can have moved on a
        // version that is not there yet.
        if ($proposal->contract_version_id === null) {
            return [];
        }

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
                        'The contract version has been changed since this proposal was created.',
                    ),
                ]];
            }
        }

        return [];
    }

    /**
     * Whether the day it takes effect has already been invoiced for.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return array<int, array{what: string, said: string}>
     */
    private function whatHasBeenInvoicedFor(ContractProposal $proposal): array
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
     * Whether a line prices the connection below the contract's minimum.
     *
     * The line was checked when it was written, so this is the minimum having been raised since.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return array<int, array{what: string, said: string}>
     */
    private function whatFallsBelowTheMinimum(ContractProposal $proposal): array
    {
        /** @var \App\Model\Table\BillingsTable $billings */
        $billings = $this->fetchTable('Billings');
        $minimum = $billings->minimumConnectionPriceOf($proposal->contract_id);

        if ($minimum === null || MinimumConnectionPrice::linesBelow($proposal, $minimum) === []) {
            return [];
        }

        return [[
            'what' => self::BELOW_MINIMUM,
            'said' => MinimumConnectionPrice::refusal($minimum) . ' ' . __(
                'Only an administrator may apply it, and only deliberately.',
            ),
        ]];
    }

    /**
     * What applying the changes would leave the billings looking like, for the operator to read against
     * what is there now.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return array<\App\Model\Entity\Billing>
     */
    public function billingsAfterwards(ContractProposal $proposal): array
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
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return array<\App\Model\Entity\Billing>
     */
    public function billingsNow(ContractProposal $proposal): array
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
