<?php
declare(strict_types=1);

namespace Scheduler\Event;

use App\Service\ErrorReport;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Override;
use Queue\Model\Entity\QueuedJob;
use QueueScheduler\Model\Entity\SchedulerRow;
use Throwable;

/**
 * Tells whoever is on call that a job has failed for good.
 *
 * Any job of the queue, whether a row of the schedule put it there or not. A row that keeps failing
 * is told about once, on the first failure after a success: at every run it would be a report
 * every few minutes, read by nobody.
 */
class FailedJobListener implements EventListenerInterface
{
    use LocatorAwareTrait;

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function implementedEvents(): array
    {
        return ['Queue.Job.maxAttemptsExhausted' => 'report'];
    }

    /**
     * @param \Cake\Event\EventInterface<object> $event The event.
     * @return void
     */
    public function report(EventInterface $event): void
    {
        /** @var \Queue\Model\Entity\QueuedJob $job */
        $job = $event->getData('job');
        $failure = (string)$event->getData('failureMessage');

        // the worker is still needed for the next job, whatever happens to the report
        try {
            $row = $this->rowOf($job);

            if ($row !== null && $row->consecutive_failures > 0) {
                return;
            }

            $name = $row->name ?? $job->job_task;

            ErrorReport::send(
                __d('scheduler', 'Job failed: {0}', $name),
                __d('scheduler', "The job {0} has failed.\n\nError: {1}", [$name, strtok($failure, "\n")]),
            );
        } catch (Throwable $e) {
            Log::error('The failure of job ' . $job->id . ' could not be reported: ' . $e->getMessage());
        }
    }

    /**
     * The row of the schedule that put the job in the queue, if one did.
     *
     * @param \Queue\Model\Entity\QueuedJob $job The job.
     * @return \QueueScheduler\Model\Entity\SchedulerRow|null
     */
    private function rowOf(QueuedJob $job): ?SchedulerRow
    {
        /** @var \QueueScheduler\Model\Entity\SchedulerRow|null */
        return $this->fetchTable('QueueScheduler.SchedulerRows')
            ->find()
            ->where(['last_queued_job_id' => $job->id])
            ->first();
    }
}
