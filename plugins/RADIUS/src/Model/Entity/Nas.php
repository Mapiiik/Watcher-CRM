<?php
declare(strict_types=1);

namespace RADIUS\Model\Entity;

use Cake\ORM\Entity;

/**
 * Nas Entity
 *
 * @property int $id
 * @property string $nasname
 * @property string $shortname
 * @property string $type
 * @property int|null $ports
 * @property string $secret
 * @property string|null $server
 * @property string|null $community
 * @property string|null $description
 */
class Nas extends Entity
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
        'nasname' => true,
        'shortname' => true,
        'type' => true,
        'ports' => true,
        'secret' => true,
        'server' => true,
        'community' => true,
        'description' => true,
    ];
}
