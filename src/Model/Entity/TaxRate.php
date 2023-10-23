<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TaxRate Entity
 *
 * @property \Cake\I18n\DateTime|null $created
 * @property string|null $created_by
 * @property \App\Model\Entity\AppUser|null $creator
 * @property \Cake\I18n\DateTime|null $modified
 * @property string|null $modified_by
 * @property \App\Model\Entity\AppUser|null $modifier
 * @property string $id
 * @property int $nid
 * @property string $name
 * @property float $vat_rate
 * @property bool $reverse_charge
 * @property string|null $accounting_assignment_code
 * @property string|null $bank_account_code
 * @property string|null $activity_code
 *
 * @property \App\Model\Entity\Customer[] $customers
 */
class TaxRate extends Entity
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
        'name' => true,
        'vat_rate' => true,
        'reverse_charge' => true,
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'accounting_assignment_code' => true,
        'bank_account_code' => true,
        'activity_code' => true,
        'creator' => true,
        'modifier' => true,
        'customers' => true,
    ];
}
