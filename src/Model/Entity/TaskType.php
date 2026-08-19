<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Tasks\Model\Entity\TaskType as TasksTaskType;

/**
 * TaskType Entity
 *
 * What a type requires of a task is what this application adds to the shared one: a task filed
 * under a customer, and under a contract.
 *
 * @property bool $customer_required
 * @property bool $contract_required
 */
class TaskType extends TasksTaskType
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
        'name' => true,
        'customer_required' => true,
        'contract_required' => true,
        'tasks' => true,
    ];
}
