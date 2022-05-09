<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * CustomerLabel Entity
 *
 * @property int $label_id
 * @property int $customer_id
 * @property \Cake\I18n\FrozenTime $created
 * @property string|null $note
 * @property int $id
 * @property int $created_by
 *
 * @property \App\Model\Entity\Label $label
 * @property \App\Model\Entity\Customer $customer
 */
class CustomerLabel extends Entity
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
        'label_id' => true,
        'customer_id' => true,
        'created' => true,
        'note' => true,
        'created_by' => true,
        'label' => true,
        'customer' => true,
    ];
}
