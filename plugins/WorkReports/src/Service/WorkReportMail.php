<?php
declare(strict_types=1);

namespace WorkReports\Service;

use App\Messages\Messages;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Throwable;
use WorkReports\Model\Entity\WorkReport;

/**
 * What every work report email does the same way.
 *
 * These are sent once the report has been saved, so nothing here may throw: a mail server that is
 * down is no reason to say the report was not submitted or returned. What happened is left in the
 * message buffer for the controller to flash, and a failure goes to the log as well.
 */
abstract class WorkReportMail
{
    /**
     * Render one work report email and send it.
     *
     * @param list<\App\Model\Entity\AppUser> $to Whom the mail is for.
     * @param list<\App\Model\Entity\AppUser> $cc Who is told as well.
     * @param string $subject What the mail says it is.
     * @param string $template Email template of the plugin to render.
     * @param \WorkReports\Model\Entity\WorkReport $workReport The report the mail is about.
     * @param \App\Messages\Messages $messages Where to leave what happened.
     * @param array<string, mixed> $viewVars Anything else the template asks for.
     * @return bool Whether it went out.
     */
    protected static function deliver(
        array $to,
        array $cc,
        string $subject,
        string $template,
        WorkReport $workReport,
        Messages $messages,
        array $viewVars = [],
    ): bool {
        $toAddresses = self::addresses($to);
        $ccAddresses = array_diff_key(self::addresses($cc), $toAddresses);

        if ($toAddresses === [] && $ccAddresses === []) {
            $messages->warning(__d('work_reports', 'Nobody to send the report to has an email address.'));

            return false;
        }

        try {
            $mailer = new Mailer('default');
            foreach ($toAddresses as $address => $name) {
                $mailer->addTo($address, $name);
            }
            foreach ($ccAddresses as $address => $name) {
                $mailer->addCc($address, $name);
            }
            $mailer->setSubject($subject);
            $mailer->setEmailFormat('html');
            $mailer->viewBuilder()
                ->setLayout('default')
                ->setTemplate('WorkReports.' . $template);
            $mailer->setViewVars(['title' => $subject, 'workReport' => $workReport] + $viewVars);
            $mailer->deliver();

            $messages->success(__d('work_reports', 'Email sent.') . ' (' . implode(', ', array_keys(
                $toAddresses + $ccAddresses,
            )) . ')');

            return true;
        } catch (Throwable $e) {
            Log::error('Could not send an email about work report ' . $workReport->id . ': ' . $e->getMessage());

            $messages->error(__d('work_reports', 'The email could not be sent.') . ' (' . $e->getMessage() . ')');

            return false;
        }
    }

    /**
     * The addresses of the users who have one, to their names.
     *
     * @param list<\App\Model\Entity\AppUser> $users Users.
     * @return array<string, string>
     */
    private static function addresses(array $users): array
    {
        $addresses = [];
        foreach ($users as $user) {
            if ($user->email !== null && $user->email !== '') {
                $addresses[$user->email] = $user->name;
            }
        }

        return $addresses;
    }

    /**
     * What the mail says it is about.
     *
     * @param string $what What happened to the report.
     * @param \WorkReports\Model\Entity\WorkReport $workReport Report with its user contained.
     * @return string
     */
    protected static function subject(string $what, WorkReport $workReport): string
    {
        return $what . ': ' . $workReport->month->i18nFormat('LLLL yyyy') . ' - ' . $workReport->user->name;
    }
}
