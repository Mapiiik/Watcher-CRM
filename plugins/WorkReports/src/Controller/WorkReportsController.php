<?php
declare(strict_types=1);

namespace WorkReports\Controller;

use App\Controller\Traits\MessageHandlerTrait;
use App\Messages\Messages;
use App\NMS\ApiClient;
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

        // asked of the NMS only when the month has any of its access points
        $accessPoints = array_filter(array_map(
            fn($item): ?string => $item->access_point_id,
            $workReport->work_report_items,
        )) === [] ? null : ApiClient::getAccessPointsList();

        $mayEdit = $this->mayEdit($userId);
        $running = $this->WorkReports->WorkReportItems->findRunning($userId);
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
            'accessPoints',
            'running',
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
            fn(Date $day): string => (string)$day,
            $days,
        ));

        if ($workReport->isLocked()) {
            $this->Flash->error(__d('work_reports', 'The report has already been submitted.'));
        } elseif ($workReport->hasRunningItem()) {
            $this->Flash->error(__d('work_reports', 'Some work is still going on. Finish it first.'));
        } elseif ($summary->missingDays !== []) {
            $this->Flash->error(__d('work_reports', 'Nothing is reported on {0}.', $days($summary->missingDays)));
        } else {
            $workReport->submitted = DateTime::now();
            $workReport->submitted_by = $this->identityId();
            $workReport->closed_until = $workReport->month->lastOfMonth();
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
     * Write the note of the month: what the numbers alone do not say.
     *
     * @param string|null $id Work report id.
     * @return \Cake\Http\Response|null Redirects to the month once written, renders the form otherwise.
     */
    public function note(?string $id = null): ?Response
    {
        $workReport = $this->WorkReports->get((string)$id, contain: ['Users']);
        $this->checkMayEdit($workReport->user_id);

        if ($workReport->isLocked()) {
            $this->Flash->error(__d('work_reports', 'The report has already been submitted.'));

            return $this->afterEditRedirect($this->sheetUrl($workReport));
        }

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $workReport = $this->WorkReports->patchEntity(
                $workReport,
                $this->getRequest()->getData(),
                ['fields' => ['note']],
            );

            if (!$workReport->hasErrors()) {
                $this->WorkReports->saveOrFail($workReport);
                $this->Flash->success(__d('work_reports', 'The note has been saved.'));

                return $this->afterEditRedirect($this->sheetUrl($workReport));
            }
        }

        $this->set(compact('workReport'));

        return null;
    }

    /**
     * Close the report up to a day: what is on it and before it stays as it was written, while the
     * days after it go on being filled in.
     *
     * The worker moves the day forward only. Whoever oversees the reports also moves it back.
     *
     * @param string|null $id Work report id.
     * @return \Cake\Http\Response|null Redirects to the month once closed, renders the form otherwise.
     */
    public function close(?string $id = null): ?Response
    {
        $workReport = $this->loadReport((string)$id);
        $this->checkMayEdit($workReport->user_id);

        if ($workReport->isLocked()) {
            $this->Flash->error(__d('work_reports', 'The report has already been submitted.'));

            return $this->afterEditRedirect($this->sheetUrl($workReport));
        }

        $closedUntil = $workReport->closed_until;
        $mayOpen = $this->seesEverybody();

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $workReport = $this->WorkReports->patchEntity(
                $workReport,
                $this->getRequest()->getData(),
                ['validate' => 'close', 'fields' => ['closed_until']],
            );
            $asked = $workReport->closed_until;

            if ($asked !== null && $asked->format('Y-m') !== $workReport->month->format('Y-m')) {
                $workReport->setError(
                    'closed_until',
                    ['inMonth' => __d('work_reports', 'The day is not in the month of the report.')],
                );
            } elseif ($asked !== null && $asked > Date::today()) {
                $workReport->setError(
                    'closed_until',
                    ['notAhead' => __d('work_reports', 'A day that has not come is not closed.')],
                );
            } elseif ($closedUntil !== null && !$mayOpen && ($asked === null || $asked < $closedUntil)) {
                $workReport->setError(
                    'closed_until',
                    ['forward' => __d('work_reports', 'Only a supervisor opens days that are closed.')],
                );
            } elseif ($asked !== null) {
                // a day left empty under the one being closed would stay empty, so it is asked
                // about now rather than at the end of the month
                $missing = WorkReportSummary::fromSettings($workReport)->missingDaysUpTo($asked);
                if ($missing !== []) {
                    $workReport->setError('closed_until', ['reported' => __d(
                        'work_reports',
                        'Nothing is reported on {0}.',
                        implode(', ', array_map(fn(Date $day): string => (string)$day, $missing)),
                    )]);
                }
            }

            if (!$workReport->hasErrors()) {
                $this->WorkReports->saveOrFail($workReport);
                $this->Flash->success($asked === null
                    ? __d('work_reports', 'The whole month is open again.')
                    : __d('work_reports', 'The report is closed up to {0}.', (string)$asked));

                return $this->afterEditRedirect($this->sheetUrl($workReport));
            }
        }

        $this->set(compact('workReport', 'mayOpen'));

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
