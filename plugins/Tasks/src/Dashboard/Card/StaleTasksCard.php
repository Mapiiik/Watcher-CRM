<?php
declare(strict_types=1);

namespace Tasks\Dashboard\Card;

use Override;

/**
 * Unfinished tasks that nobody has touched for a while.
 *
 * Tasks carry no recurrence, so nothing brings a forgotten one back on its own. Listing
 * what has lain untouched is what stands in for that.
 */
class StaleTasksCard extends AbstractTaskListCard
{
    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'stale_tasks';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __d('tasks', 'Stale Tasks');
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
        $days = $this->days('tasks.stale_after_days', 30);

        return $this->payload(
            $this->activeTasks()->find('stale', days: $days),
            ['stale' => 1],
            ['empty' => __d('tasks', 'Nothing has been left lying around.')],
        );
    }
}
