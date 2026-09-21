<?php
declare(strict_types=1);

namespace WorkReports\Controller;

use Cake\Http\Exception\BadRequestException;
use Cake\I18n\Date;
use WorkReports\Model\Entity\WorkReport;
use WorkReports\Service\WorkingCalendar;
use WorkReports\Service\WorkReportSummary;

/**
 * WorkReports Controller
 *
 * @property \WorkReports\Model\Table\WorkReportsTable $WorkReports
 */
class WorkReportsController extends AppController
{
    /**
     * The reports the user signed in may see.
     *
     * @return void Renders view
     */
    public function index(): void
    {
        $workers = $this->visibleWorkers();

        $query = $this->WorkReports->find(
            'all',
            contain: ['Users'],
            conditions: ['WorkReports.user_id IN' => array_column($workers, 'value') ?: [$this->identityId()]],
        );

        $userId = $this->getRequest()->getQuery('user_id');
        if (is_string($userId) && $userId !== '') {
            $query->where(['WorkReports.user_id' => $userId]);
        }

        $this->paginate = [
            'order' => ['WorkReports.month' => 'DESC'],
            'sortableFields' => ['WorkReports.month', 'WorkReports.submitted', 'Users.last_name'],
        ];
        $workReports = $this->paginate($query);

        $this->set(compact('workReports', 'workers'));
    }

    /**
     * A month of a worker the way the spreadsheet showed it: what it adds up to, and every day of
     * it with what was reported on it. A month with nothing reported yet is shown all the same.
     *
     * @return void Renders view
     */
    public function sheet(): void
    {
        $userId = (string)($this->getRequest()->getQuery('user_id') ?: $this->identityId());
        $this->checkMaySee($userId);

        $month = $this->monthFromQuery();

        $workReport = $this->WorkReports->findFor($userId, $month);
        if ($workReport === null) {
            /** @var \WorkReports\Model\Table\WorkReportWorkersTable $workers */
            $workers = $this->fetchTable('WorkReports.WorkReportWorkers');

            $workReport = $this->WorkReports->newEntity([
                'user_id' => $userId,
                'month' => $month,
                'workload' => $workers->workloadOf($userId),
            ], ['validate' => false]);
            $workReport->work_report_items = [];
            $workReport->work_report_on_calls = [];
        } else {
            $workReport = $this->WorkReports->get($workReport->id, contain: [
                'WorkReportItems' => [
                    'WorkReportItemTypes',
                    'Customers',
                    'Contracts',
                    'PrivateCars',
                    'CompanyCars',
                    'WorkRates',
                    'WorkLabels',
                    'Collaborators',
                ],
                'WorkReportOnCalls',
                'Submitters',
            ]);
        }

        $calendar = WorkingCalendar::fromSettings();
        $summary = WorkReportSummary::fromSettings($workReport);
        $days = $this->daysOf($workReport);
        $workers = $this->visibleWorkers();
        $workerName = $this->fetchTable('AppUsers')->get($userId)->get('name');

        $this->set(compact('workReport', 'summary', 'calendar', 'days', 'workers', 'workerName', 'month'));
    }

    /**
     * The month asked for as `YYYY-MM`, the current one when none is.
     *
     * @return \Cake\I18n\Date
     * @throws \Cake\Http\Exception\BadRequestException
     */
    protected function monthFromQuery(): Date
    {
        $month = $this->getRequest()->getQuery('month');
        if (!is_string($month) || $month === '') {
            return Date::today()->firstOfMonth();
        }

        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            throw new BadRequestException(__d('work_reports', 'A month is asked for as YYYY-MM.'));
        }

        return new Date($month . '-01');
    }

    /**
     * Every day of the month with the items reported on it.
     *
     * @param \WorkReports\Model\Entity\WorkReport $workReport Report with its items.
     * @return array<string, array{date: \Cake\I18n\Date, items: list<\WorkReports\Model\Entity\WorkReportItem>}>
     */
    protected function daysOf(WorkReport $workReport): array
    {
        $days = [];
        $day = $workReport->month->firstOfMonth();
        $last = $workReport->month->lastOfMonth();
        while ($day <= $last) {
            $days[$day->format('Y-m-d')] = ['date' => $day, 'items' => []];
            $day = $day->addDays(1);
        }

        foreach ($workReport->work_report_items as $item) {
            $key = $item->date->format('Y-m-d');
            if (isset($days[$key])) {
                $days[$key]['items'][] = $item;
            }
        }

        return $days;
    }
}
