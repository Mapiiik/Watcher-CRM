<?php
declare(strict_types=1);

namespace Tasks\Service;

use App\Messages\Messages;
use App\Service\OperatorReport;
use Cake\Log\Log;
use Tasks\Model\Entity\Task;

/**
 * Tells the report addresses that a task has been closed.
 *
 * Closing a task does not always end the work: an installation that is done still has to be
 * invoiced, and the one who closed the task is rarely the one who does what follows. Until now
 * they had no way of learning it happened - the task simply dropped out of the open list.
 *
 * Only types that ask for it are reported, so nobody is buried in tasks that end where they are.
 */
final class CompletedTaskReport extends TaskMail
{
    /**
     * Report that this task has been closed.
     *
     * @param \Tasks\Model\Entity\Task $task The task, read together with `TasksTable::reportContain()`.
     * @param \App\Messages\Messages $messages Where to leave what happened, for the layer above.
     * @return bool Whether it went out.
     */
    public static function send(Task $task, Messages $messages): bool
    {
        $recipients = OperatorReport::recipients();

        if ($recipients === []) {
            // nobody configured is a deployment's choice rather than an error
            Log::debug('Nobody is configured to be told that a task was closed.');

            return false;
        }

        $subject = __d('tasks', 'Task completed: {0} # {1}', $task->task_type->name, $task->number)
            . ' - ' . $task->summary_text;

        return self::deliver(
            array_fill_keys($recipients, null),
            $subject,
            'task-completed',
            $task,
            $messages,
        );
    }
}
