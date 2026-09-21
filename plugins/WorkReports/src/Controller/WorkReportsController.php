<?php
declare(strict_types=1);

namespace WorkReports\Controller;

use App\Controller\Traits\MessageHandlerTrait;
use App\Messages\Messages;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use WorkReports\Model\Entity\WorkReport;
use WorkReports\Service\ReturnedWorkReportMail;
use WorkReports\Service\SubmittedWorkReportMail;
use WorkReports\Service\WorkingCalendar;
use WorkReports\Service\WorkReportSummary;

/**
 * WorkReports Controller
 *
 * @property \WorkReports\Model\Table\WorkReportsTable $WorkReports
 */
class WorkReportsController extends AppController
{
    use MessageHandlerTrait;

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
            $workReport = $this->loadReport($workReport->id);
        }

        $calendar = WorkingCalendar::fromSettings();
        $summary = WorkReportSummary::fromSettings($workReport);
        $days = $this->daysOf($workReport);
        $workers = $this->visibleWorkers();
        $workerName = $this->fetchTable('AppUsers')->get($userId)->get('name');

        $mayEdit = $this->mayEdit($userId);
        $maySubmit = $userId === $this->identityId() || $this->seesEverybody();
        $mayReopen = $this->mayReopen($userId);

        $this->set(compact(
            'workReport',
            'summary',
            'calendar',
            'days',
            'workers',
            'workerName',
            'month',
            'mayEdit',
            'maySubmit',
            'mayReopen',
        ));
    }

    /**
     * Submit the report: the month is closed for its worker and sent to whoever gets it.
     *
     * Every working day has to have something reported on it. Days on top of them are only said.
     *
     * @param string|null $id Work report id.
     * @return \Cake\Http\Response|null Redirects to the month.
     */
    public function submit(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post']);
        $workReport = $this->loadReport((string)$id);
        if ($workReport->user_id !== $this->identityId() && !$this->seesEverybody()) {
            throw new ForbiddenException(__d('work_reports', 'Only the worker submits their report.'));
        }

        $summary = WorkReportSummary::fromSettings($workReport);
        $days = fn(array $days): string => implode(', ', array_map(
            fn(Date $day): string => (string)$day->i18nFormat('d. M.'),
            $days,
        ));

        if ($workReport->isLocked()) {
            $this->Flash->error(__d('work_reports', 'The report has already been submitted.'));
        } elseif ($summary->missingDays !== []) {
            $this->Flash->error(__d('work_reports', 'Nothing is reported on {0}.', $days($summary->missingDays)));
        } else {
            $workReport->submitted = DateTime::now();
            $workReport->submitted_by = $this->identityId();
            $this->WorkReports->saveOrFail($workReport);
            $this->Flash->success(__d('work_reports', 'The report has been submitted.'));

            if ($summary->extraDays !== []) {
                $this->Flash->warning(__d('work_reports', 'Also reported on {0}.', $days($summary->extraDays)));
            }

            /** @var \WorkReports\Model\Table\WorkReportWorkersTable $workers */
            $workers = $this->fetchTable('WorkReports.WorkReportWorkers');
            $messages = new Messages();
            SubmittedWorkReportMail::send(
                $workReport,
                $summary,
                $workers->recipientsOf($workReport->user_id),
                $messages,
            );
            $this->handleMessages($messages);
        }

        return $this->redirect($this->sheetUrl($workReport));
    }

    /**
     * Return a submitted report to its worker to be corrected, saying why.
     *
     * @param string|null $id Work report id.
     * @return \Cake\Http\Response|null Redirects to the month once returned, renders the form otherwise.
     */
    public function reopen(?string $id = null): ?Response
    {
        $workReport = $this->WorkReports->get((string)$id, contain: ['Users']);

        if (!$this->mayReopen($workReport->user_id)) {
            throw new ForbiddenException(__d('work_reports', 'This report is not yours to return.'));
        }

        if (!$workReport->isLocked()) {
            $this->Flash->error(__d('work_reports', 'The report has not been submitted.'));

            return $this->afterEditRedirect($this->sheetUrl($workReport));
        }

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $workReport = $this->WorkReports->patchEntity(
                $workReport,
                $this->getRequest()->getData(),
                ['validate' => 'reopen', 'fields' => ['return_reason']],
            );

            if (!$workReport->hasErrors()) {
                $workReport->submitted = null;
                $workReport->submitted_by = null;
                $workReport->returned = DateTime::now();
                $workReport->returned_by = $this->identityId();
                $this->WorkReports->saveOrFail($workReport);
                $this->Flash->success(__d('work_reports', 'The report has been returned for correction.'));

                /** @var \WorkReports\Model\Table\WorkReportWorkersTable $workers */
                $workers = $this->fetchTable('WorkReports.WorkReportWorkers');
                $workReport->returner = $this->fetchTable('AppUsers')->get($this->identityId());
                $messages = new Messages();
                ReturnedWorkReportMail::send($workReport, $workers->recipientsOf($workReport->user_id), $messages);
                $this->handleMessages($messages);

                return $this->afterEditRedirect($this->sheetUrl($workReport));
            }
        }

        $this->set(compact('workReport'));

        return null;
    }

    /**
     * The report with everything the month shows.
     *
     * @param string $id Work report id.
     * @return \WorkReports\Model\Entity\WorkReport
     */
    protected function loadReport(string $id): WorkReport
    {
        return $this->WorkReports->get($id, contain: [
            'Users',
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
            'Returners',
        ]);
    }

    /**
     * The month of the report.
     *
     * @param \WorkReports\Model\Entity\WorkReport $workReport Report.
     * @return array<string, mixed>
     */
    protected function sheetUrl(WorkReport $workReport): array
    {
        return [
            'action' => 'sheet',
            '?' => ['user_id' => $workReport->user_id, 'month' => $workReport->month->format('Y-m')],
        ];
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
     * Every day of the month with the items reported on it, and whether it was one on call.
     *
     * @param \WorkReports\Model\Entity\WorkReport $workReport Report with its items and on-call days.
     * @return array<string, array{date: \Cake\I18n\Date, items: list<\WorkReports\Model\Entity\WorkReportItem>, on_call: \WorkReports\Model\Entity\WorkReportOnCall|null}>
     */
    protected function daysOf(WorkReport $workReport): array
    {
        $days = [];
        $day = $workReport->month->firstOfMonth();
        $last = $workReport->month->lastOfMonth();
        while ($day <= $last) {
            $days[$day->format('Y-m-d')] = ['date' => $day, 'items' => [], 'on_call' => null];
            $day = $day->addDays(1);
        }

        foreach ($workReport->work_report_items as $item) {
            $key = $item->date->format('Y-m-d');
            if (isset($days[$key])) {
                $days[$key]['items'][] = $item;
            }
        }

        foreach ($workReport->work_report_on_calls as $onCall) {
            $key = $onCall->date->format('Y-m-d');
            if (isset($days[$key])) {
                $days[$key]['on_call'] = $onCall;
            }
        }

        return $days;
    }
}
