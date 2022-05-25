<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TaxRate Entity
 *
 * @property int $id
 * @property string $name
 * @property float $vat_rate
 * @property bool $reverse_charge
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
    protected $_accessible = [
        'name' => true,
        'vat_rate' => true,
        'reverse_charge' => true,
        'customers' => true,
    ];
}
