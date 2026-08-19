<?php
declare(strict_types=1);

namespace Tasks\Dashboard\Card;

use Override;

/**
 * Unfinished tasks nobody is holding.
 */
class UnassignedTasksCard extends AbstractTaskListCard
{
    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'unassigned_tasks';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __d('tasks', 'Unassigned Tasks');
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function roles(): array
    {
        return ['network-manager', 'sales-manager'];
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function data(): array
    {
        return $this->payload(
            $this->activeTasks()->find('unassigned'),
            ['user_id' => 'none'],
            ['empty' => __d('tasks', 'Every unfinished task has somebody holding it.')],
        );
    }
}
