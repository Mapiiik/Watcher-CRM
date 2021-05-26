<?php
declare(strict_types=1);

namespace RADIUS\Model\Entity;

use Cake\ORM\Entity;

/**
 * Radusergroup Entity
 *
 * @property int $id
 * @property string $username
 * @property string $groupname
 * @property int $priority
 *
 * @property \RADIUS\Model\Entity\User $user
 * @property \RADIUS\Model\Entity\Radgroupcheck[] $radgroupcheck
 * @property \RADIUS\Model\Entity\Radgroupreply[] $radgroupreply
 */
class Radusergroup extends Entity
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
        'username' => true,
        'groupname' => true,
        'priority' => true,
        'user' => true,
        'radgroupcheck' => true,
        'radgroupreply' => true,
    ];
}
