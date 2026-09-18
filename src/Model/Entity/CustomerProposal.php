<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Entity\Trait\SendingTrait;
use App\Model\Enum\ProposalStep;
use App\Proposals\ProposalLifecycleTrait;
use App\Proposals\Settlement;
use RuntimeException;

/**
 * CustomerProposal Entity
 *
 * One round of a paper that concerns the customer rather than any one contract.
 *
 * It holds the round and nothing else: what it was for, the day it speaks about, and the days it
 * went out and came back. What the paper said is the paper, frozen in the store the moment it was
 * drawn - there is no snapshot beside it, because a second copy of the same truth is only somewhere
 * else for it to be wrong.
 *
 * @property string $id
 * @property string $customer_id
 * @property \App\Model\Enum\CustomerProposalPurpose|null $purpose
 * @property \Cake\I18n\Date $effective_from
 * @property \Cake\I18n\Date|null $sent_date
 * @property \App\Model\Enum\DocumentsDeliveryType|null $delivery_type
 * @property \Cake\I18n\Date|null $conclusion_date
 * @property \Cake\I18n\DateTime|null $revoked
 * @property string|null $revoked_by
 * @property string|null $note
 *
 * @property \App\Model\Entity\Customer $customer
 * @property array<\App\Model\Entity\ContractProposal> $contract_proposals
 */
class CustomerProposal extends AppEntity
{
    use ProposalLifecycleTrait;
    use SendingTrait;

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'customer_id' => true,
        'purpose' => true,
        'effective_from' => true,
        'sent_date' => true,
        'delivery_type' => true,
        'conclusion_date' => true,
        'revoked' => true,
        'revoked_by' => true,
        'note' => true,
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'customer' => true,
    ];

    /**
     * What the proposal is for, in words: what it asks of the customer, and whether it carries the
     * papers of any of their contracts.
     *
     * Both halves, because either may stand alone - a consent asked on its own, a proposal opened
     * only to hold a contract's papers, or the two together. One that asks for nothing and holds
     * nothing says so, which is what a proposal just opened is.
     *
     * The papers of the contracts have to be loaded: a proposal asked without them would say it
     * carries none, which is a different thing from not knowing.
     *
     * @return string
     * @throws \RuntimeException When what the proposal holds was not loaded.
     */
    public function whatItIsFor(): string
    {
        $parts = $this->contractProposals();

        $said = array_filter([
            $this->purpose?->label(),
            $parts === [] ? null : __('Documents of the contracts'),
        ]);

        return $said === [] ? __('Nothing asked for yet') : implode(' + ', $said);
    }

    /**
     * Where the round stands, in one word for a listing to print.
     *
     * Its own road ends at the signature - nothing of the customer's own is written anywhere
     * afterwards. What it holds goes further, though: the papers of a contract are written into
     * the live records one contract at a time, and until that is done the round is not finished
     * even though it is settled. So after the signature the round says what is left to do.
     *
     * Settled and finished part company here on purpose. What is settled decides whether more
     * papers may join the round and whether it may still be changed, and the signature is the
     * right line for both.
     *
     * @return string
     * @throws \RuntimeException When what the round holds was not loaded.
     */
    public function getState(): string
    {
        $said = $this->settlement()->state();

        if (!$this->hasBeenConcluded() || $this->hasBeenRevoked()) {
            return $said;
        }

        $carried = false;

        foreach ($this->contractProposals() as $part) {
            if ($part->isStillToBeCarriedOver()) {
                return __('Waiting to be carried over');
            }

            $carried = $carried || $part->hasBeenApplied();
        }

        return $carried ? __('Carried over') : $said;
    }

    /**
     * Whether there is nothing left to do about the round at all.
     *
     * Not the same as settled, which is the signature: the papers of a contract are written into
     * the live records afterwards, one contract at a time, and a round is not done with while any
     * of that is still owed. This is what a listing greys a row out on.
     *
     * @return bool
     * @throws \RuntimeException When what the round holds was not loaded.
     */
    public function hasBeenDealtWith(): bool
    {
        if (!$this->hasBeenSettled()) {
            return false;
        }

        foreach ($this->contractProposals() as $part) {
            if ($part->isStillToBeCarriedOver()) {
                return false;
            }
        }

        return true;
    }

    /**
     * What the round holds, asked rather than assumed.
     *
     * @return array<\App\Model\Entity\ContractProposal>
     * @throws \RuntimeException When they were not loaded.
     */
    private function contractProposals(): array
    {
        if (!isset($this->contract_proposals)) {
            throw new RuntimeException(__('Contract proposal data not available.'));
        }

        return $this->contract_proposals;
    }

    /**
     * The fields the round keeps.
     *
     * Nothing stands behind a paper put to a customer waiting to be written, so it never carries
     * anything over. A round that asks for nothing of the customer's own - one that is there to
     * hold the papers of their contracts together - travels the same road all the same.
     *
     * @return \App\Proposals\Settlement
     */
    protected function settlement(): Settlement
    {
        return new Settlement(
            $this->purpose?->lastStep() ?? ProposalStep::Signed,
            $this->sent_date,
            $this->conclusion_date,
            null,
            $this->revoked,
        );
    }
}
