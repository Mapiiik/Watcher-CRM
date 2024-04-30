<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\Date;
use Cake\ORM\Entity;
use Exception;
use PhpCollective\DecimalObject\Decimal;

/**
 * Billing Entity
 *
 * @property \Cake\I18n\DateTime|null $created
 * @property string|null $created_by
 * @property \App\Model\Entity\AppUser|null $creator
 * @property \Cake\I18n\DateTime|null $modified
 * @property string|null $modified_by
 * @property \App\Model\Entity\AppUser|null $modifier
 * @property string $id
 * @property int $nid
 * @property string $customer_id
 * @property string|null $text
 * @property \PhpCollective\DecimalObject\Decimal|null $price
 * @property \Cake\I18n\Date|null $billing_from
 * @property string|null $note
 * @property bool $active
 * @property \Cake\I18n\Date|null $billing_until
 * @property bool $separate_invoice
 * @property int|null $service_id
 * @property int $quantity
 * @property string $contract_id
 * @property \PhpCollective\DecimalObject\Decimal|null $fixed_discount
 * @property int|null $percentage_discount
 * @property \PhpCollective\DecimalObject\Decimal $sum
 * @property \PhpCollective\DecimalObject\Decimal $fixed_discount_sum
 * @property \PhpCollective\DecimalObject\Decimal $percentage_discount_sum
 * @property \PhpCollective\DecimalObject\Decimal $discount
 * @property \PhpCollective\DecimalObject\Decimal $total_price
 * @property \PhpCollective\DecimalObject\Decimal $vat
 * @property \PhpCollective\DecimalObject\Decimal $vat_base
 * @property string $style
 * @property string $name
 * @property \PhpCollective\DecimalObject\Decimal $period_total
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
    protected array $_accessible = [
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
     * @return \PhpCollective\DecimalObject\Decimal
     */
    protected function _getSum(): Decimal
    {
        $sum = Decimal::create(0, 2);

        if (isset($this->price)) {
            $sum = $this->price;
        } elseif (isset($this->service->price)) {
            $sum = $this->service->price;
        }

        $sum = $sum->multiply($this->quantity);

        return $sum;
    }

    /**
     * getter for sum of fixed discount (0 when not set)
     *
     * @return \PhpCollective\DecimalObject\Decimal
     */
    protected function _getFixedDiscountSum(): Decimal
    {
        $discount = Decimal::create(0, 2);

        if (isset($this->fixed_discount)) {
            $discount = $this->fixed_discount;
        }

        return $discount;
    }

    /**
     * getter for sum of percentage discount from calculated sum (0 when not set)
     *
     * @return \PhpCollective\DecimalObject\Decimal
     */
    protected function _getPercentageDiscountSum(): Decimal
    {
        $discount = Decimal::create(0, 2);

        if (isset($this->percentage_discount)) {
            $discount = $this->sum->multiply($this->percentage_discount)->divide(100, 4)->round(2);
        }

        return $discount;
    }

    /**
     * getter for sum of all discounts from calculated sum
     *
     * @return \PhpCollective\DecimalObject\Decimal
     */
    protected function _getDiscount(): Decimal
    {
        return $this->fixed_discount_sum->add($this->percentage_discount_sum);
    }

    /**
     * getter for total price (sum - discount)
     *
     * @return \PhpCollective\DecimalObject\Decimal
     */
    protected function _getTotalPrice(): Decimal
    {
        return $this->sum->subtract($this->discount);
    }

    /**
     * Calculate VAT from total.
     *
     * @param \PhpCollective\DecimalObject\Decimal $total Total price.
     * @param float $vat_rate VAT rate.
     * @return \PhpCollective\DecimalObject\Decimal
     */
    public static function calcVatFromTotal(Decimal $total, float $vat_rate): Decimal
    {
        return $total->subtract(
            $total->divide(1 + $vat_rate, 4)->round(2)
        );
    }

    /**
     * Calculate VAT base from total.
     *
     * @param \PhpCollective\DecimalObject\Decimal $total Total price.
     * @param float $vat_rate VAT rate.
     * @return \PhpCollective\DecimalObject\Decimal
     */
    public static function calcVatBaseFromTotal(Decimal $total, float $vat_rate): Decimal
    {
        return $total->subtract(
            self::calcVatFromTotal($total, $vat_rate)
        );
    }

    /**
     * getter for VAT
     *
     * @return \PhpCollective\DecimalObject\Decimal
     */
    protected function _getVat(): Decimal
    {
        return self::calcVatFromTotal($this->total_price, $this->customer->tax_rate->vat_rate);
    }

    /**
     * getter for vat base (total - vat)
     *
     * @return \PhpCollective\DecimalObject\Decimal
     */
    protected function _getVatBase(): Decimal
    {
        return self::calcVatBaseFromTotal($this->total_price, $this->customer->tax_rate->vat_rate);
    }

    /**
     * getter for total price per period (calculates the price ratio within active days in the selected period)
     *
     * @param \Cake\I18n\Date $from First day of period
     * @param \Cake\I18n\Date $until Last day of period
     * @return \PhpCollective\DecimalObject\Decimal
     * @throws \Exception When something goes wrong.
     */
    public function periodTotal(Date $from, Date $until): Decimal
    {
        $period_days = $from->diffInDays($until->addDays(1));

        // billing_from not set
        if (is_null($this->billing_from)) {
            return Decimal::create(0, 2);
        }

        // billing_from in future period
        if ($this->billing_from > $until) {
            return Decimal::create(0, 2);
        }

        // billing_until before period
        if (!is_null($this->billing_until) && $this->billing_until < $from) {
            return Decimal::create(0, 2);
        }

        if (is_null($this->billing_until) || (!is_null($this->billing_until) && $this->billing_until >= $until)) { // billing_until is not limiting
            // whole period
            if ($this->billing_from <= $from) {
                return $this->total_price
                    ->round(0, Decimal::ROUND_CEIL);
            }
            // later billing_from
            if ($this->billing_from <= $until) {
                return $this->total_price
                    ->multiply($this->billing_from->diffInDays($until->addDays(1)))
                    ->divide($period_days, 4)
                    ->round(0, Decimal::ROUND_CEIL);
            }
        } else { // billing_until is limiting
            // earlier billing_until
            if ($this->billing_from <= $from) {
                return $this->total_price
                    ->multiply($from->diffInDays($this->billing_until->addDays(1)))
                    ->divide($period_days, 4)
                    ->round(0, Decimal::ROUND_CEIL);
            }
            // later billing_from and earlier billing_until
            if ($this->billing_from <= $until) {
                return $this->total_price
                    ->multiply($this->billing_from->diffInDays($this->billing_until->addDays(1)))
                    ->divide($period_days, 4)
                    ->round(0, Decimal::ROUND_CEIL);
            }
        }

        // This should never happen :-)
        throw new Exception('This should never happen :-)');
    }

    /**
     * getter for style
     *
     * @return string
     */
    protected function _getStyle(): string
    {
        $style = '';
        $now = Date::now();

        if (isset($this->billing_from) && $this->billing_from > $now) {
            $style = 'color: darkorange;';
        }

        if (isset($this->billing_until) && $this->billing_until < $now) {
            $style = 'color: darkgray; text-decoration: line-through;';
        }

        if (isset($this->contract->style)) {
            $style .= ' ' . $this->contract->style;
        }

        return $style;
    }

    /**
     * getter for active
     *
     * @return bool
     * @throws \Exception When contract data not available.
     */
    protected function _getActive(): bool
    {
        $now = Date::now();

        if (isset($this->billing_from) && $this->billing_from > $now) {
            return false;
        }

        if (isset($this->billing_until) && $this->billing_until < $now) {
            return false;
        }

        if (isset($this->contract)) {
            return $this->contract->billed;
        }

        throw new Exception(__('Contract data not available.'));
    }

    /**
     * getter for option separate_invoice
     *
     * @return bool
     */
    public function isSeparateInvoice(): bool
    {
        return $this->separate_invoice;
    }
}
