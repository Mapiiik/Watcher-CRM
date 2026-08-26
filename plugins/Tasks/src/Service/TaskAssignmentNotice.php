<?php
declare(strict_types=1);

namespace Tasks\Service;

use App\Messages\Messages;
use Tasks\Model\Entity\Task;

/**
 * Tells whoever holds a task that it is theirs, or that it has changed under them.
 *
 * Somebody else's task is the whole point: a task saved by the person who holds it tells them
 * nothing they were not just looking at.
 */
final class TaskAssignmentNotice extends TaskMail
{
    /**
     * Tell the person this task has been handed to.
     *
     * @param \Tasks\Model\Entity\Task $task The task, read together with `TasksTable::reportContain()`.
     * @param bool $new Whether the task has only just been written down.
     * @param \App\Messages\Messages $messages Where to leave what happened, for the layer above.
     * @return bool Whether it went out.
     */
    public static function send(Task $task, bool $new, Messages $messages): bool
    {
        if ($task->user === null || ($task->user->email ?? '') === '') {
            $messages->warning(__d(
                'tasks',
                'The notification email could not be sent because the user does not have an email address.',
            ));

            return false;
        }

        $title = $new
            ? __d('tasks', 'You have a new task # {0}', $task->number)
            : __d('tasks', 'You have changes in task # {0}', $task->number);

        return self::deliver(
            [$task->user->email => $task->user->name],
            $title . ' - ' . $task->summary_text,
            'task-notification',
            $task,
            $messages,
            // the heading stays the sentence; the summary is in the subject only, where it helps
            // somebody pick the mail out of a list
            ['title' => $title],
        );
    }
}
