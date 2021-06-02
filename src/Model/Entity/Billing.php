<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Billing Entity
 *
 * @property int $id
 * @property int|null $customer_id
 * @property string|null $text
 * @property int|null $price
 * @property \Cake\I18n\FrozenDate|null $billing_from
 * @property string|null $note
 * @property bool $active
 * @property int|null $modified_by
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $created_by
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenDate|null $billing_until
 * @property bool $separate
 * @property int|null $service_id
 * @property int $quantity
 * @property int $contract_id
 * @property int|null $fixed_discount
 * @property int|null $percentage_discount
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Service $service
 * @property \App\Model\Entity\Contract $contract
 */
class Billing extends Entity
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
        'text' => true,
        'price' => true,
        'billing_from' => true,
        'note' => true,
        'active' => true,
        'modified_by' => true,
        'modified' => true,
        'created_by' => true,
        'created' => true,
        'billing_until' => true,
        'separate' => true,
        'service_id' => true,
        'quantity' => true,
        'contract_id' => true,
        'customer' => true,
        'service' => true,
        'contract' => true,
        'fixed_discount' => true,
        'percentage_discount' => true,        
    ];
    
    protected function _getSum(): int
    {
        $sum = 0;
        
        if (isset($this->price)) {
            $sum = $this->price;
        } else if (isset($this->service->price)) {
            $sum = $this->service->price;
        }
        
        $sum = $sum * $this->quantity;
        
        return $sum;
    }    
    protected function _getDiscount(): int
    {
        $discount = 0;
        
        if (isset($this->percentage_discount)) $discount += $this->sum * $this->percentage_discount / 100;
        if (isset($this->fixed_discount)) $discount += $this->fixed_discount;
        
        return $discount;
    }
    protected function _getTotal(): int
    {
        return $this->sum - $this->discount;
    }    
}
