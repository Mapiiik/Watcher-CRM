<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * EquipmentType Entity
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
 * @property \PhpCollective\DecimalObject\Decimal|null $price
 * @property \PhpCollective\DecimalObject\Decimal|null $price_with_obligation
 *
 * @property \App\Model\Entity\BorrowedEquipment[] $borrowed_equipments
 * @property \App\Model\Entity\SoldEquipment[] $sold_equipments
 */
class EquipmentType extends Entity
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
        'price' => true,
        'price_with_obligation' => true,
        'borrowed_equipments' => true,
        'sold_equipments' => true,
    ];
}
