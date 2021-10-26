<?php
declare(strict_types=1);

namespace App\Model\Entity;

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
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Address $installation_address
 * @property \App\Model\Entity\ServiceType $service_type
 * @property \App\Model\Entity\Customer $installation_technician
 * @property \App\Model\Entity\Brokerage $brokerage
 * @property \App\Model\Entity\Billing[] $billings
 * @property \App\Model\Entity\BorrowedEquipment[] $borrowed_equipments
 * @property \App\Model\Entity\Ip[] $ips
 * @property \App\Model\Entity\RemovedIp[] $removed_ips
 * @property \App\Model\Entity\SoldEquipment[] $sold_equipments
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
     * @var array
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
    ];

    protected function _getMinimumDuration(): ?int
    {
        $minimum_duration = null;
        
        if (isset($this->obligation_until) && ($this->valid_from < $this->obligation_until))
        {
            $minimum_duration = $this->valid_from->diffInMonths($this->obligation_until->addDay(1));
        }
        
        return $minimum_duration;
    }
    
    protected function _getActivationFeeSum(): ?int
    {
        if (isset($this->activation_fee)) {
            return $this->activation_fee;
        }

        if (isset($this->service_type->activation_fee)) {
            return $this->service_type->activation_fee;
        }
    }

    protected function _getActivationFeeWithObligationSum(): ?int
    {
        if (isset($this->activation_fee_with_obligation)) {
            return $this->activation_fee_with_obligation;
        }

        if (isset($this->service_type->activation_fee_with_obligation)) {
            return $this->service_type->activation_fee_with_obligation;
        }
    }

    protected function _getBillingAddress(): ?Address
    {
        return $this->customer->billing_address;
    }

    protected function _getDeliveryAddress(): ?Address
    {
        return $this->customer->delivery_address;
    }    

    protected function _getPermanentAddress(): ?Address
    {
        return $this->customer->permanent_address;
    }

    protected function _getSeparateInvoice(): bool
    {
        return true;
    }        
}
