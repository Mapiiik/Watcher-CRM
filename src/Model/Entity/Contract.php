<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\ApiClient;
use Cake\I18n\FrozenDate;
use Cake\ORM\Entity;

/**
 * Contract Entity
 *
 * @property int $id
 * @property int $customer_id
 * @property int|null $installation_address_id
 * @property string|null $number
 * @property int $service_type_id
 * @property \Cake\I18n\FrozenTime $created
 * @property int $created_by
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $modified_by
 * @property string|null $note
 * @property \Cake\I18n\FrozenDate|null $obligation_until
 * @property bool|null $vip
 * @property int|null $installation_technician_id
 * @property int|null $brokerage_id
 * @property \Cake\I18n\FrozenDate|null $installation_date
 * @property string|null $access_description
 * @property \Cake\I18n\FrozenDate|null $valid_from
 * @property \Cake\I18n\FrozenDate|null $valid_until
 * @property \Cake\I18n\FrozenDate|null $conclusion_date
 * @property int|null $number_of_amendments
 * @property int|null $minimum_duration
 * @property int|null $activation_fee_sum
 * @property int|null $activation_fee_with_obligation_sum
 * @property string|null $number_of_the_contract_to_be_terminated
 * @property string|null $access_point_id
 * @property string $style
 * @property bool $active
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Address $installation_address
 * @property \App\Model\Entity\Address $billing_address
 * @property \App\Model\Entity\Address $delivery_address
 * @property \App\Model\Entity\Address $permanent_address
 * @property \App\Model\Entity\ServiceType $service_type
 * @property \App\Model\Entity\Customer $installation_technician
 * @property \App\Model\Entity\Brokerage $brokerage
 * @property \App\Model\Entity\Billing[] $billings
 * @property \App\Model\Entity\BorrowedEquipment[] $borrowed_equipments
 * @property \App\Model\Entity\Ip[] $ips
 * @property \App\Model\Entity\RemovedIp[] $removed_ips
 * @property \App\Model\Entity\SoldEquipment[] $sold_equipments
 * @property \App\Model\Entity\Billing[] $individual_billings
 * @property \App\Model\Entity\Billing[] $standard_billings
 * @property \Cake\ORM\Entity|null $access_point
 */
class Contract extends Entity
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
        'customer_id' => true,
        'installation_address_id' => true,
        'number' => true,
        'service_type_id' => true,
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'note' => true,
        'obligation_until' => true,
        'vip' => true,
        'installation_technician_id' => true,
        'brokerage_id' => true,
        'installation_date' => true,
        'access_description' => true,
        'valid_from' => true,
        'valid_until' => true,
        'conclusion_date' => true,
        'number_of_amendments' => true,
        'customer' => true,
        'installation_address' => true,
        'service_type' => true,
        'installation_technician' => true,
        'brokerage' => true,
        'billings' => true,
        'borrowed_equipments' => true,
        'ips' => true,
        'removed_ips' => true,
        'sold_equipments' => true,
        'activation_fee' => true,
        'activation_fee_with_obligation' => true,
        'access_point_id' => true,
        'access_point' => true,
    ];

    /**
     * getter for minumum duration of contract in months (based on valid_from a obligation_until params)
     *
     * @return int|null
     */
    protected function _getMinimumDuration(): ?int
    {
        $minimum_duration = null;

        if (isset($this->obligation_until) && ($this->valid_from < $this->obligation_until)) {
            $minimum_duration = $this->valid_from->diffInMonths($this->obligation_until->addDay(1));
        }

        return $minimum_duration;
    }

    /**
     * getter for activation_fee (local or from service_type)
     *
     * @return int|null
     */
    protected function _getActivationFeeSum(): ?int
    {
        if (isset($this->activation_fee)) {
            return $this->activation_fee;
        }

        if (isset($this->service_type->activation_fee)) {
            return $this->service_type->activation_fee;
        }

        return null;
    }

    /**
     * getter for activation_fee_with_obligation (local or from service_type)
     *
     * @return int|null
     */
    protected function _getActivationFeeWithObligationSum(): ?int
    {
        if (isset($this->activation_fee_with_obligation)) {
            return $this->activation_fee_with_obligation;
        }

        if (isset($this->service_type->activation_fee_with_obligation)) {
            return $this->service_type->activation_fee_with_obligation;
        }

        return null;
    }

    /**
     * getter for billing address from Customer object
     *
     * @return \App\Model\Entity\Address|null
     */
    protected function _getBillingAddress(): ?Address
    {
        return $this->customer->billing_address;
    }

    /**
     * getter for delivery address from Customer object
     *
     * @return \App\Model\Entity\Address|null
     */
    protected function _getDeliveryAddress(): ?Address
    {
        return $this->customer->delivery_address;
    }

    /**
     * getter for permanent address from Customer object
     *
     * @return \App\Model\Entity\Address|null
     */
    protected function _getPermanentAddress(): ?Address
    {
        return $this->customer->permanent_address;
    }

    /**
     * getter for name in format "number - service_type->name - installation_address->address"
     *
     * @return string
     */
    protected function _getName(): string
    {
        return $this->number .
            ($this->has('service_type') ? ' - ' . $this->service_type->name : '') .
            ($this->has('installation_address') ? ' - ' . $this->installation_address->address : '');
    }

    /**
     * getter for option separate_invoice (use option from service type)
     *
     * @return bool
     */
    protected function _getSeparateInvoice(): bool
    {
        if (isset($this->service_type->separate_invoice)) {
            return $this->service_type->separate_invoice;
        }

        return false;
    }

    /**
     * getter for option invoice_with_items (use option from service type)
     *
     * @return bool
     */
    protected function _getInvoiceWithItems(): bool
    {
        if (isset($this->service_type->invoice_with_items)) {
            return $this->service_type->invoice_with_items;
        }

        return false;
    }

    /**
     * getter for option invoice_text (use option from service type)
     *
     * @return string|null
     */
    protected function _getInvoiceText(): ?string
    {
        if (isset($this->service_type->invoice_text)) {
            return $this->service_type->invoice_text;
        }

        return null;
    }

    /**
     * getter for acess point (try to load via ApiClient)
     *
     * @return \Cake\ORM\Entity|null
     */
    protected function _getAccessPoint(): ?Entity
    {
        if ($this->access_point_id) {
            return ApiClient::getAccessPoint($this->access_point_id);
        }

        return null;
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

        if (isset($this->valid_until) && $this->valid_until < $now) {
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

        if (isset($this->valid_until) && $this->valid_until < $now) {
            return false;
        }

        return true;
    }
}
