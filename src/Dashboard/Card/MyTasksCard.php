<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use App\Model\Table\TasksTable;
use Override;

/**
 * The unfinished tasks the signed-in operator is holding.
 *
 * Tasks are assigned to a dealer rather than to a user, so an identity without a customer
 * behind it holds nothing and the card says so instead of listing everybody's work.
 */
class MyTasksCard extends AbstractTaskListCard
{
    /**
     * @param \App\Model\Table\TasksTable $tasks Tasks table.
     * @param string|null $dealer_id The dealer the signed-in operator stands for.
     */
    public function __construct(TasksTable $tasks, private ?string $dealer_id)
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
        if ($this->dealer_id === null) {
            return [
                'tasks' => [],
                'total' => 0,
                'url' => $this->listingUrl([]),
                'empty' => __('Your account stands for no dealer, so it holds no tasks.'),
            ];
        }

        return $this->payload(
            $this->activeTasks()->find('forDealer', dealer_id: $this->dealer_id),
            ['dealer_id' => $this->dealer_id],
            ['empty' => __('You are holding no unfinished tasks.')],
        );
    }
}
