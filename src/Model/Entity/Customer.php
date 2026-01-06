<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Enum\AddressType;
use Cake\ORM\Entity;
use RuntimeException;

/**
 * Customer Entity
 *
 * @property \Cake\I18n\DateTime|null $created
 * @property string|null $created_by
 * @property \App\Model\Entity\AppUser|null $creator
 * @property \Cake\I18n\DateTime|null $modified
 * @property string|null $modified_by
 * @property \App\Model\Entity\AppUser|null $modifier
 * @property string $id
 * @property int $nid
 * @property \App\Model\Enum\CustomerDealer $dealer
 * @property string|null $title
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $suffix
 * @property string|null $company
 * @property int $accounting_profile_id
 * @property string|null $bank_name
 * @property string|null $bank_account
 * @property string|null $bank_code
 * @property string|null $identity_number
 * @property string|null $vat_number
 * @property string|null $www
 * @property string|null $internal_note
 * @property \App\Model\Enum\CustomerInvoiceDeliveryType $invoice_delivery_type
 * @property string|null $note
 * @property string|null $identity_card_number
 * @property \Cake\I18n\Date|null $date_of_birth
 * @property int $individual_maturity_period
 * @property bool|null $agree_gdpr
 * @property bool|null $agree_mailing_outages
 * @property bool|null $agree_mailing_commercial
 * @property bool|null $agree_mailing_billing
 * @property string $email
 * @property string $billing_email
 * @property string $phone
 * @property string $billing_phone
 * @property string $number
 * @property bool $active_services
 * @property bool $billed
 *
 * @property \App\Model\Entity\AccountingProfile $accounting_profile
 * @property \App\Model\Entity\Address[] $addresses
 * @property \App\Model\Entity\Billing[] $billings
 * @property \App\Model\Entity\Address $installation_address
 * @property \App\Model\Entity\Address $billing_address
 * @property \App\Model\Entity\Address $delivery_address
 * @property \App\Model\Entity\Address $permanent_address
 * @property \App\Model\Entity\BorrowedEquipment[] $borrowed_equipments
 * @property \App\Model\Entity\Contract[] $contracts
 * @property \App\Model\Entity\Email[] $emails
 * @property \App\Model\Entity\Email[] $billing_emails
 * @property \App\Model\Entity\IpAddress[] $ip_addresses
 * @property \App\Model\Entity\RemovedIpAddress[] $removed_ip_addresses
 * @property \App\Model\Entity\IpNetwork[] $ip_networks
 * @property \App\Model\Entity\RemovedIpNetwork[] $removed_ip_networks
 * @property \App\Model\Entity\CustomerLabel[] $customer_labels
 * @property \App\Model\Entity\Login[] $logins
 * @property \App\Model\Entity\Phone[] $phones
 * @property \App\Model\Entity\Phone[] $billing_phones
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
    protected array $_accessible = [
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
        'accounting_profile_id' => true,
        'bank_name' => true,
        'bank_account' => true,
        'bank_code' => true,
        'identity_number' => true,
        'vat_number' => true,
        'www' => true,
        'internal_note' => true,
        'invoice_delivery_type' => true,
        'note' => true,
        'identity_card_number' => true,
        'date_of_birth' => true,
        'individual_maturity_period' => true,
        'agree_gdpr' => true,
        'agree_mailing_outages' => true,
        'agree_mailing_commercial' => true,
        'agree_mailing_billing' => true,
        'accounting_profile' => true,
        'addresses' => true,
        'billings' => true,
        'borrowed_equipments' => true,
        'contracts' => true,
        'emails' => true,
        'ip_addresses' => true,
        'customer_labels' => true,
        'logins' => true,
        'phones' => true,
        'removed_ip_addresses' => true,
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
        $number = strval($this->nid + (int)env('CUSTOMER_SERIES', '0'));

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
     * @return array<\App\Model\Entity\Email>
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
     * all customer phones for billing
     *
     * @return array<\App\Model\Entity\Phone>
     */
    protected function _getBillingPhones(): array
    {
        $billing_phones = [];
        foreach ($this->phones as $phone) {
            if ($phone->use_for_billing) {
                $billing_phones[] = $phone;
            }
        }

        return $billing_phones;
    }

    /**
     * all customer phones for billing separated by commas
     *
     * @return string
     */
    protected function _getBillingPhone(): string
    {
        $phone = implode(', ', array_column($this->billing_phones, 'phone'));

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
            if ($address->type == AddressType::Installation) {
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
            if ($address->type == AddressType::Billing) {
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
            if ($address->type == AddressType::Delivery) {
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
            if ($address->type == AddressType::Permanent) {
                $permanent_address = $address;
            }
        }

        return $permanent_address;
    }

    /**
     * Verify Identification Number (Citizen/Company ID)
     *
     * will verify Czech ID and Croatian OIB
     *
     * @return bool Returns true when the number is valid, false otherwise
     */
    public function verifyIdentityNumber(): bool
    {
        $identityNumber = (string)$this->identity_number;

        return $this->verifyIdentityNumberCzech($identityNumber)
            || $this->verifyIdentityNumberCroatian($identityNumber);
    }

    /**
     * Verify Czech Identification Number (Citizen/Company ID)
     *
     * @param string $ic Czech Identification Number
     * @return bool
     */
    public function verifyIdentityNumberCzech(string $ic): bool
    {
        // normalize input – remove any whitespace
        $ic = preg_replace('/\s+/', '', $ic);

        // must be exactly 8 digits
        if (!preg_match('/^\d{8}$/', $ic)) {
            return false;
        }

        // calculate checksum using weights 8–2 for the first 7 digits
        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $sum += (int)$ic[$i] * (8 - $i);
        }

        // determine check digit based on modulo 11
        $mod = $sum % 11;
        $checkDigit = match ($mod) {
            0 => 1,
            1 => 0,
            default => 11 - $mod,
        };

        // last digit must equal the calculated check digit
        return (int)$ic[7] === $checkDigit;
    }

    /**
     * Verify Croatian OIB (Personal Identification Number)
     *
     * @param string $oib Croatian OIB (11 digits)
     * @return bool
     */
    public function verifyIdentityNumberCroatian(string $oib): bool
    {
        // normalize input – remove whitespace
        $oib = preg_replace('/\s+/', '', $oib);

        // must be exactly 11 digits
        if (!preg_match('/^\d{11}$/', $oib)) {
            return false;
        }

        // ISO 7064 Mod 11,10 algorithm
        $control = 10;
        for ($i = 0; $i < 10; $i++) {
            $digit = (int)$oib[$i];
            $control = ($control + $digit) % 10;
            if ($control === 0) {
                $control = 10;
            }
            $control = ($control * 2) % 11;
        }

        $checkDigit = 11 - $control;
        if ($checkDigit === 10) {
            $checkDigit = 0;
        }

        // last digit must equal the calculated check digit
        return (int)$oib[10] === $checkDigit;
    }

    /**
     * getter for active_services
     *
     * @return bool
     * @throws \RuntimeException When contracts data not available.
     */
    protected function _getActiveServices(): bool
    {
        if (isset($this->contracts) && is_array($this->contracts)) {
            foreach ($this->contracts as $contract) {
                if ($contract->active_services === true) {
                    // contract with active services found
                    return true;
                }
            }
            // contract with active services not found
            return false;
        }

        throw new RuntimeException(__('Contracts data not available.'));
    }

    /**
     * getter for billed
     *
     * @return bool
     * @throws \RuntimeException When contracts data not available.
     */
    protected function _getBilled(): bool
    {
        if (isset($this->contracts) && is_array($this->contracts)) {
            foreach ($this->contracts as $contract) {
                if ($contract->billed === true) {
                    // billed contract found
                    return true;
                }
            }
            // billed contract not found
            return false;
        }

        throw new RuntimeException(__('Contracts data not available.'));
    }

    /**
     * Returns whether this customer's accounting profile uses invoices with items.
     *
     * @return bool
     * @throws \RuntimeException When accounting profile data not available.
     */
    public function isInvoiceWithItems(): bool
    {
        if ($this->has('accounting_profile')) {
            return $this->accounting_profile->invoice_with_items;
        }

        throw new RuntimeException(__('Accounting profile data not available.'));
    }
}
