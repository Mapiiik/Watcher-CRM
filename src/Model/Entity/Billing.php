<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\FrozenDate;
use Cake\ORM\Entity;

/**
 * Billing Entity
 *
 * @property \Cake\I18n\FrozenTime|null $created
 * @property int|null $created_by
 * @property \CakeDC\Users\Model\Entity\User|null $creator
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $modified_by
 * @property \CakeDC\Users\Model\Entity\User|null $modifier
 * @property int $id
 * @property int|null $customer_id
 * @property string|null $text
 * @property int|null $price
 * @property \Cake\I18n\FrozenDate|null $billing_from
 * @property string|null $note
 * @property bool $active
 * @property \Cake\I18n\FrozenDate|null $billing_until
 * @property bool $separate_invoice
 * @property int|null $service_id
 * @property int $quantity
 * @property int $contract_id
 * @property int|null $fixed_discount
 * @property int|null $percentage_discount
 * @property float $sum
 * @property float $fixed_discount_sum
 * @property float $percentage_discount_sum
 * @property float $discount
 * @property float $total_price
 * @property float $vat
 * @property float $vat_base
 * @property string $style
 * @property string $name
 * @property float $period_total
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
     * @var array<string, bool>
     */
    protected $_accessible = [
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'customer_id' => true,
        'text' => true,
        'price' => true,
        'billing_from' => true,
        'note' => true,
        'billing_until' => true,
        'separate_invoice' => true,
        'service_id' => true,
        'quantity' => true,
        'contract_id' => true,
        'customer' => true,
        'service' => true,
        'contract' => true,
        'fixed_discount' => true,
        'percentage_discount' => true,
    ];

    /**
     * getter for name (use local text parameter or service name and adds number of items on front if more than one)
     *
     * @return string
     */
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

    /**
     * getter for sum of price (use local price or from service and multiply by quantity)
     *
     * @return float
     */
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

    /**
     * getter for sum of fixed discount (0 when not set)
     *
     * @return float
     */
    protected function _getFixedDiscountSum(): float
    {
        $discount = 0;

        if (isset($this->fixed_discount)) {
            $discount = $this->fixed_discount;
        }

        return $discount;
    }

    /**
     * getter for sum of percentage discount from calculated sum (0 when not set)
     *
     * @return float
     */
    protected function _getPercentageDiscountSum(): float
    {
        $discount = 0;

        if (isset($this->percentage_discount)) {
            $discount = $this->sum * $this->percentage_discount / 100;
        }

        return $discount;
    }

    /**
     * getter for sum of all discounts from calculated sum
     *
     * @return float
     */
    protected function _getDiscount(): float
    {
        return $this->fixed_discount_sum + $this->percentage_discount_sum;
    }

    /**
     * getter for total price (sum - discount)
     *
     * @return float
     */
    protected function _getTotalPrice(): float
    {
        return $this->sum - $this->discount;
    }

    /**
     * getter for vat base (total - vat)
     *
     * @return float
     */
    protected function _getVatBase(): float
    {
        return $this->total_price - $this->vat;
    }

    /**
     * getter for VAT
     *
     * @return float
     */
    protected function _getVat(): float
    {
        return round($this->total_price - ($this->total_price / (1 + $this->customer->tax_rate->vat_rate)), 2);
    }

    /**
     * getter for total price per period (calculates the price ratio within active days in the selected period)
     *
     * @param \Cake\I18n\FrozenDate $from First day of period
     * @param \Cake\I18n\FrozenDate $until Last day of period
     * @return float
     */
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
                return ceil($this->total_price);
            }
            // later billing_from
            if ($this->billing_from <= $until) {
                return ceil($this->total_price / $period_days
                    * $this->billing_from->diffInDays($until->addDay(1)));
            }
        } else { // billing_until is limiting
            // earlier billing_until
            if ($this->billing_from <= $from) {
                return ceil($this->total_price / $period_days
                    * $from->diffInDays($this->billing_until->addDay(1)));
            }
            // later billing_from and earlier billing_until
            if ($this->billing_from <= $until) {
                return ceil($this->total_price / $period_days
                    * $this->billing_from->diffInDays($this->billing_until->addDay(1)));
            }
        }

        // this should never happen
        return 0;
    }

    /**
     * getter for style
     *
     * @return string
     */
    protected function _getStyle(): string
    {
        $style = '';
        $now = new FrozenDate();

        if (isset($this->billing_from) && $this->billing_from > $now) {
            $style = 'background-color: #ffbc80;';
        }

        if (isset($this->billing_until) && $this->billing_until < $now) {
            $style = 'background-color: #bbbbbb;';
        }

        if (isset($this->contract->valid_until) && $this->contract->valid_until < $now) {
            $style = 'background-color: #ffaaaa;';
        }

        return $style;
    }

    /**
     * getter for active
     *
     * @return bool
     */
    protected function _getActive(): bool
    {
        $now = new FrozenDate();

        if (isset($this->billing_from) && $this->billing_from > $now) {
            return false;
        }

        if (isset($this->billing_until) && $this->billing_until < $now) {
            return false;
        }

        return true;
    }
}
