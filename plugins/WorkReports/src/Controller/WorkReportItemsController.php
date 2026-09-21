<?php
declare(strict_types=1);

namespace WorkReports\Controller;

use App\Controller\Traits\CommonViewVarListsTrait;
use Cake\Http\Response;
use Cake\I18n\Date;
use PhpCollective\DecimalObject\Decimal;
use WorkReports\Model\Entity\WorkReportItem;

/**
 * WorkReportItems Controller
 *
 * @property \WorkReports\Model\Table\WorkReportItemsTable $WorkReportItems
 */
class WorkReportItemsController extends AppController
{
    use CommonViewVarListsTrait;

    /**
     * Add method
     *
     * The item goes to the report of the month its day falls in, which comes to be with it.
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $userId = (string)($this->getRequest()->getQuery('user_id') ?: $this->identityId());
        $this->checkMayEdit($userId);

        $item = $this->WorkReportItems->newEmptyEntity();
        $item->date = $this->dayFromQuery();
        $item->whole_day = false;
        $item->rate_multiplier = Decimal::create(1);
        if ($this->customer_id !== null) {
            $item->customer_id = $this->customer_id;
        }
        if ($this->contract_id !== null) {
            $item->contract_id = $this->contract_id;
        }
        $this->presetCars($item, $userId);

        if ($this->getRequest()->is('post')) {
            $item = $this->WorkReportItems->patchEntity($item, $this->formData());

            if ($this->getRequest()->getData('refresh') != 'refresh') {
                if ($item->date instanceof Date) {
                    $item->work_report_id = $this->WorkReportItems->WorkReports
                        ->findOrCreateFor($userId, $item->date)->id;
                }

                if ($this->WorkReportItems->save($item)) {
                    $this->Flash->success(__d('work_reports', 'The work report item has been saved.'));

                    return $this->afterAddRedirect($this->sheetUrl($userId, $item->date));
                }
                $this->flashValidationErrors($item->getErrors());
                $this->Flash->error(__d('work_reports', 'The work report item could not be saved. Please, try again.'));
            }
        }

        $this->setFormLists($item, $userId);

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Work report item id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $item = $this->WorkReportItems->get($id, contain: ['WorkReports', 'WorkLabels', 'Collaborators']);
        $userId = $item->work_report->user_id;
        $this->checkMayEdit($userId);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $item = $this->WorkReportItems->patchEntity($item, $this->formData());

            if ($this->getRequest()->getData('refresh') != 'refresh') {
                if ($this->WorkReportItems->save($item)) {
                    $this->Flash->success(__d('work_reports', 'The work report item has been saved.'));

                    return $this->afterEditRedirect($this->sheetUrl($userId, $item->date));
                }
                $this->flashValidationErrors($item->getErrors());
                $this->Flash->error(__d('work_reports', 'The work report item could not be saved. Please, try again.'));
            }
        }

        $this->setFormLists($item, $userId);

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Work report item id.
     * @return \Cake\Http\Response|null Redirects to the month.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $item = $this->WorkReportItems->get($id, contain: ['WorkReports']);
        $userId = $item->work_report->user_id;
        $this->checkMayEdit($userId);

        if ($this->WorkReportItems->delete($item)) {
            $this->Flash->success(__d('work_reports', 'The work report item has been deleted.'));
        } else {
            $this->flashValidationErrors($item->getErrors());
            $this->Flash->error(__d('work_reports', 'The work report item could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect($this->sheetUrl($userId, $item->date));
    }

    /**
     * What the form sent, without whether the item was invoiced unless that is the user's to say.
     *
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        $data = (array)$this->getRequest()->getData();
        if (!$this->mayInvoice()) {
            unset($data['invoiced']);
        }

        return $data;
    }

    /**
     * The day asked for, today when none is.
     *
     * @return \Cake\I18n\Date
     */
    protected function dayFromQuery(): Date
    {
        $date = $this->getRequest()->getQuery('date');

        return is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? new Date($date) : Date::today();
    }

