<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use App\Model\Table\TasksTable;
use Override;

/**
 * The unfinished tasks the signed-in operator is holding.
 */
class MyTasksCard extends AbstractTaskListCard
{
    /**
     * @param \App\Model\Table\TasksTable $tasks Tasks table.
     * @param string|null $user_id The signed-in operator.
     */
    public function __construct(TasksTable $tasks, private ?string $user_id)
    {
        parent::__construct($tasks);
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'my_tasks';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('My Tasks');
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function roles(): array
    {
        return self::TASK_ROLES;
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function data(): array
    {
        if ($this->user_id === null) {
            return [
                'tasks' => [],
                'total' => 0,
                'url' => $this->listingUrl([]),
                'empty' => __('You are holding no unfinished tasks.'),
            ];
        }

        return $this->payload(
            $this->activeTasks()->find('forUser', user_id: $this->user_id),
            ['user_id' => $this->user_id],
            ['empty' => __('You are holding no unfinished tasks.')],
        );
    }
}
