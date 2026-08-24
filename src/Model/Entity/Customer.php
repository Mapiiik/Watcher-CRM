<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\BusinessRegister\IdentityNumber;
use App\BusinessRegister\IdentityNumberCheck;
use App\BusinessRegister\PortalLinks;
use App\BusinessRegister\Registry;
use App\BusinessRegister\VatNumberCheck;
use App\Model\Enum\AddressType;
use Cake\Core\Configure;
use RuntimeException;

/**
 * Customer Entity
 *
 * @property string $id
 * @property int $nid
 * @property \App\Model\Enum\CustomerDealer $dealer
 * @property string|null $title
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $suffix
 * @property string|null $company
 * @property int $accounting_profile_id
 * @property bool $sync_to_accounting
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
 * @property \App\Model\Enum\BillingAddressProblem|null $billing_address_problem
 *
 * @property \App\Model\Entity\AccountingProfile $accounting_profile
 * @property \App\Model\Entity\Address[] $addresses
 * @property \App\Model\Entity\Billing[] $billings
 * @property \App\Model\Entity\Address|null $installation_address
 * @property \App\Model\Entity\Address|null $billing_address
 * @property \App\Model\Entity\Address|null $delivery_address
 * @property \App\Model\Entity\Address|null $permanent_address
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
class Customer extends AppEntity
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
        'sync_to_accounting' => true,
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
        return implode(' ', array_filter([
            $this->title,
            $this->first_name,
            $this->last_name,
            $this->suffix,
        ]));
    }

    /**
     * getter for full name with company
     *
     * @return string
     */
    protected function _getName(): string
    {
        return implode(' ', array_filter([
            !empty($this->company) ? '[' . $this->company . ']' : null,
            $this->full_name,
        ]));
    }

    /**
     * getter for full name with company and with customer number for lists
     *
     * @return string
     */
    protected function _getNameForLists(): string
    {
        // Company and the person's name form one block, the degrees follow it
        // behind a comma.
        $name = implode(', ', array_filter([
            implode(' ', array_filter([
                !empty($this->company) ? '[' . $this->company . ']' : null,
                $this->last_name,
                $this->first_name,
            ])),
            $this->title,
            $this->suffix,
        ]));

        return $name . ' (' . $this->number . ')';
    }

    /**
     * getter for customer number
     *
     * @return string
     */
    protected function _getNumber(): string
    {
        return strval($this->nid + (int)Configure::read('Customers.series'));
    }

    /**
     * all customer emails separated by commas
     *
     * @return string
     */
    protected function _getEmail(): string
    {
        return implode(', ', array_column($this->emails, 'email'));
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
        return implode(', ', array_column($this->billing_emails, 'email'));
    }

    /**
     * all customer phones separated by commas
     *
     * @return string
     */
    protected function _getPhone(): string
    {
        return implode(', ', array_column($this->phones, 'phone'));
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
        return implode(', ', array_column($this->billing_phones, 'phone'));
    }

    /**
     * get last address of the given type
     *
     * @param \App\Model\Enum\AddressType $type The type to look for.
     * @return \App\Model\Entity\Address|null
     */
    private function lastAddressOfType(AddressType $type): ?Address
    {
        $found = null;

        foreach ($this->addresses as $address) {
            if ($address->type == $type) {
                $found = $address;
            }
        }

        return $found;
    }

    /**
     * get last installation address
     *
     * @return \App\Model\Entity\Address|null
     */
    protected function _getInstallationAddress(): ?Address
    {
        return $this->lastAddressOfType(AddressType::Installation);
    }

    /**
     * get last billing address or alternative for billing
     *
     * Falls through {@see \App\Model\Enum\AddressType::billingFallback()} and takes the
     * first type the customer has an address of. Where a type has more than one, the last
     * one wins, which is arbitrary - `AddressCheckRegistry` reports those customers so
     * somebody can say which address is meant.
     *
     * @return \App\Model\Entity\Address|null
     */
    protected function _getBillingAddress(): ?Address
    {
        foreach (AddressType::billingFallback() as $type) {
            $address = $this->lastAddressOfType($type);

            if ($address !== null) {
                return $address;
            }
        }

        return null;
    }

    /**
     * get last delivery address
     *
     * @return \App\Model\Entity\Address|null
     */
    protected function _getDeliveryAddress(): ?Address
    {
        return $this->lastAddressOfType(AddressType::Delivery);
    }

    /**
     * get last permanent address
     *
     * @return \App\Model\Entity\Address|null
     */
    protected function _getPermanentAddress(): ?Address
    {
        return $this->lastAddressOfType(AddressType::Permanent);
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
        return IdentityNumber::isValid($this->identity_number);
    }

    /**
     * Verify Czech Identification Number (Citizen/Company ID)
     *
     * @param string $ic Czech Identification Number
     * @return bool
     */
    public function verifyIdentityNumberCzech(string $ic): bool
    {
        return IdentityNumber::isValidCzech($ic);
    }

    /**
     * Verify Croatian OIB (Personal Identification Number)
     *
     * @param string $oib Croatian OIB (11 digits)
     * @return bool
     */
    public function verifyIdentityNumberCroatian(string $oib): bool
    {
        return IdentityNumber::isValidCroatian($oib);
    }

    /**
     * Whether a register calling the customer this is calling them what the CRM does.
     *
     * A number that checks out but belongs to somebody else is a mistake no check digit can
     * catch, and the register's name is what shows it - but only if the two are read for what
     * they say. Case, spacing and the dots a legal form is written with differ between one
     * register and another without the company being a different one.
     *
     * The name is measured against both what the customer trades as and what they are called,
     * because a register writes a company where the CRM holds a company and a person where it
     * holds a person.
     *
     * @param string|null $registerName The name as a register wrote it, null when it named none.
     * @return bool
     */
    public function isKnownAs(?string $registerName): bool
    {
        $registerName = self::comparableName($registerName);
        if ($registerName === '') {
            // a register that named nobody disagrees with nothing
            return true;
        }

        return in_array($registerName, array_filter([
            self::comparableName($this->company),
            self::comparableName($this->full_name),
        ]), true);
    }

    /**
     * A name reduced to what it says, so that two ways of writing one name read alike.
     *
     * @param string|null $name The name as it was written.
     * @return string
     */
    private static function comparableName(?string $name): string
    {
        // punctuation is how a legal form is abbreviated, not what tells two companies apart
        $name = preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower(trim((string)$name)));

        return trim((string)$name);
    }

    /**
     * Where the identification number can be looked up by hand, null when it is not a number any
     * register named here takes.
     *
     * @return string|null
     */
    public function identityNumberPortalUrl(): ?string
    {
        return PortalLinks::forIdentityNumber($this->identity_number);
    }

    /**
     * What the business registers say about the identification number, null when none of them
     * could say - none is configured, or the one that covers the number could not be reached.
     *
     * A number that fails its own check digit is not asked about at all: no register holds it,
     * and `verifyIdentityNumber()` already says so without anyone being asked.
     *
     * This reaches a register over the network. Answers are kept for as long as the
     * `business_register` cache says, so asking about the same customer again is cheap - but a
     * listing that asked for every row would still be a request per row on a cold cache.
     *
     * @return \App\BusinessRegister\IdentityNumberCheck|null
     */
    public function identityNumberCheck(): ?IdentityNumberCheck
    {
        if (!$this->verifyIdentityNumber()) {
            return null;
        }

        return Registry::identityNumberCheck($this->identity_number);
    }

    /**
     * What the business registers say about the VAT number, null when none of them could say.
     *
     * Reaches a register over the network under the same terms as
     * {@see \App\Model\Entity\Customer::identityNumberCheck()}.
     *
     * @return \App\BusinessRegister\VatNumberCheck|null
     */
    public function vatNumberCheck(): ?VatNumberCheck
    {
        return Registry::vatNumberCheck($this->vat_number);
    }

    /**
     * getter for active_services
     *
     * True where any of the customer's contracts is running. In a query the same decision is
     * {@see \App\Model\Table\ContractsTable::findWithActiveServices()}.
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
