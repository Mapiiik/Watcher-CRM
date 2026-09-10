<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Model\Entity\ContractVersion;
use App\Model\Entity\ContractVersionProposal;
use App\Model\Enum\ProposalPurpose;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Everything carrying a proposal over will write, apart from the billings.
 *
 * The billings have had their own before-and-after on the preview from the start, because that is
 * what a proposal is usually about. The rest of it - the version, the version it replaces and the
 * contract - was written without ever being shown, and some of it without anybody having asked:
 * the day the customer signed goes onto the version, an amendment counts itself, and a version
 * being replaced is given the day before as its last.
 *
 * Held as a list of what will be written rather than worked out where it is needed, so that the
 * page that shows it and the transfer that does it cannot come to different answers.
 */
final class TransferPlan
{
    use LocatorAwareTrait;

    /**
     * The records a transfer writes to.
     *
     * @var string
     */
    public const VERSION = 'version';
    public const REPLACED_VERSION = 'replacedVersion';
    public const CONTRACT = 'contract';

    /**
     * What the transfer will write.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @return list<\App\Contracts\Proposal\PlannedChange>
     */
    public function of(ContractVersionProposal $proposal): array
    {
        return array_merge(
            $this->onTheVersion($proposal),
            $this->onTheVersionItReplaces($proposal),
            $this->onTheContract($proposal),
        );
    }

    /**
     * What it will write onto the version the proposal belongs to.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @return list<\App\Contracts\Proposal\PlannedChange>
     */
    private function onTheVersion(ContractVersionProposal $proposal): array
    {
        /** @var \App\Model\Entity\ContractVersion $version */
        $version = $this->fetchTable('ContractVersions')->get($proposal->contract_version_id);

        $planned = [];

        foreach ($proposal->proposedChanges()->version->asked() as $field => $value) {
            $planned[] = $this->change(
                self::VERSION,
                $version,
                $field,
                ProposedVersion::label($field),
                $version->get($field),
                $value,
                true,
            );
        }

        // Asked before the signature below fills it in: what makes this an amendment is that the
        // version was already agreed to before this paper.
        $amends = $proposal->purpose === ProposalPurpose::ServiceChange
            && $version->conclusion_date !== null;

        // The signature comes over though nobody asked for it: it is recorded on the proposal, and
        // the version is what the checks and the reminders read. Only onto a version that has none
        // of its own, because a version signed long ago and amended today was still agreed on the
        // day it was agreed.
        if ($version->conclusion_date === null) {
            $planned[] = $this->change(
                self::VERSION,
                $version,
                'conclusion_date',
                __('Conclusion Date'),
                null,
                $proposal->conclusion_date,
                false,
            );
        }

        if ($amends) {
            $planned[] = $this->change(
                self::VERSION,
                $version,
                'number_of_amendments',
                __('Number of Amendments'),
                $version->number_of_amendments,
                $this->amendmentsAfterwards($proposal, $version),
                false,
            );
        }

        return $planned;
    }

    /**
     * The number the amendment was printed with.
     *
     * The snapshot's count plus one, the same arithmetic the paper did, rather than one more than
     * whatever the version says today - that is what keeps the paper and the record agreeing.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @param \App\Model\Entity\ContractVersion $version The version it belongs to.
     * @return int
     */
    private function amendmentsAfterwards(ContractVersionProposal $proposal, ContractVersion $version): int
    {
        // What the papers were drawn from, falling back on the version for a snapshot taken before
        // this field was kept in one.
        $taken = $proposal->stateOfThings()->part('version');

        return (int)($taken['number_of_amendments'] ?? $version->number_of_amendments) + 1;
    }

    /**
     * What it will write onto the version the proposal replaces.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @return list<\App\Contracts\Proposal\PlannedChange>
     */
    private function onTheVersionItReplaces(ContractVersionProposal $proposal): array
    {
        if (!$proposal->terminatesAnotherVersion()) {
            return [];
        }

        /** @var \App\Model\Entity\ContractVersion $replaced */
        $replaced = $this->fetchTable('ContractVersions')->get($proposal->terminates_contract_version_id);

        return [
            $this->change(
                self::REPLACED_VERSION,
                $replaced,
                'valid_until',
                ProposedVersion::label('valid_until'),
                $replaced->valid_until,
                $proposal->effective_from->subDays(1),
                false,
            ),
        ];
    }

    /**
     * What it will write onto the contract.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @return list<\App\Contracts\Proposal\PlannedChange>
     */
    private function onTheContract(ContractVersionProposal $proposal): array
    {
        $asked = $proposal->proposedChanges()->contract;

        if ($asked->isEmpty()) {
            return [];
        }

        /** @var \App\Model\Entity\Contract $contract */
        $contract = $this->fetchTable('Contracts')->get($proposal->contract_id);

        $planned = [];

        foreach ($asked->asked() as $field => $value) {
            $planned[] = new PlannedChange(
                self::CONTRACT,
                (string)$contract->id,
                (string)$contract->number,
                $field,
                ProposedContract::label($field),
                $contract->get($field),
                $value,
                true,
            );
        }

        return $planned;
    }

    /**
     * One planned write onto a contract version.
     *
     * @param string $target Which version.
     * @param \App\Model\Entity\ContractVersion $version The version itself.
     * @param string $field The column.
     * @param string $label What to call the column.
     * @param \Cake\I18n\Date|string|int|null $from What it says now.
     * @param \Cake\I18n\Date|string|int|null $to What it will say.
     * @param bool $asked Whether somebody asked for it.
     * @return \App\Contracts\Proposal\PlannedChange
     */
    private function change(
        string $target,
        ContractVersion $version,
        string $field,
        string $label,
        mixed $from,
        mixed $to,
        bool $asked,
    ): PlannedChange {
        return new PlannedChange(
            $target,
            (string)$version->id,
            (string)$version->name,
            $field,
            $label,
            $from,
            $to,
            $asked,
        );
    }
}
