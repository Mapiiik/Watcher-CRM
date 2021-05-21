<?php
declare(strict_types=1);

namespace RADIUS\Model\Entity;

use Cake\ORM\Entity;

/**
 * Radusergroup Entity
 *
 * @property string $username
 * @property string $groupname
 * @property int $priority
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
        'groupname' => true,
        'priority' => true,
    ];
}
