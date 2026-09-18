<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Contracts\Proposal\ProposalChanges;
use App\Contracts\Proposal\ProposalConfirmations;
use App\Contracts\Proposal\ProposalSnapshot;
use App\Model\Entity\Trait\SendingTrait;
use App\Model\Enum\DocumentsDeliveryType;
use App\Model\Enum\ProposalStep;
use App\Proposals\ProposalLifecycleTrait;
use App\Proposals\Settlement;
use Cake\I18n\Date;
use RuntimeException;

/**
 * ContractProposal Entity
 *
 * @property string $id
 * @property string $contract_id
 * @property string|null $customer_proposal_id
 * @property string|null $contract_version_id
 * @property string|null $terminates_contract_version_id
 * @property string|null $terminated_contract_number
 * @property \App\Model\Enum\ProposalPurpose $purpose
 * @property \Cake\I18n\Date $effective_from
 * @property array<string, mixed> $snapshot
 * @property \Cake\I18n\DateTime $snapshot_taken
 * @property array<string, mixed> $changes
 * @property array<string, bool> $confirmations
 * @property \Cake\I18n\DateTime|null $applied
 * @property string|null $applied_by
 * @property \Cake\I18n\DateTime|null $revoked
 * @property string|null $revoked_by
 * @property string|null $note
 *
 * @property \App\Model\Entity\Contract $contract
 * @property \App\Model\Entity\ContractVersion|null $contract_version
 * @property \App\Model\Entity\CustomerProposal $customer_proposal
 * @property \App\Model\Entity\ContractVersion|null $terminated_contract_version
 *
 * Of the envelope rather than of the papers - see the getters below.
 * @property \Cake\I18n\Date|null $sent_date
 * @property \App\Model\Enum\DocumentsDeliveryType|null $delivery_type
 * @property \Cake\I18n\Date|null $conclusion_date
 */
class ContractProposal extends AppEntity
{
    use ProposalLifecycleTrait;
    use SendingTrait;

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
        'customer_proposal_id' => true,
        'contract_version_id' => true,
        'purpose' => true,
        'terminates_contract_version_id' => true,
        'terminated_contract_number' => true,
        'effective_from' => true,
        'snapshot' => true,
        'snapshot_taken' => true,
        'changes' => true,
        'confirmations' => true,
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
        'customer_proposal' => true,
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
     * @return \App\Contracts\Proposal\ProposalConfirmations
     */
    public function confirmations(): ProposalConfirmations
    {
        return ProposalConfirmations::fromArray((array)($this->confirmations ?? []));
    }

    /**
     * Whether the changes have been applied to the live records.
     *
     * @return bool
     */
    public function hasBeenApplied(): bool
    {
        return $this->applied !== null;
    }

    /**
     * The fields the proposal keeps.
     *
     * Everything a proposal of a contract asks for reaches the records by being applied, so
     * signing is only halfway and the purpose says as much.
     *
     * @return \App\Proposals\Settlement
     */
    protected function settlement(): Settlement
    {
        return new Settlement(
            $this->purpose->lastStep(),
            $this->sent_date,
            $this->conclusion_date,
            $this->applied,
            $this->revoked,
        );
    }

    /**
     * When the papers went out, and how.
     *
     * Of the envelope rather than of the papers: they leave in one and come back in one, so the
     * day and the way are facts about the envelope. Kept as properties of their own names so that
     * everything reading a proposal goes on reading it the same way.
     *
     * @return \Cake\I18n\Date|null
     * @throws \RuntimeException When the envelope was not fetched.
     */
    protected function _getSentDate(): ?Date
    {
        return $this->envelope()->sent_date;
    }

    /**
     * @return \App\Model\Enum\DocumentsDeliveryType|null
     * @throws \RuntimeException When the envelope was not fetched.
     */
    protected function _getDeliveryType(): ?DocumentsDeliveryType
    {
        return $this->envelope()->delivery_type;
    }

    /**
     * The day the customer agreed to what the envelope held.
     *
     * @return \Cake\I18n\Date|null
     * @throws \RuntimeException When the envelope was not fetched.
     */
    protected function _getConclusionDate(): ?Date
    {
        return $this->envelope()->conclusion_date;
    }

    /**
     * Whether what these papers ask for still has to be written into the live records.
     *
     * Asked without the envelope, unlike everything else about where the papers stand, because it
     * is what the envelope asks of what it holds: what has been applied is done, and what was
     * given up on is never applied at all.
     *
     * @return bool
     */
    public function isStillToBeApplied(): bool
    {
        return $this->purpose->lastStep() === ProposalStep::Applied
            && $this->applied === null
            && $this->revoked === null;
    }

    /**
     * The round these papers go out in.
     *
     * Asked rather than assumed: a proposal read without it would answer that nothing has gone
     * out, which is a different thing from not knowing.
     *
     * @return \App\Model\Entity\CustomerProposal
     * @throws \RuntimeException When the envelope was not fetched.
     */
    private function envelope(): CustomerProposal
    {
        if (!isset($this->customer_proposal)) {
            throw new RuntimeException(__('Customer proposal data not available.'));
        }

        return $this->customer_proposal;
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
     * What the papers are called wherever they are named beside something else: which contract
     * they are about, what they ask of it, and from when.
     *
     * The same words the papers are headed with on their own page, so a list of them and the page
     * they lead to agree. Without the contract loaded the number is left out rather than guessed.
     *
     * @return string
     */
    public function getName(): string
    {
        if (!isset($this->contract)) {
            return __('{0} from {1}', $this->purpose->label(), $this->effective_from);
        }

        return __(
            '{0} - {1} from {2}',
            $this->contract->number ?? '',
            $this->purpose->label(),
            $this->effective_from,
        );
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
