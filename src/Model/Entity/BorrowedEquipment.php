<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\Date;
use Cake\ORM\Entity;

/**
 * BorrowedEquipment Entity
 *
 * @property \Cake\I18n\DateTime|null $created
 * @property string|null $created_by
 * @property \App\Model\Entity\AppUser|null $creator
 * @property \Cake\I18n\DateTime|null $modified
 * @property string|null $modified_by
 * @property \App\Model\Entity\AppUser|null $modifier
 * @property string $id
 * @property int $nid
 * @property string $customer_id
 * @property string $contract_id
 * @property int $equipment_type_id
 * @property string|null $serial_number
 * @property \Cake\I18n\Date|null $borrowed_from
 * @property \Cake\I18n\Date|null $borrowed_until
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
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'customer_id' => true,
        'contract_id' => true,
        'equipment_type_id' => true,
        'serial_number' => true,
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
        $now = Date::now();

        if (isset($this->borrowed_from) && $this->borrowed_from > $now) {
            $style = 'color: darkorange;';
        }

        if (isset($this->borrowed_until) && $this->borrowed_until < $now) {
            $style = 'color: darkgray; text-decoration: line-through;';
        }

        if (isset($this->contract->style)) {
            $style .= ' ' . $this->contract->style;
        }

        return $style;
    }
}