    /**
     * The cars the worker drives unless they say otherwise.
     *
     * @param \WorkReports\Model\Entity\WorkReportItem $item Item to preset.
     * @param string $userId Worker.
     * @return void
     */
    protected function presetCars(WorkReportItem $item, string $userId): void
    {
        /** @var \WorkReports\Model\Table\WorkReportWorkersTable $workers */
        $workers = $this->fetchTable('WorkReports.WorkReportWorkers');
        /** @var \WorkReports\Model\Entity\WorkReportWorker|null $worker */
        $worker = $workers->find()->where(['user_id' => $userId])->first();
        if ($worker === null) {
            return;
        }

        $item->private_car_id = $worker->default_private_car_id;
        $item->company_car_id = $worker->default_company_car_id;
    }

    /**
     * The month the item belongs to.
     *
     * @param string $userId Worker.
     * @param \Cake\I18n\Date|null $day Day of the item.
     * @return array<string, mixed>
     */
    protected function sheetUrl(string $userId, ?Date $day): array
    {
        return [
            'controller' => 'WorkReports',
            'action' => 'sheet',
            '?' => ['user_id' => $userId, 'month' => ($day ?? Date::today())->format('Y-m')],
        ];
    }

    /**
     * What the form offers to choose from.
     *
     * @param \WorkReports\Model\Entity\WorkReportItem $item Item being edited.
     * @param string $userId Worker.
     * @return void
     */
    protected function setFormLists(WorkReportItem $item, string $userId): void
    {
        $table = $this->WorkReportItems;

        $types = [];
        $timeMode = null;
        /** @var iterable<\WorkReports\Model\Entity\WorkReportItemType> $typeRecords */
        $typeRecords = $table->WorkReportItemTypes->find()
            ->where(['OR' => ['active' => true, 'id IS' => $item->work_report_item_type_id]])
            ->orderBy(['position', 'name'])
            ->all();
        foreach ($typeRecords as $typeRecord) {
            $types[] = ['value' => $typeRecord->id, 'text' => $typeRecord->name];
            if ($typeRecord->id === $item->work_report_item_type_id) {
                $timeMode = $typeRecord->time_mode;
            }
        }

        $customers = $table->Customers->find('list', order: ['company', 'last_name', 'first_name']);
        if ($this->customer_id !== null) {
            $customers->where(['Customers.id' => $this->customer_id]);
        }

        $contracts = [];
        $tasks = [];
        if ($item->customer_id !== null) {
            $contracts = $table->Contracts->find(
                'list',
                contain: ['InstallationAddresses', 'ServiceTypes'],
                conditions: ['Contracts.customer_id' => $item->customer_id],
                order: ['Contracts.number'],
            );
            $tasks = $table->Tasks->find(
                'list',
                valueField: 'name_for_lists',
                contain: ['TaskTypes'],
                conditions: ['Tasks.customer_id' => $item->customer_id],
                order: ['Tasks.created' => 'DESC'],
            );
        }

        $privateCars = $table->PrivateCars->find('private', ownerId: $userId)
            ->find('list')
            ->where(['OR' => ['PrivateCars.active' => true, 'PrivateCars.id IS' => $item->private_car_id]]);
        $companyCars = $table->CompanyCars->find('company')
            ->find('list')
            ->where(['OR' => ['CompanyCars.active' => true, 'CompanyCars.id IS' => $item->company_car_id]]);
        $workRates = $table->WorkRates->find('list', order: ['code'])
            ->where(['OR' => ['WorkRates.active' => true, 'WorkRates.id IS' => $item->work_rate_id]]);
        $workLabels = $table->WorkLabels->find('list', order: ['name'])
            ->where(['WorkLabels.active' => true]);

        /** @var \App\Model\Table\AppUsersTable $users */
        $users = $this->fetchTable('AppUsers');
        $collaborators = $this->usersForSelect($users->find('holdingTasks')
            ->where(['AppUsers.id !=' => $userId]));

        $mayInvoice = $this->mayInvoice();

        $this->set(compact(
            'mayInvoice',
            'item',
            'userId',
            'types',
            'timeMode',
            'customers',
            'contracts',
            'tasks',
            'privateCars',
            'companyCars',
            'workRates',
            'workLabels',
            'collaborators',
        ));

        $this->setAccessPointsViewVarList(onlyActive: true);
    }
}
