<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Model\Entity;

use Cake\ORM\Entity;

/**
 * Invoice Entity
 *
 * @property int $number
 * @property int|null $varsym
 * @property \Cake\I18n\FrozenDate|null $date
 * @property \Cake\I18n\FrozenDate|null $maturity
 * @property string|null $text
 * @property string|null $sum
 * @property string|null $debt
 * @property \Cake\I18n\FrozenDate|null $payment_date
 * @property int $id
 * @property int|null $customer_id
 *
 * @property \App\Model\Entity\Customer $customer
 */
class Invoice extends Entity
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
        'number' => true,
        'varsym' => true,
        'date' => true,
        'maturity' => true,
        'text' => true,
        'sum' => true,
        'debt' => true,
        'payment_date' => true,
        'customer_id' => true,
        'customer' => true,
    ];
}
