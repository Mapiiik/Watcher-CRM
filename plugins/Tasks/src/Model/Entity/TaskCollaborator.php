<?php
declare(strict_types=1);

namespace Tasks\Model\Entity;

use App\Model\Entity\AppEntity;

/**
 * One person working on one task beside whoever holds it.
 *
 * Nothing hangs on the link itself, so it carries nothing but the two names it joins. It is a
 * record of its own all the same rather than a row the framework writes behind the scenes:
 * who put whom on a task, and when, belongs in the history of that task like everything else.
 *
 * What keeps that history is hidden here, because this is read out through the task - a page
 * of it, an answer to another application - and there it would only be noise.
 *
 * @property string $id
 * @property string $task_id
 * @property string $user_id
 *
 * @property \Tasks\Model\Entity\Task $task
 * @property \App\Model\Entity\AppUser $user
 */
class TaskCollaborator extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'task_id' => true,
        'user_id' => true,
        'task' => true,
        'user' => true,
    ];

    /**
     * Fields that are left out wherever the entity is written out whole.
     *
     * @var list<string>
     */
    protected array $_hidden = [
        'created',
        'created_by',
        'modified',
        'modified_by',
    ];
}
