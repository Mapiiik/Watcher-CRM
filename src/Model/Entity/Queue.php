<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Queue Entity
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
 * @property string|null $caption
 * @property int|null $fup
 * @property int|null $limit
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
class Queue extends Entity
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
        'fup' => true,
        'limit' => true,
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
