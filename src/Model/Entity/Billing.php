<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\FrozenDate;
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

    protected function _getName(): string
    {
        $name = '';

        if (isset($this->text)) {
            $name = $this->text;
        } elseif (isset($this->service->name)) {
            $name = $this->service->name;
        }

        if ($this->quantity > 1) {
            $name = $this->quantity . 'x ' . $name;
        }

        return $name;
    }

    protected function _getSum(): float
    {
        $sum = 0;

        if (isset($this->price)) {
            $sum = $this->price;
        } elseif (isset($this->service->price)) {
            $sum = $this->service->price;
        }

        $sum = $sum * $this->quantity;

        return $sum;
    }

    protected function _getFixedDiscountSum(): float
    {
        $discount = 0;

        if (isset($this->fixed_discount)) {
            $discount = $this->fixed_discount;
        }

        return $discount;
    }

    protected function _getPercentageDiscountSum(): float
    {
        $discount = 0;

        if (isset($this->percentage_discount)) {
            $discount = $this->sum * $this->percentage_discount / 100;
        }

        return $discount;
    }

    protected function _getDiscount(): float
    {
        return $this->fixed_discount_sum + $this->percentage_discount_sum;
    }

    protected function _getTotal(): float
    {
        return $this->sum - $this->discount;
    }

    protected function _getVatBase(): float
    {
        return $this->total - $this->vat;
    }

    protected function _getVat(): float
    {
        return round($this->total - ($this->total / (1 + env('VAT_RATE', 0))), 2);
    }

    protected function _getSeparateInvoice(): bool
    {
        return $this->separate;
    }

    public function periodTotal(FrozenDate $from, FrozenDate $until): float
    {
        $period_days = $from->diffInDays($until->addDay(1));

        // billing_from not set
        if (is_null($this->billing_from)) {
            return 0;
        }

        // billing_from in future period
        if ($this->billing_from > $until) {
            return 0;
        }

        // billing_until before period
        if (!is_null($this->billing_until) && $this->billing_until < $from) {
            return 0;
        }

        if (is_null($this->billing_until) || (!is_null($this->billing_until) && $this->billing_until >= $until)) { // billing_until is not limiting
            // whole period
            if ($this->billing_from <= $from) {
                return ceil($this->total);
            }
            // later billing_from
            if ($this->billing_from <= $until) {
                return ceil($this->total / $period_days * $this->billing_from->diffInDays($until->addDay(1)));
            }
        } else { // billing_until is limiting
            // earlier billing_until
            if ($this->billing_from <= $from) {
                return ceil($this->total / $period_days * $from->diffInDays($this->billing_until->addDay(1)));
            }
            // later billing_from and earlier billing_until
            if ($this->billing_from <= $until) {
                return ceil($this->total / $period_days * $this->billing_from->diffInDays($this->billing_until->addDay(1)));
            }
        }

        return false;
    }
}
