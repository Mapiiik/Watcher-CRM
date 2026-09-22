<?php
declare(strict_types=1);

namespace App\Model\Entity;

/**
 * Service Entity
 *
 * @property string $id
 * @property int $nid
 * @property string|null $name
 * @property \PhpCollective\DecimalObject\Decimal|null $price
 * @property int|null $service_type_id
 * @property int|null $connection_profile_id
 * @property string|null $accounting_product_code
 * @property bool $currently_offered
 * @property \App\Model\Enum\ServiceCriticalityLevel $criticality_level
 *
 * @property \App\Model\Entity\ServiceType $service_type
 * @property \App\Model\Entity\ConnectionProfile $connection_profile
 * @property \App\Model\Entity\Billing[] $billings
 */
class Service extends AppEntity
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
        'service_type_id' => true,
        'connection_profile_id' => true,
        'accounting_product_code' => true,
        'currently_offered' => true,
        'criticality_level' => true,
        'service_type' => true,
        'connection_profile' => true,
        'billings' => true,
    ];
}
