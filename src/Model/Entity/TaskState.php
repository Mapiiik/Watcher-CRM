<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * TaskState Entity
 *
 * @property \Cake\I18n\DateTime|null $created
 * @property string|null $created_by
 * @property \App\Model\Entity\AppUser|null $creator
 * @property \Cake\I18n\DateTime|null $modified
 * @property string|null $modified_by
 * @property \App\Model\Entity\AppUser|null $modifier
 * @property string $id
 * @property int $nid
 * @property string|null $name
 * @property string $color
 * @property bool $completed
 * @property int $priority
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
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'name' => true,
        'color' => true,
        'completed' => true,
        'priority' => true,
        'tasks' => true,
    ];
}
