<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\Date;

/**
 * ContractVersion Entity
 *
 * @property string $id
 * @property string $contract_id
 * @property \Cake\I18n\Date $valid_from
 * @property \Cake\I18n\Date|null $valid_until
 * @property \Cake\I18n\Date|null $obligation_until
 * @property bool $obligations_settled
 * @property \Cake\I18n\Date|null $conclusion_date
 * @property int $number_of_amendments
 * @property string|null $note
 * @property int|null $minimum_duration
 * @property string $style
 *
 * @property \App\Model\Entity\Contract $contract
 */
class ContractVersion extends AppEntity
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
        'valid_from' => true,
        'valid_until' => true,
        'obligation_until' => true,
        'obligations_settled' => true,
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
            $minimum_duration = $this->valid_from->diffInMonths($this->obligation_until->addDays(1));
        }

        return $minimum_duration;
    }

    /**
     * getter for style
     *
     * @return string
     */
    protected function _getStyle(): string
    {
        $style = '';
        $now = Date::now();

        if (isset($this->valid_from) && $this->valid_from > $now) {
            $style = 'color: darkorange;';
        }

        if (isset($this->valid_until) && $this->valid_until < $now) {
            if ($this->valid_until < $now->firstOfMonth()) {
                $style = 'color: darkgray; text-decoration: line-through;';
            } else {
                $style = 'color: darkgray;';
            }
        }

        return $style;
    }
}
