<?php
declare(strict_types=1);

namespace WorkReports\Service;

use App\Messages\Messages;
use WorkReports\Model\Entity\WorkReport;

/**
 * Sends a submitted report to the worker and to whoever gets their reports.
 */
final class SubmittedWorkReportMail extends WorkReportMail
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
        return self::deliver(
            [$workReport->user, ...$recipients],
            [],
            self::subject(__d('work_reports', 'Work report submitted'), $workReport),
            'work_report_submitted',
            $workReport,
            $messages,
            ['summary' => $summary],
        );
    }
}
