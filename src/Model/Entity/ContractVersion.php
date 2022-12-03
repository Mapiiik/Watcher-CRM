<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ContractVersion Entity
 *
 * @property \Cake\I18n\FrozenTime|null $created
 * @property int|null $created_by
 * @property \CakeDC\Users\Model\Entity\User|null $creator
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $modified_by
 * @property \CakeDC\Users\Model\Entity\User|null $modifier
 * @property string $id
 * @property int $contract_id
 * @property \Cake\I18n\FrozenDate $valid_from
 * @property \Cake\I18n\FrozenDate|null $valid_until
 * @property \Cake\I18n\FrozenDate|null $obligation_until
 * @property \Cake\I18n\FrozenDate|null $conclusion_date
 * @property int $number_of_amendments
 * @property string|null $note
 * @property int|null $minimum_duration
 *
 * @property \App\Model\Entity\Contract $contract
 */
class ContractVersion extends Entity
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
    protected $_accessible = [
        'contract_id' => true,
        'valid_from' => true,
        'valid_until' => true,
        'obligation_until' => true,
        'conclusion_date' => true,
        'number_of_amendments' => true,
        'note' => true,
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'contract' => true,
    ];

    /**
     * getter for minumum duration of contract in months (based on valid_from a obligation_until params)
     *
     * @return int|null
     */
    protected function _getMinimumDuration(): ?int
    {
        $minimum_duration = null;

        if (isset($this->obligation_until) && ($this->valid_from < $this->obligation_until)) {
            $minimum_duration = $this->valid_from->diffInMonths($this->obligation_until->addDay(1));
        }

        return $minimum_duration;
    }
}
