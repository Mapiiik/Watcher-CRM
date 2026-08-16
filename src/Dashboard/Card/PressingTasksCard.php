<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use Override;

/**
 * Tasks whose deadline is near or past, and tasks marked urgent whatever their date says.
 *
 * This is the one card that is written out rather than counted - a deadline nobody can
 * read is a deadline nobody acts on.
 */
class PressingTasksCard extends AbstractTaskListCard
{
    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'pressing_tasks';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Urgent and Overdue Tasks');
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
        $within_days = $this->days('tasks.critical_within_days', 7);

        return $this->payload(
            $this->activeTasks()->find('pressing', within_days: $within_days),
            ['pressing' => 1],
            ['empty' => __('Nothing is urgent or running late.')],
        );
    }
}
