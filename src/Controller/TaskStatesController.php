<?php
declare(strict_types=1);

namespace App\Controller;

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
                'Customers',
                'TaskStates',
                'TaskTypes',
                'Users',
                'Collaborators',
            ],
        ];
    }
}
