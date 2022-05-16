<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TaskState Entity
 *
 * @property int $id
 * @property string|null $name
 * @property string $color
 * @property bool $completed
 *
 * @property \App\Model\Entity\Task[] $tasks
 */
class TaskState extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<bool>
     */
    protected $_accessible = [
        'name' => true,
        'color' => true,
        'completed' => true,
        'tasks' => true,
    ];
}
