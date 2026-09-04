<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Contracts\Proposal\ProposalAcknowledgements;
use App\Contracts\Proposal\ProposalChanges;
use App\Contracts\Proposal\ProposalSnapshot;
use Cake\I18n\Date;

/**
 * ContractVersionProposal Entity
 *
 * @property string $id
 * @property string $contract_id
 * @property string $contract_version_id
 * @property string|null $terminates_contract_version_id
 * @property string|null $terminated_contract_number
 * @property \Cake\I18n\Date $effective_from
 * @property array<string, mixed> $snapshot
 * @property \Cake\I18n\DateTime $snapshot_taken
 * @property array<string, mixed> $changes
 * @property array<string, bool> $acknowledgements
 * @property \Cake\I18n\Date|null $sent_date
 * @property \App\Model\Enum\ContractDeliveryMethod|null $sent_by
 * @property \Cake\I18n\Date|null $conclusion_date
 * @property \Cake\I18n\DateTime|null $applied
 * @property string|null $applied_by
 * @property \Cake\I18n\DateTime|null $revoked
 * @property string|null $revoked_by
 * @property string|null $note
 *
 * @property \App\Model\Entity\Contract $contract
 * @property \App\Model\Entity\ContractVersion $contract_version
 * @property \App\Model\Entity\ContractVersion|null $terminated_contract_version
 */
class ContractVersionProposal extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'contract_id' => true,
        'contract_version_id' => true,
        'terminates_contract_version_id' => true,
        'terminated_contract_number' => true,
        'effective_from' => true,
        'snapshot' => true,
        'snapshot_taken' => true,
        'changes' => true,
        'acknowledgements' => true,
        'sent_date' => true,
        'sent_by' => true,
        'conclusion_date' => true,
        'applied' => true,
        'applied_by' => true,
        'revoked' => true,
        'revoked_by' => true,
        'note' => true,
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'contract' => true,
        'contract_version' => true,
        'terminated_contract_version' => true,
    ];

    /**
     * What the proposal asks to happen once it is signed.
     *
     * @return \App\Contracts\Proposal\ProposalChanges
     */
    public function proposedChanges(): ProposalChanges
    {
        return ProposalChanges::fromArray((array)($this->changes ?? []));
    }

    /**
     * How everything stood when the proposal was drawn up.
     *
     * @return \App\Contracts\Proposal\ProposalSnapshot
     */
    public function stateOfThings(): ProposalSnapshot
    {
        return ProposalSnapshot::fromArray((array)($this->snapshot ?? []));
    }

    /**
     * What the operator confirmed against the readiness checks.
     *
     * @return \App\Contracts\Proposal\ProposalAcknowledgements
     */
    public function confirmations(): ProposalAcknowledgements
    {
        return ProposalAcknowledgements::fromArray((array)($this->acknowledgements ?? []));
    }

    /**
     * Whether the papers have gone out.
     *
     * This is what locks the proposal: what stood behind a paper that has left the building is not
     * rewritten afterwards.
     *
     * @return bool
     */
    public function hasBeenSent(): bool
    {
        return $this->sent_date !== null;
    }

    /**
     * Whether the customer has agreed to the proposal.
     *
     * @return bool
     */
    public function hasBeenConcluded(): bool
    {
        return $this->conclusion_date !== null;
    }

    /**
     * Whether the changes have been carried over into the live records.
     *
     * @return bool
     */
    public function hasBeenApplied(): bool
    {
        return $this->applied !== null;
    }

    /**
     * Whether the proposal was given up on.
     *
     * @return bool
     */
    public function hasBeenRevoked(): bool
    {
        return $this->revoked !== null;
    }

    /**
     * Whether the proposal is still waiting to be settled one way or the other.
     *
     * @return bool
     */
    public function isOpen(): bool
    {
        return !$this->hasBeenApplied() && !$this->hasBeenRevoked();
    }

    /**
     * Whether the proposal is signed and waiting to be carried over.
     *
     * @return bool
     */
    public function isWaitingToBeTransferred(): bool
    {
        return $this->isOpen() && $this->hasBeenConcluded();
    }

    /**
     * Whether the proposal ends an earlier version of the same contract.
     *
     * A shorthand for what would otherwise be two proposals; it exists because the paper that does
     * it is one paper.
     *
     * @return bool
     */
    public function terminatesAnotherVersion(): bool
    {
        return $this->terminates_contract_version_id !== null;
    }

    /**
     * Whether the proposal brings the contract to an end.
     *
     * @return bool
     */
    public function endsTheContract(): bool
    {
        return $this->proposedChanges()->endsTheContract();
    }

    /**
     * The day the billings it replaces are to stop.
     *
     * @return \Cake\I18n\Date
     */
    public function dayBeforeItTakesEffect(): Date
    {
        return $this->effective_from->subDays(1);
    }
}
