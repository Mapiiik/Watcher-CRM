<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\FrozenDate;
use Cake\ORM\Entity;

/**
 * BorrowedEquipment Entity
 *
 * @property int $id
 * @property int $customer_id
 * @property int $contract_id
 * @property int $equipment_type_id
 * @property string|null $serial_number
 * @property \Cake\I18n\FrozenTime $created
 * @property int $created_by
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $modified_by
 * @property \Cake\I18n\FrozenDate|null $borrowed_from
 * @property \Cake\I18n\FrozenDate|null $borrowed_until
 * @property string $style
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Contract $contract
 * @property \App\Model\Entity\EquipmentType $equipment_type
 */
class BorrowedEquipment extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<bool>
     */
    protected $_accessible = [
        'customer_id' => true,
        'contract_id' => true,
        'equipment_type_id' => true,
        'serial_number' => true,
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'borrowed_from' => true,
        'borrowed_until' => true,
        'customer' => true,
        'contract' => true,
        'equipment_type' => true,
    ];

    /**
     * getter for style
     *
     * @return string
     */
    protected function _getStyle(): string
    {
        $style = '';
        $now = new FrozenDate();

        if (isset($this->borrowed_from) && $this->borrowed_from > $now) {
            $style = 'background-color: #ffbc80;';
        }

        if (isset($this->borrowed_until) && $this->borrowed_until < $now) {
            $style = 'background-color: #bbbbbb;';
        }

        if (isset($this->contract->valid_until) && $this->contract->valid_until < $now) {
            $style = 'background-color: #ffaaaa;';
        }

        return $style;
    }
}
