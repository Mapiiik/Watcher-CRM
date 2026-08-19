<?php
declare(strict_types=1);

namespace App\Maps;

use App\Model\Entity\Task;
use ArrayObject;
use Cake\ORM\Association;
use Cake\ORM\Locator\LocatorAwareTrait;
use Maps\Position;
use Override;
use Tasks\Maps\TaskMap as TasksTaskMap;

/**
 * Where a task is to be done, in this application.
 *
 * A task is done at the installation address of its contract; a task holding an access point
 * instead is done at the access point. One holding neither cannot be put on a map and is left off,
 * the same way the network map passes over a place without coordinates.
 */
final class TaskMap extends TasksTaskMap
{
    use LocatorAwareTrait;

    /**
     * The open tasks, with everything a bubble reads.
     *
     * In no particular order: a marker is placed by its position and kept under its own key,
     * so nothing about the map depends on which task came first.
     *
     * @param string|null $taskTypeId Only tasks of this type, when one is named.
     * @param string|null $taskStateId Only tasks in this state, when one is named.
     * @return iterable<\App\Model\Entity\Task>
     */
    #[Override]
    protected function tasks(?string $taskTypeId, ?string $taskStateId): iterable
    {
        /** @var \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Task> $query */
        $query = $this->fetchTable('Tasks')
            ->find('active')
            ->contain([
                'TaskTypes',
                'Contracts' => ['InstallationAddresses'],
                // A customer is read per task while the tasks are ordered by columns of their
                // own, which the subquery strategy turns into a grouping PostgreSQL will not
                // accept - the task listing spells this out for the same reason.
                // The summary line falls back to the customer's own address when the contract
                // has none.
                'Customers' => [
                    'Addresses' => ['strategy' => Association::STRATEGY_SELECT],
                    'strategy' => Association::STRATEGY_SELECT,
                ],
            ]);

        if ($taskTypeId !== null) {
            $query->where(['Tasks.task_type_id' => $taskTypeId]);
        }

        if ($taskStateId !== null) {
            $query->where(['Tasks.task_state_id' => $taskStateId]);
        }

        return $query->all();
    }

    /**
     * Where the task is to be done, or null where it cannot be put on a map at all.
     *
     * @param \App\Model\Entity\Task $task The task being drawn.
     * @return \Maps\Position|null
     */
    #[Override]
    protected function positionOf(Task $task): ?Position
    {
        $address = $task->contract?->installation_address;

        if ($address !== null && is_numeric($address->gps_y) && is_numeric($address->gps_x)) {
            return new Position((float)$address->gps_y, (float)$address->gps_x);
        }

        $accessPoint = $task->access_point;

        if (
            $accessPoint instanceof ArrayObject
            && is_numeric($accessPoint['gps_y'] ?? null)
            && is_numeric($accessPoint['gps_x'] ?? null)
        ) {
            return new Position((float)$accessPoint['gps_y'], (float)$accessPoint['gps_x']);
        }

        return null;
    }
}
