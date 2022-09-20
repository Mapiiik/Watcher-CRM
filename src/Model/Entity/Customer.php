<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Customer Entity
 *
 * @property \Cake\I18n\FrozenTime|null $created
 * @property int|null $created_by
 * @property \CakeDC\Users\Model\Entity\User|null $creator
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $modified_by
 * @property \CakeDC\Users\Model\Entity\User|null $modifier
 * @property int $id
 * @property int $dealer
 * @property string|null $title
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $suffix
 * @property string|null $company
 * @property int $tax_rate_id
 * @property string|null $bank_name
 * @property string|null $bank_account
 * @property string|null $bank_code
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
 * @property string $email
 * @property string $phone
 * @property string $number
 * @property bool $ic_verified
 *
 * @property \App\Model\Entity\TaxRate $tax_rate
 * @property \App\Model\Entity\Address[] $addresses
 * @property \App\Model\Entity\Billing[] $billings
 * @property \App\Model\Entity\Address $billing_address
 * @property \App\Model\Entity\Address $delivery_address
 * @property \App\Model\Entity\Address $permanent_address
 * @property \App\Model\Entity\BorrowedEquipment[] $borrowed_equipments
 * @property \App\Model\Entity\Contract[] $contracts
 * @property \App\Model\Entity\Email[] $emails
 * @property \App\Model\Entity\Email[] $billing_emails
 * @property \App\Model\Entity\Ip[] $ips
 * @property \App\Model\Entity\RemovedIp[] $removed_ips
 * @property \App\Model\Entity\IpNetwork[] $ip_networks
 * @property \App\Model\Entity\RemovedIpNetwork[] $removed_ip_networks
 * @property \App\Model\Entity\CustomerLabel[] $customer_labels
 * @property \App\Model\Entity\Login[] $logins
 * @property \App\Model\Entity\Phone[] $phones
 * @property \App\Model\Entity\SoldEquipment[] $sold_equipments
 * @property \App\Model\Entity\Task[] $tasks
 *
 * @property string $full_name
 * @property string $name
 * @property string $name_for_lists
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
     * @var array<string, bool>
     */
    protected $_accessible = [
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'dealer' => true,
        'title' => true,
        'first_name' => true,
        'last_name' => true,
        'suffix' => true,
        'company' => true,
        'tax_rate_id' => true,
        'bank_name' => true,
        'bank_account' => true,
        'bank_code' => true,
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
        'tax_rate' => true,
        'addresses' => true,
        'billings' => true,
        'borrowed_equipments' => true,
        'contracts' => true,
        'emails' => true,
        'ips' => true,
        'customer_labels' => true,
        'logins' => true,
        'phones' => true,
        'removed_ips' => true,
        'sold_equipments' => true,
        'tasks' => true,
    ];

    /**
     * getter for full name of person
     *
     * @return string
     */
    protected function _getFullName(): string
    {
        $name = '';

        if (isset($this->title)) {
            $name .= $this->title;
        }
        if (isset($this->first_name)) {
            if ($name <> '') {
                $name .= ' ';
            }
            $name .= $this->first_name;
        }
        if (isset($this->last_name)) {
            if ($name <> '') {
                $name .= ' ';
            }
            $name .= $this->last_name;
        }
        if (isset($this->suffix)) {
            if ($name <> '') {
                $name .= ' ';
            }
            $name .= $this->suffix;
        }

        return $name;
    }

    /**
     * getter for full name with company
     *
     * @return string
     */
    protected function _getName(): string
    {
        $name = '';

        if (isset($this->company)) {
            $name .= '[' . $this->company . ']';
        }
        if ($this->full_name <> '') {
            if ($name <> '') {
                $name .= ' ';
            }
            $name .= $this->full_name;
        }

        return $name;
    }

    /**
     * getter for full name with company and with customer number for lists
     *
     * @return string
     */
    protected function _getNameForLists(): string
    {
        $name = '';

        if (isset($this->company)) {
            $name .= '[' . $this->company . ']';
        }
        if (isset($this->last_name)) {
            if ($name <> '') {
                $name .= ' ';
            }
            $name .= $this->last_name;
        }
        if (isset($this->first_name)) {
            if ($name <> '') {
                $name .= ' ';
            }
            $name .= $this->first_name;
        }
        if (isset($this->title)) {
            if ($name <> '') {
                $name .= ', ';
            }
            $name .= $this->title;
        }
        if (isset($this->suffix)) {
            if ($name <> '') {
                $name .= ', ';
            }
            $name .= $this->suffix;
        }

        return $name . ' (' . $this->number . ')';
    }

    /**
     * getter for customer number
     *
     * @return string
     */
    protected function _getNumber(): string
    {
        $number = strval($this->id + (int)env('CUSTOMER_SERIES', '0'));

        return $number;
    }

    /**
     * all customer emails separated by commas
     *
     * @return string
     */
    protected function _getEmail(): string
    {
        $email = implode(', ', array_column($this->emails, 'email'));

        return $email;
    }

    /**
     * all customer emails for billing
     *
     * @return array
     */
    protected function _getBillingEmails(): array
    {
        $billing_emails = [];
        foreach ($this->emails as $email) {
            if ($email->use_for_billing) {
                $billing_emails[] = $email;
            }
        }

        return $billing_emails;
    }

    /**
     * all customer emails for billing separated by commas
     *
     * @return string
     */
    protected function _getBillingEmail(): string
    {
        $email = implode(', ', array_column($this->billing_emails, 'email'));

        return $email;
    }

    /**
     * all customer phones separated by commas
     *
     * @return string
     */
    protected function _getPhone(): string
    {
        $phone = implode(', ', array_column($this->phones, 'phone'));

        return $phone;
    }

    /**
     * get last installation address
     *
     * @return \App\Model\Entity\Address|null
     */
    protected function _getInstallationAddress(): ?Address
    {
        $installation_address = null;

        // take last installation address
        foreach ($this->addresses as $address) {
            if ($address->type == 0) {
                $installation_address = $address;
            }
        }

        return $installation_address;
    }

    /**
     * get last billing address or alternative for billing
     *
     * @return \App\Model\Entity\Address|null
     */
    protected function _getBillingAddress(): ?Address
    {
        $billing_address = null;

        // take last billing address
        foreach ($this->addresses as $address) {
            if ($address->type == 1) {
                $billing_address = $address;
            }
        }

        // if there is no billing address take permanent address
        if (!isset($billing_address) && isset($this->permanent_address)) {
            $billing_address = $this->permanent_address;
        }

        // if there is no billing address take installation address
        if (!isset($billing_address) && isset($this->installation_address)) {
            $billing_address = $this->installation_address;
        }

        return $billing_address;
    }

    /**
     * get last delivery address
     *
     * @return \App\Model\Entity\Address|null
     */
    protected function _getDeliveryAddress(): ?Address
    {
        $delivery_address = null;

        // take last delivery address
        foreach ($this->addresses as $address) {
            if ($address->type == 2) {
                $delivery_address = $address;
            }
        }

        return $delivery_address;
    }

    /**
     * get last permanent address
     *
     * @return \App\Model\Entity\Address|null
     */
    protected function _getPermanentAddress(): ?Address
    {
        $permanent_address = null;

        // take last permanent address
        foreach ($this->addresses as $address) {
            if ($address->type == 3) {
                $permanent_address = $address;
            }
        }

        return $permanent_address;
    }

    /**
     * get verification of identification number (citizen/company ID)
     *
     * @return bool
     */
    protected function _getIcVerified(): bool
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
            $a += (int)$ic[$i] * (8 - $i);
        }

        $a = $a % 11;
        if ($a === 0) {
            $c = 1;
        } elseif ($a === 1) {
            $c = 0;
        } else {
            $c = 11 - $a;
        }

        return (int)$ic[7] === $c;
    }

    /**
     * Get dealer state options method
     *
     * @return array<int, string>
     */
    public function getDealerStateOptions(): array
    {
        return [
            0 => __('No'),
            1 => __('Yes') . ' (' . __('current') . ')',
            2 => __('Yes') . ' (' . __('former') . ')',
        ];
    }

    /**
     * Get dealer state method
     *
     * @return string
     */
    public function getDealerState(): string
    {
        return $this->getDealerStateOptions()[$this->dealer];
    }
}
