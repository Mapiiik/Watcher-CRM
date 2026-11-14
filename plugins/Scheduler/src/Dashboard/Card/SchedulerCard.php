<?php
declare(strict_types=1);

namespace Scheduler\Dashboard\Card;

use Dashboard\Card\AbstractDashboardCard;
use Override;
use Scheduler\Model\Enum\SchedulerState;
use Scheduler\Service\SchedulerHealth;

/**
 * Whether the scheduler is keeping up, for the one who can do something about it.
 *
 * `scheduler check` mails the same problems, but it runs inside the scheduler, so a service that
 * has stopped altogether is reported by nothing but this card. It is there when all is well too,
 * with the time of the last pass, so that a card gone missing is not mistaken for good news.
 *
 * Everything it says is worded here, in the plugin's own domain, and the application's template
 * only lays it out.
 */
class SchedulerCard extends AbstractDashboardCard
{
    /**
     * @param \Scheduler\Service\SchedulerHealth $health What is known about the scheduler.
     */
    public function __construct(private SchedulerHealth $health)
    {
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'scheduler';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __d('scheduler', 'Scheduler');
    }

    /**
     * Only administrators may open the pages of the queue, so nobody else could act on it.
     *
     * @return list<string>
     */
    #[Override]
    public function roles(): array
    {
        return ['admin'];
    }

    /**
     * Fetched on its own, as it asks the cache and the queue before it can say anything.
     *
     * @return bool
     */
    #[Override]
    public function deferred(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function data(): array
    {
        $problems = $this->health->problems();
        $lastRun = $this->health->lastRun();

        if ($problems !== []) {
            $state = SchedulerState::Attention;
            $summary = __d('scheduler', 'The scheduler needs attention');
        } elseif ($lastRun !== null) {
            $state = SchedulerState::Running;
            $summary = __d('scheduler', 'The dispatcher last ran at {0}.', (string)$lastRun);
        } else {
            // An overdue row would have said so above, so a silence this long is a short one.
            $state = SchedulerState::Unknown;
            $summary = __d('scheduler', 'The dispatcher has not reported since the cache was last cleared.');
        }

        return [
            'state' => $state,
            'summary' => $summary,
            'problems' => $problems,
            'links' => [
                [
                    'title' => __d('scheduler', 'Open the runs'),
                    'url' => ['prefix' => 'Admin', 'plugin' => 'Queue', 'controller' => 'Queue', 'action' => 'index'],
                ],
                [
                    'title' => __d('scheduler', 'Open the schedule'),
                    'url' => [
                        'prefix' => 'Admin',
                        'plugin' => 'QueueScheduler',
                        'controller' => 'QueueScheduler',
                        'action' => 'index',
                    ],
                ],
            ],
        ];
    }
}
