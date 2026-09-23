<?php
declare(strict_types=1);

namespace WorkReports\Controller;

use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\I18n\Date;
use WorkReports\Service\WorkingCalendar;

/**
 * WorkReportOnCalls Controller
 *
 * @property \WorkReports\Model\Table\WorkReportOnCallsTable $WorkReportOnCalls
 */
class WorkReportOnCallsController extends AppController
{
    /**
     * Mark the day as one on call, or take the mark back.
     *
     * The day is worth what the settings say for its kind at the moment it is marked, and keeps
     * that when the settings change later.
     *
     * @return \Cake\Http\Response|null Redirects to the month.
     */
    public function toggle(): ?Response
    {
        $this->getRequest()->allowMethod(['post']);

        $userId = (string)($this->getRequest()->getData('user_id') ?: $this->identityId());
        $this->checkMayEdit($userId);

        $date = $this->getRequest()->getData('date');
        if (!is_string($date) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            throw new BadRequestException(__d('work_reports', 'A day is asked for as YYYY-MM-DD.'));
        }
        $day = new Date($date);

        // asked before the report is looked up, so that a month still ahead does not come to be
        if ($day > Date::today()) {
            $this->Flash->error(__d('work_reports', 'Work is not reported ahead of time.'));

            return $this->redirect($this->sheetUrl($userId, $day));
        }

        /** @var \WorkReports\Model\Table\WorkReportsTable $reports */
        $reports = $this->fetchTable('WorkReports.WorkReports');
        $report = $reports->findOrCreateFor($userId, $day);

        if ($report->isLocked()) {
            $this->Flash->error(
                __d('work_reports', 'The report has been submitted, its items can no longer be changed.'),
            );
        } elseif ($report->isClosedOn($day)) {
            $this->Flash->error(__d('work_reports', 'The report is closed up to this day.'));
        } else {
            $onCall = $this->WorkReportOnCalls->find()
                ->where(['work_report_id' => $report->id, 'date' => $day])
                ->first();

            if ($onCall !== null) {
                $this->WorkReportOnCalls->deleteOrFail($onCall);
            } else {
                $this->WorkReportOnCalls->saveOrFail($this->WorkReportOnCalls->newEntity([
                    'work_report_id' => $report->id,
                    'date' => $day,
                    'hours' => (string)WorkingCalendar::fromSettings()->onCallHours($day),
                ]));
            }
        }

        return $this->redirect($this->sheetUrl($userId, $day));
    }

    /**
     * The month the day falls in, at the items.
     *
     * @param string $userId Worker.
     * @param \Cake\I18n\Date $day Day of the month.
     * @return array<string, mixed>
     */
    protected function sheetUrl(string $userId, Date $day): array
    {
        return [
            'controller' => 'WorkReports',
            'action' => 'sheet',
            '?' => ['user_id' => $userId, 'month' => $day->format('Y-m')],
            '#' => 'work-report-items',
        ];
    }
}
