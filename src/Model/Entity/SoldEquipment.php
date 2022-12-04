<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SoldEquipment Entity
 *
 * @property \Cake\I18n\FrozenTime|null $created
 * @property int|null $created_by
 * @property \CakeDC\Users\Model\Entity\User|null $creator
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $modified_by
 * @property \CakeDC\Users\Model\Entity\User|null $modifier
 * @property int $id
 * @property int $customer_id
 * @property int $contract_id
 * @property int $equipment_type_id
 * @property string|null $serial_number
 * @property \Cake\I18n\FrozenTime|null $date_of_sale
 * @property string $style
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Contract $contract
 * @property \App\Model\Entity\EquipmentType $equipment_type
 */
class SoldEquipment extends Entity
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
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'customer_id' => true,
        'contract_id' => true,
        'equipment_type_id' => true,
        'serial_number' => true,
        'date_of_sale' => true,
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

        if (isset($this->contract->style)) {
            $style .= ' ' . $this->contract->style;
        }

        return $style;
    }
}
