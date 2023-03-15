<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ServiceType Entity
 *
 * @property \Cake\I18n\FrozenTime|null $created
 * @property int|null $created_by
 * @property \CakeDC\Users\Model\Entity\User|null $creator
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $modified_by
 * @property \CakeDC\Users\Model\Entity\User|null $modifier
 * @property int $id
 * @property string|null $name
 * @property string|null $contract_number_format
 * @property string|null $subscriber_verification_code_format
 * @property int|null $activation_fee
 * @property int|null $activation_fee_with_obligation
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
    protected $_accessible = [
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
        'contracts' => true,
        'services' => true,
    ];
}
