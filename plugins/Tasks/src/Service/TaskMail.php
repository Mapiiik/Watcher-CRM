<?php
declare(strict_types=1);

namespace Tasks\Service;

use App\Messages\Messages;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Tasks\Model\Entity\Task;
use Throwable;

/**
 * What every task email does the same way.
 *
 * These are sent from a save that has already been committed, so nothing here may throw: a mail
 * server that is down is not a reason to tell somebody their task was not saved. What happened is
 * left in the message buffer for whichever layer is above - a controller flashes it, a command
 * prints it - and a failure goes to the log as well, because a save from the API has nobody
 * watching the buffer.
 */
abstract class TaskMail
{
    /**
     * Render one task email and send it.
     *
     * @param array<string, string|null> $recipients Address to the name to address it to, if known.
     * @param string $subject What the mail says it is.
     * @param string $template Email template to render, dash-cased.
     * @param \Tasks\Model\Entity\Task $task The task the mail is about.
     * @param \App\Messages\Messages $messages Where to leave what happened, for the layer above.
     * @param array<string, mixed> $viewVars Anything else the template asks for.
     * @return bool Whether it went out.
     */
    protected static function deliver(
        array $recipients,
        string $subject,
        string $template,
        Task $task,
        Messages $messages,
        array $viewVars = [],
    ): bool {
        try {
            $mailer = new Mailer('default');

            foreach ($recipients as $address => $name) {
                if ($name !== null && $name !== '') {
                    $mailer->addTo($address, $name);
                } else {
                    $mailer->addTo($address);
                }
            }

            $mailer->setSubject($subject);
            $mailer->setEmailFormat('html');

            $mailer->viewBuilder()
                ->setLayout('default')
                ->setTemplate($template);

            $mailer->setViewVars(array_merge(['title' => $subject, 'task' => $task], $viewVars));

            $mailer->deliver();

            $messages->success(
                __d('tasks', 'Notification email sent.') . ' (' . implode(', ', array_keys($recipients)) . ')',
            );

            return true;
        } catch (Throwable $e) {
            Log::error('Could not send a task email for task ' . $task->id . ': ' . $e->getMessage());

            $messages->error(__d('tasks', 'The notification email could not be sent.') . ' (' . $e->getMessage() . ')');

            return false;
        }
    }
}
