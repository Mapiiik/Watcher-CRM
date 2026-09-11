<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Entity\Trait\SendingTrait;
use App\Proposals\ProposalLifecycleTrait;

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
 * @property \App\Model\Enum\CustomerProposalPurpose $purpose
 * @property \Cake\I18n\Date $effective_from
 * @property \Cake\I18n\Date|null $sent_date
 * @property \App\Model\Enum\DocumentsDeliveryType|null $delivery_type
 * @property \Cake\I18n\Date|null $conclusion_date
 * @property \Cake\I18n\DateTime|null $revoked
 * @property string|null $revoked_by
 * @property string|null $note
 *
 * @property \App\Model\Entity\Customer $customer
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
     * What settles a paper put to a customer: it comes back signed, or nobody waits for it any
     * more. Nothing stands behind it waiting to be written, so signing is the end of the road
     * rather than the middle of it.
     *
     * @return bool
     */
    protected function hasBeenSettled(): bool
    {
        return $this->hasBeenConcluded() || $this->hasBeenRevoked();
    }

    /**
     * Where the round stands, in one word for a listing to print.
     *
     * Read backwards, as the contract's is: what settled it comes before what only moved it along.
     *
     * @return string
     */
    public function getState(): string
    {
        return match (true) {
            $this->hasBeenRevoked() => __('Revoked'),
            $this->hasBeenConcluded() => __('Signed'),
            $this->hasBeenSent() => __('Sent'),
            default => __('Being prepared'),
        };
    }
}
