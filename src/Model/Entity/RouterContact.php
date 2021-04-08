<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * RouterContact Entity
 *
 * @property int $router_id
 * @property int $customer_id
 * @property int $id
 *
 * @property \App\Model\Entity\Router $router
 * @property \App\Model\Entity\Customer $customer
 */
class RouterContact extends Entity
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
        'router_id' => true,
        'customer_id' => true,
        'router' => true,
        'customer' => true,
    ];
}
