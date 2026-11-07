<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\TasksTable;
use Cake\ORM\Query\SelectQuery;
use Tasks\Controller\Trait\TaskStatesControllerTrait;

/**
 * TaskStates Controller
 *
 * @property \App\Model\Table\TaskStatesTable $TaskStates
 */
class TaskStatesController extends AppController
{
    use TaskStatesControllerTrait;

    /**
     * A task here is filed under a customer, so that is what is read with it.
     *
     * @return array<mixed>
     */
    protected function viewContain(): array
    {
        return [
            'Creators',
            'Modifiers',
            'Tasks' => [
                // a state or a type gathers tasks for years, so the card takes only so many
                'queryBuilder' => fn(SelectQuery $query): SelectQuery => $query->limit(TasksTable::SHOWN_ON_A_CARD),
                'Customers',
                'TaskStates',
                'TaskTypes',
                'Users',
                'Collaborators',
            ],
        ];
    }
}
