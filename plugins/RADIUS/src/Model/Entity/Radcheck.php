<?php
declare(strict_types=1);

namespace RADIUS\Model\Entity;

use Cake\ORM\Entity;

/**
 * Radcheck Entity
 *
 * @property int $id
 * @property string $username
 * @property string $attribute
 * @property string $op
 * @property string $value
 * @property int $customer_connection_id
 * @property int $modified_by
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int $created_by
 * @property \Cake\I18n\FrozenTime $created
 * @property int $type
 * @property int|null $customer_id
 * @property int|null $contract_id
 *
 * @property \RADIUS\Model\Entity\CustomerConnection $customer_connection
 * @property \RADIUS\Model\Entity\Customer $customer
 * @property \RADIUS\Model\Entity\Contract $contract
 */
class Radcheck extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'username' => true,
        'attribute' => true,
        'op' => true,
        'value' => true,
        'customer_connection_id' => true,
        'modified_by' => true,
        'modified' => true,
        'created_by' => true,
        'created' => true,
        'type' => true,
        'customer_id' => true,
        'contract_id' => true,
        'customer_connection' => true,
        'customer' => true,
        'contract' => true,
    ];
}
