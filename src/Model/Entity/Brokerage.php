<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Brokerage Entity
 *
 * @property int $id
 * @property string|null $name
 *
 * @property \App\Model\Entity\BrokerageDealer[] $brokerage_dealers
 * @property \App\Model\Entity\Contract[] $contracts
 */
class Brokerage extends Entity
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
        'name' => true,
        'brokerage_dealers' => true,
        'contracts' => true,
    ];
}
