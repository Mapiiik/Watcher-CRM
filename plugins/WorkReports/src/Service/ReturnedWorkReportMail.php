<?php
declare(strict_types=1);

namespace WorkReports\Service;

use App\Messages\Messages;
use WorkReports\Model\Entity\WorkReport;

/**
 * Tells the worker their report was returned for correction, and why. Whoever else gets their
 * reports is told as well, so that nobody goes on working with the month as it was.
 */
final class ReturnedWorkReportMail extends WorkReportMail
{
    /**
     * Send the notice.
     *
     * @param \WorkReports\Model\Entity\WorkReport $workReport Report with its user and returner contained.
     * @param list<\App\Model\Entity\AppUser> $recipients Who gets the worker's reports.
     * @param \App\Messages\Messages $messages Where to leave what happened.
     * @return bool Whether it went out.
     */
    public static function send(WorkReport $workReport, array $recipients, Messages $messages): bool
    {
        return self::deliver(
            [$workReport->user],
            $recipients,
            self::subject(__d('work_reports', 'Work report returned for correction'), $workReport),
            'work_report_returned',
            $workReport,
            $messages,
        );
    }
}
