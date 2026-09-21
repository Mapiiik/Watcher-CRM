<?php
declare(strict_types=1);

namespace WorkReports\Service;

use App\Messages\Messages;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Throwable;
use WorkReports\Model\Entity\WorkReport;

/**
 * Sends a submitted report to the worker and to whoever gets their reports.
 *
 * The report is already submitted when this runs, so nothing here may throw: a mail server that
 * is down is no reason to say the report was not submitted. What happened is left in the message
 * buffer for the controller to flash, and a failure goes to the log as well.
 */
final class SubmittedWorkReportMail
{
    /**
     * Send the report.
     *
     * @param \WorkReports\Model\Entity\WorkReport $workReport Report with its user and its items contained.
     * @param \WorkReports\Service\WorkReportSummary $summary What the report adds up to.
     * @param list<\App\Model\Entity\AppUser> $recipients Who gets the worker's reports.
     * @param \App\Messages\Messages $messages Where to leave what happened.
     * @return bool Whether it went out.
     */
    public static function send(
        WorkReport $workReport,
        WorkReportSummary $summary,
        array $recipients,
        Messages $messages,
    ): bool {
        $addresses = [];
        foreach ([$workReport->user, ...$recipients] as $user) {
            if ($user->email !== null && $user->email !== '') {
                $addresses[$user->email] = $user->name;
            }
        }

        if ($addresses === []) {
            $messages->warning(__d('work_reports', 'Nobody to send the report to has an email address.'));

            return false;
        }

        $subject = __d(
            'work_reports',
            'Work report {0} - {1}',
            $workReport->month->i18nFormat('LLLL yyyy'),
            $workReport->user->name,
        );

        try {
            $mailer = new Mailer('default');
            foreach ($addresses as $address => $name) {
                $mailer->addTo($address, $name);
            }
            $mailer->setSubject($subject);
            $mailer->setEmailFormat('html');
            $mailer->viewBuilder()
                ->setLayout('default')
                ->setTemplate('WorkReports.work_report_submitted');
            $mailer->setViewVars([
                'title' => $subject,
                'workReport' => $workReport,
                'summary' => $summary,
            ]);
            $mailer->deliver();

            $messages->success(
                __d('work_reports', 'The report has been sent.') . ' (' . implode(', ', array_keys($addresses)) . ')',
            );

            return true;
        } catch (Throwable $e) {
            Log::error('Could not send work report ' . $workReport->id . ': ' . $e->getMessage());

            $messages->error(__d('work_reports', 'The report could not be sent.') . ' (' . $e->getMessage() . ')');

            return false;
        }
    }
}
