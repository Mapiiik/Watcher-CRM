<?php
declare(strict_types=1);

namespace Radius\Model\Entity;

use Cake\ORM\Entity;

/**
 * Radgroupreply Entity
 *
 * @property int $id
 * @property string $groupname
 * @property string $attribute
 * @property string $op
 * @property string $value
 *
 * @property \Radius\Model\Entity\Radusergroup[] $radusergroup
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
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'groupname' => true,
        'attribute' => true,
        'op' => true,
        'value' => true,
        'radusergroup' => true,
    ];
}
