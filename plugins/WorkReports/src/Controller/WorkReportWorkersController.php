<?php
declare(strict_types=1);

namespace WorkReports\Controller;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use WorkReports\Controller\Trait\LookupControllerTrait;

/**
 * WorkReportWorkers Controller
 *
 * @property \WorkReports\Model\Table\WorkReportWorkersTable $WorkReportWorkers
 */
class WorkReportWorkersController extends AppController
{
    use LookupControllerTrait;

    /**
     * @inheritDoc
     */
    protected function lookupTable(): Table
    {
        return $this->WorkReportWorkers;
    }

    /**
     * @inheritDoc
     */
    protected function indexContain(): array
    {
        return ['Users', 'Recipients' => ['strategy' => 'select'], 'DefaultPrivateCars', 'DefaultCompanyCars'];
    }

    /**
     * View method
     *
     * @param string|null $id Work report worker id.
     * @return void Renders view
     */
    public function view(?string $id = null): void
    {
        $record = $this->WorkReportWorkers->get($id, contain: [
            'Users',
            'DefaultPrivateCars',
            'DefaultCompanyCars',
            'WorkReportWorkerRecipients' => ['Users', 'sort' => ['Users.last_name', 'Users.first_name']],
        ]);

        $this->set(compact('record'));
    }

    /**
     * @inheritDoc
     */
    protected function afterSaveUrl(EntityInterface $record): array
    {
        return ['action' => 'view', $record->get('id')];
    }

    /**
     * @inheritDoc
     */
    protected function setFormLists(): void
    {
        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');

        // what the row already holds stays on offer, or saving it would hand it to somebody else
        $record = $this->viewBuilder()->getVar('record');
        $people = $this->usersForSelect($users->find()->where(['OR' => [
            'Users.holds_tasks' => true,
            'Users.id IS' => $record?->get('user_id'),
        ]]));
        $privateCars = $this->WorkReportWorkers->DefaultPrivateCars->find('list')
            ->where(['DefaultPrivateCars.owner_id IS NOT' => null, 'OR' => [
                'DefaultPrivateCars.active' => true,
                'DefaultPrivateCars.id IS' => $record?->get('default_private_car_id'),
            ]])
            ->contain(['Owners']);
        $companyCars = $this->WorkReportWorkers->DefaultCompanyCars->find('company')
            ->find('list')
            ->where(['OR' => [
                'DefaultCompanyCars.active' => true,
                'DefaultCompanyCars.id IS' => $record?->get('default_company_car_id'),
            ]]);

        $this->set(compact('people', 'privateCars', 'companyCars'));
    }
}
