<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * BrokerageDealer Entity
 *
 * @property \Cake\I18n\FrozenTime|null $created
 * @property int|null $created_by
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $modified_by
 * @property int|null $dealer_id
 * @property int|null $brokerage_id
 * @property float|null $fixed
 * @property float|null $percentage
 * @property int $id
 *
 * @property \App\Model\Entity\Customer $dealer
 * @property \App\Model\Entity\Brokerage $brokerage
 */
class BrokerageDealer extends Entity
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
        'brokerage_id' => true,
        'fixed' => true,
        'percentage' => true,
        'dealer' => true,
        'brokerage' => true,
    ];
}
