<?php
declare(strict_types=1);

namespace Tasks\Model\Entity;

use App\Model\Entity\AppEntity;

/**
 * What a task type is, in both applications.
 *
 * What a type requires of a task is left to each application, which adds its own flags to this.
 *
 * @property string $id
 * @property int $nid
 * @property string|null $name
 *
 * @property \App\Model\Entity\Task[] $tasks
 */
class TaskType extends AppEntity
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
        'tasks' => true,
    ];
}
