<?php
declare(strict_types=1);

namespace App\Model\Entity;

/**
 * Queue Entity
 *
 * @property string $id
 * @property int $nid
 * @property string $name
 * @property string|null $caption
 * @property int|null $fup_limit
 * @property int|null $data_limit
 * @property int|null $overlimit_fragment
 * @property int|null $overlimit_cost
 * @property int|null $service_type_id
 * @property int|null $speed_up
 * @property int|null $speed_down
 * @property string|null $cto_category
 *
 * @property \App\Model\Entity\ServiceType $service_type
 * @property \App\Model\Entity\Service[] $services
 */
class Queue extends AppEntity
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
        'caption' => true,
        'fup_limit' => true,
        'data_limit' => true,
        'overlimit_fragment' => true,
        'overlimit_cost' => true,
        'service_type_id' => true,
        'speed_up' => true,
        'speed_down' => true,
        'cto_category' => true,
        'service_type' => true,
        'services' => true,
    ];
}
