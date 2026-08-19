<?php
declare(strict_types=1);

namespace App\Controller;

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
            'Tasks' => ['Customers', 'TaskStates', 'Users'],
            'ContractStates',
            'Creators',
            'Modifiers',
        ];
    }
}
