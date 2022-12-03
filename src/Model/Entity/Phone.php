<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Phone Entity
 *
 * @property \Cake\I18n\FrozenTime|null $created
 * @property int|null $created_by
 * @property \CakeDC\Users\Model\Entity\User|null $creator
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $modified_by
 * @property \CakeDC\Users\Model\Entity\User|null $modifier
 * @property int $id
 * @property int $customer_id
 * @property string|null $phone
 * @property bool $use_for_billing
 * @property bool $use_for_outages
 * @property bool $use_for_commercial
 * @property string|null $note
 *
 * @property \App\Model\Entity\Customer $customer
 */
class Phone extends Entity
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
        'phone' => true,
        'use_for_billing' => true,
        'use_for_outages' => true,
        'use_for_commercial' => true,
        'note' => true,
        'customer' => true,
    ];
}
