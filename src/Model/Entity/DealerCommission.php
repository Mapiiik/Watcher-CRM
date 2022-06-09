<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * DealerCommission Entity
 *
 * @property \Cake\I18n\FrozenTime|null $created
 * @property int|null $created_by
 * @property \CakeDC\Users\Model\Entity\User|null $creator
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $modified_by
 * @property \CakeDC\Users\Model\Entity\User|null $modifier
 * @property int|null $dealer_id
 * @property int|null $commission_id
 * @property float|null $fixed
 * @property float|null $percentage
 * @property int $id
 *
 * @property \App\Model\Entity\Customer $dealer
 * @property \App\Model\Entity\Commission $commission
 */
class DealerCommission extends Entity
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
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'dealer_id' => true,
        'commission_id' => true,
        'fixed' => true,
        'percentage' => true,
        'dealer' => true,
        'commission' => true,
    ];
}
