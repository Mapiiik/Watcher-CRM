<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Customer Entity
 *
 * @property int $id
 * @property int $dealer
 * @property string|null $title
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $suffix
 * @property string|null $company
 * @property int $taxe_id
 * @property string|null $bank_name
 * @property string|null $bank_account
 * @property string|null $bank_code
 * @property int $modified_by
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int $created_by
 * @property \Cake\I18n\FrozenTime|null $created
 * @property string|null $ic
 * @property string|null $dic
 * @property string|null $www
 * @property string|null $internal_note
 * @property bool|null $invoice_delivery_type
 * @property string|null $note
 * @property string|null $identity_card_number
 * @property \Cake\I18n\FrozenDate|null $date_of_birth
 * @property \Cake\I18n\FrozenDate|null $termination_date
 * @property bool|null $agree_gdpr
 * @property bool|null $agree_mailing_outages
 * @property bool|null $agree_mailing_commercial
 * @property bool|null $agree_mailing_billing
 *
 * @property \App\Model\Entity\Tax $tax
 * @property \App\Model\Entity\Address[] $addresses
 * @property \App\Model\Entity\Billing[] $billings
 * @property \App\Model\Entity\BorrowedEquipment[] $borrowed_equipments
 * @property \App\Model\Entity\Contract[] $contracts
 * @property \App\Model\Entity\Email[] $emails
 * @property \App\Model\Entity\Ip[] $ips
 * @property \App\Model\Entity\LabelCustomer[] $label_customers
 * @property \App\Model\Entity\Login[] $logins
 * @property \App\Model\Entity\Phone[] $phones
 * @property \App\Model\Entity\RemovedIp[] $removed_ips
 * @property \App\Model\Entity\SoldEquipment[] $sold_equipments
 * @property \App\Model\Entity\Task[] $tasks
 *
 * @property string $full_name
 * @property string $name
 */
class Customer extends Entity
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
        'dealer' => true,
        'title' => true,
        'first_name' => true,
        'last_name' => true,
        'suffix' => true,
        'company' => true,
        'taxe_id' => true,
        'bank_name' => true,
        'bank_account' => true,
        'bank_code' => true,
        'modified_by' => true,
        'modified' => true,
        'created_by' => true,
        'created' => true,
        'ic' => true,
        'dic' => true,
        'www' => true,
        'internal_note' => true,
        'invoice_delivery_type' => true,
        'note' => true,
        'identity_card_number' => true,
        'date_of_birth' => true,
        'termination_date' => true,
        'agree_gdpr' => true,
        'agree_mailing_outages' => true,
        'agree_mailing_commercial' => true,
        'agree_mailing_billing' => true,
        'tax' => true,
        'addresses' => true,
        'billings' => true,
        'borrowed_equipments' => true,
        'contracts' => true,
        'emails' => true,
        'ips' => true,
        'label_customers' => true,
        'logins' => true,
        'phones' => true,
        'removed_ips' => true,
        'sold_equipments' => true,
        'tasks' => true,
    ];

    protected function _getFullName(): string
    {
        $name = '';
	
	if (isset($this->title)) {
            if ($name <> '') $name .= " ";
            $name .= $this->title;
        }
        if (isset($this->first_name)) {
            if ($name <> '') $name .= " ";
            $name .= $this->first_name;
        }
        if (isset($this->last_name)) {
            if ($name <> '') $name .= " ";
            $name .= $this->last_name;
        }
	if (isset($this->suffix)) {
            if ($name <> '') $name .= " ";
            $name .= $this->suffix;
        }

        return $name;
    }
    
    protected function _getName(): string
    {
        $name = '';
	
        if (isset($this->company)) $name .= "[" . $this->company . "]";
        if ($this->full_name <> '') {
            if ($name <> '') $name .= " ";
            $name .= $this->full_name;
        }

        return $name;
    }

    protected function _getNumber(): string
    {
        $number = strval($this->id + env('CUSTOMER_SERIES', 0));
	
        return $number;
    }
    
    protected function _getEmail(): string
    {
        $email = implode(', ', array_column($this->emails, 'email'));
        return $email;
    }

    protected function _getPhone(): string
    {
        $phone = implode(', ', array_column($this->phones, 'phone'));
        return $phone;
    }

    protected function _getInstallationAddress(): ?Address
    {
        $installation_address = null;
        
        // take last installation address
        foreach ($this->addresses as $address)
        {
            if ($address->type == 0)
            {
                $installation_address = $address;
            }
        }
        
        return $installation_address;
    }        
    
    protected function _getBillingAddress(): ?Address
    {
        $billing_address = null;
        
        // take last billing address
        foreach ($this->addresses as $address)
        {
            if ($address->type == 1)
            {
                $billing_address = $address;
            }
        }
        
        // if there is no billing address take permanent address
        if (!isset($billing_address) && isset($this->permanent_address)) $billing_address = $this->permanent_address;

        // if there is no billing address take installation address
        if (!isset($billing_address) && isset($this->installation_address)) $billing_address = $this->installation_address;

        return $billing_address;
    }        

    protected function _getDeliveryAddress(): ?Address
    {
        $delivery_address = null;
        
        // take last delivery address
        foreach ($this->addresses as $address)
        {
            if ($address->type == 2)
            {
                $delivery_address = $address;
            }
        }
        
        return $delivery_address;
    }        

    protected function _getPermanentAddress(): ?Address
    {
        $permanent_address = null;
        
        // take last permanent address
        foreach ($this->addresses as $address)
        {
            if ($address->type == 3)
            {
                $permanent_address = $address;
            }
        }
        
        return $permanent_address;
    }        
    
    function _getIcVerified()
    {
        $ic = $this->ic;
        
        // be liberal in what you receive
        $ic = preg_replace('#\s+#', '', $ic);

        // má požadovaný tvar?
        if (!preg_match('#^\d{8}$#', $ic)) {
            return false;
        }

        // kontrolní součet
        $a = 0;
        for ($i = 0; $i < 7; $i++) {
            $a += $ic[$i] * (8 - $i);
        }

        $a = $a % 11;
        if ($a === 0) {
            $c = 1;
        } elseif ($a === 1) {
            $c = 0;
        } else {
            $c = 11 - $a;
        }

        return (int) $ic[7] === $c;
    }
}
