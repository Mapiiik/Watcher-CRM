<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\ApiClient;
use Cake\ORM\Entity;
use Exception;

/**
 * Contract Entity
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
 * @property int|null $installation_address_id
 * @property string|null $number
 * @property string|null $subscriber_verification_code
 * @property int $service_type_id
 * @property string|null $note
 * @property bool|null $vip
 * @property int|null $installation_technician_id
 * @property int|null $uninstallation_technician_id
 * @property int|null $commission_id
 * @property \Cake\I18n\Date|null $installation_date
 * @property \Cake\I18n\Date|null $uninstallation_date
 * @property \Cake\I18n\Date|null $termination_date
 * @property string|null $access_description
 * @property int|null $activation_fee
 * @property int|null $activation_fee_with_obligation
 * @property int|null $activation_fee_sum
 * @property int|null $activation_fee_with_obligation_sum
 * @property string|null $number_of_the_contract_to_be_terminated
 * @property string|null $access_point_id
 * @property string $contract_state_id
 * @property string $name
 * @property string $style
 * @property bool $active_services
 * @property bool $billed
 * @property bool $blocked
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Address $installation_address
 * @property \App\Model\Entity\Address $billing_address
 * @property \App\Model\Entity\Address $delivery_address
 * @property \App\Model\Entity\Address $permanent_address
 * @property \App\Model\Entity\ServiceType $service_type
 * @property \App\Model\Entity\Customer $installation_technician
 * @property \App\Model\Entity\Customer $uninstallation_technician
 * @property \App\Model\Entity\Commission $commission
 * @property \App\Model\Entity\ContractState $contract_state
 * @property \App\Model\Entity\Billing[] $billings
 * @property \App\Model\Entity\BorrowedEquipment[] $borrowed_equipments
 * @property \App\Model\Entity\ContractVersion[] $contract_versions
 * @property \App\Model\Entity\Ip[] $ips
 * @property \App\Model\Entity\RemovedIp[] $removed_ips
 * @property \App\Model\Entity\IpNetwork[] $ip_networks
 * @property \App\Model\Entity\RemovedIpNetwork[] $removed_ip_networks
 * @property \App\Model\Entity\SoldEquipment[] $sold_equipments
 * @property \App\Model\Entity\Task[] $tasks
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
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'customer_id' => true,
        'installation_address_id' => true,
        'number' => true,
        'subscriber_verification_code' => true,
        'service_type_id' => true,
        'note' => true,
        'vip' => true,
        'installation_technician_id' => true,
        'uninstallation_technician_id' => true,
        'commission_id' => true,
        'installation_date' => true,
        'uninstallation_date' => true,
        'termination_date' => true,
        'access_description' => true,
        'activation_fee' => true,
        'activation_fee_with_obligation' => true,
        'access_point_id' => true,
        'contract_state_id' => true,
        'creator' => true,
        'modifier' => true,
        'customer' => true,
        'installation_address' => true,
        'service_type' => true,
        'installation_technician' => true,
        'uninstallation_technician' => true,
        'commission' => true,
        'contract_state' => true,
        'billings' => true,
        'borrowed_equipments' => true,
        'contract_versions' => true,
        'ips' => true,
        'removed_ips' => true,
        'ip_networks' => true,
        'removed_ip_networks' => true,
        'sold_equipments' => true,
        'tasks' => true,
    ];

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
            ($this->__isset('service_type') ? ' - ' . $this->service_type->name : '') .
            ($this->__isset('installation_address') ? ' - ' . $this->installation_address->address : '');
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

        if (isset($this->contract_state)) {
            $style = 'background-color: ' . $this->contract_state->color . ';';
        }

        return $style;
    }

    /**
     * getter for active_services
     *
     * @return bool
     * @throws \Exception When contract state data not available.
     */
    protected function _getActive(): bool
    {
        if (isset($this->contract_state)) {
            return $this->contract_state->active_services;
        }

        throw new Exception(__('Contract state data not available.'));
    }

    /**
     * getter for billed
     *
     * @return bool
     * @throws \Exception When contract state data not available.
     */
    protected function _getBilled(): bool
    {
        if (isset($this->contract_state)) {
            return $this->contract_state->billed;
        }

        throw new Exception(__('Contract state data not available.'));
    }

    /**
     * getter for blocked
     *
     * @return bool
     * @throws \Exception When contract state data not available.
     */
    protected function _getBlocked(): bool
    {
        if (isset($this->contract_state)) {
            return $this->contract_state->blocked;
        }

        throw new Exception(__('Contract state data not available.'));
    }

    /**
     * getter for option separate_invoice (use option from service type)
     *
     * @return bool
     */
    public function isSeparateInvoice(): bool
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
    public function isInvoiceWithItems(): bool
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
    public function getInvoiceText(): ?string
    {
        if (isset($this->service_type->invoice_text)) {
            return $this->service_type->invoice_text;
        }

        return null;
    }
}
