<?php
declare(strict_types=1);

namespace RADIUS\Model\Entity;

use Cake\ORM\Entity;

/**
 * Radgroupreply Entity
 *
 * @property int $id
 * @property string $groupname
 * @property string $attribute
 * @property string $op
 * @property string $value
 */
class Radgroupreply extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'groupname' => true,
        'attribute' => true,
        'op' => true,
        'value' => true,
    ];
}
