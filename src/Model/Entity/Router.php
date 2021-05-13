<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Router Entity
 *
 * @property int $id
 * @property string $name
 * @property string $ip
 * @property int $port
 * @property string|null $caption
 * @property bool $accounting
 * @property float|null $gpsx
 * @property float|null $gpsy
 * @property string|null $note
 *
 * @property \App\Model\Entity\Range[] $ranges
 * @property \App\Model\Entity\Task[] $tasks
 */
class Router extends Entity
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
        'name' => true,
        'ip' => true,
        'port' => true,
        'caption' => true,
        'accounting' => true,
        'gpsx' => true,
        'gpsy' => true,
        'note' => true,
        'ranges' => true,
        'tasks' => true,
    ];
}
