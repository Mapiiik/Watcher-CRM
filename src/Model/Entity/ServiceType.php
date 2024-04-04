<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ServiceType Entity
 *
 * @property \Cake\I18n\DateTime|null $created
 * @property string|null $created_by
 * @property \App\Model\Entity\AppUser|null $creator
 * @property \Cake\I18n\DateTime|null $modified
 * @property string|null $modified_by
 * @property \App\Model\Entity\AppUser|null $modifier
 * @property string $id
 * @property int $nid
 * @property string|null $name
 * @property string|null $contract_number_format
 * @property string|null $subscriber_verification_code_format
 * @property \PhpCollective\DecimalObject\Decimal|null $activation_fee
 * @property \PhpCollective\DecimalObject\Decimal|null $activation_fee_with_obligation
 * @property bool $separate_invoice
 * @property bool $invoice_with_items
 * @property string|null $invoice_text
 * @property bool $installation_address_required
 * @property bool $access_point_required
 * @property bool $normally_with_borrowed_equipment
 * @property bool $have_contract_versions
 * @property bool $have_equipments
 * @property bool $have_ip_addresses
 * @property bool $have_radius_accounts
 * @property bool $assign_ip_addresses_from_behind
 *
 * @property \App\Model\Entity\Contract[] $contracts
 * @property \App\Model\Entity\Service[] $services
 */
class ServiceType extends Entity
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
        'name' => true,
        'contract_number_format' => true,
        'subscriber_verification_code_format' => true,
        'activation_fee' => true,
        'activation_fee_with_obligation' => true,
        'separate_invoice' => true,
        'invoice_with_items' => true,
        'invoice_text' => true,
        'installation_address_required' => true,
        'access_point_required' => true,
        'normally_with_borrowed_equipment' => true,
        'have_contract_versions' => true,
        'have_equipments' => true,
        'have_ip_addresses' => true,
        'have_radius_accounts' => true,
        'assign_ip_addresses_from_behind' => true,
        'contracts' => true,
        'services' => true,
    ];
}
