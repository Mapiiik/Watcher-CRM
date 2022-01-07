<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ServiceType Entity
 *
 * @property int $id
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property string|null $name
 * @property string|null $contract_number_format
 * @property integer|null $activation_fee
 * @property integer|null $activation_fee_with_obligation
 * @property bool $separate_invoice
 * @property bool $invoice_with_items
 * @property string|null $invoice_text
 *
 * @property \App\Model\Entity\Contract[] $contracts
 * @property \App\Model\Entity\Queue[] $queues
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
     * @var array<bool>
     */
    protected $_accessible = [
        'created' => true,
        'modified' => true,
        'name' => true,
        'contract_number_format' => true,
        'contracts' => true,
        'queues' => true,
        'services' => true,
    ];
}
