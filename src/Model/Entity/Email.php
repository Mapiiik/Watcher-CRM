<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Email Entity
 *
 * @property int $id
 * @property int $customer_id
 * @property string|null $email
 * @property bool $use_for_billing
 * @property bool $use_for_outages
 * @property bool $use_for_commercial
 *
 * @property \App\Model\Entity\Customer $customer
 */
class Email extends Entity
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
        'customer_id' => true,
        'email' => true,
        'use_for_billing' => true,
        'use_for_outages' => true,
        'use_for_commercial' => true,
        'customer' => true,
    ];
}
