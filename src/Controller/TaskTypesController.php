<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\TasksTable;
use Cake\ORM\Query\SelectQuery;
use Tasks\Controller\Trait\TaskTypesControllerTrait;

/**
 * TaskTypes Controller
 *
 * @property \App\Model\Table\TaskTypesTable $TaskTypes
 */
class TaskTypesController extends AppController
{
    use TaskTypesControllerTrait;

    /**
     * A task here is filed under a customer, and a contract state may name a type as the one it
     * waits for, so both are read with it.
     *
     * @return array<mixed>
     */
    protected function viewContain(): array
    {
        return [
            'Tasks' => [
                // a state or a type gathers tasks for years, so the card takes only so many
                'queryBuilder' => fn(SelectQuery $query): SelectQuery => $query->limit(TasksTable::SHOWN_ON_A_CARD),
                'Customers',
                'TaskStates',
                'Users',
                'Collaborators',
            ],
            'ContractStates',
            'CustomerStates',
            'Creators',
            'Modifiers',
        ];
    }
}
