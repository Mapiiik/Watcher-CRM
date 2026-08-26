<?php
declare(strict_types=1);

namespace Tasks\Service;

use App\Messages\Messages;
use Tasks\Model\Entity\Task;

/**
 * Tells whoever a task is now in the hands of that it is theirs, or that it has changed under
 * them.
 *
 * Somebody else's task is the whole point: a task saved by one of the people it names tells them
 * nothing they were not just looking at. Who that leaves is worked out before this is called.
 */
final class TaskAssignmentNotice extends TaskMail
{
    /**
     * Tell the people this task has been left with.
     *
     * @param \Tasks\Model\Entity\Task $task The task, read together with `TasksTable::reportContain()`.
     * @param list<\App\Model\Entity\AppUser> $people Who to tell.
     * @param bool $new Whether the task has only just been written down.
     * @param \App\Messages\Messages $messages Where to leave what happened, for the layer above.
     * @return bool Whether it went out.
     */
    public static function send(Task $task, array $people, bool $new, Messages $messages): bool
    {
        $recipients = [];
        $unreachable = false;

        foreach ($people as $person) {
            $address = $person->get('email');

            if (!is_string($address) || $address === '') {
                $unreachable = true;

                continue;
            }

            $recipients[$address] = $person->get('name');
        }

        // Said once however many of them there are. One mail goes out to the rest all the same:
        // an account nobody filled in an address for is not a reason to leave the others in the
        // dark about work they are expected to do.
        if ($unreachable) {
            $messages->warning(__d(
                'tasks',
                'The notification email could not be sent because the user does not have an email address.',
            ));
        }

        if ($recipients === []) {
            return false;
        }

        $title = $new
            ? __d('tasks', 'You have a new task # {0}', $task->number)
            : __d('tasks', 'You have changes in task # {0}', $task->number);

        return self::deliver(
            $recipients,
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
