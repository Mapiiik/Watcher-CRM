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
        /** @var \App\Model\Table\AppUsersTable $users */
        $users = $this->fetchTable('AppUsers');

        $people = $this->usersForSelect($users->find('holdingTasks'));
        $privateCars = $this->WorkReportWorkers->DefaultPrivateCars->find('list')
            ->where(['DefaultPrivateCars.owner_id IS NOT' => null, 'DefaultPrivateCars.active' => true])
            ->contain(['Owners']);
        $companyCars = $this->WorkReportWorkers->DefaultCompanyCars->find('company')
            ->find('list')
            ->where(['DefaultCompanyCars.active' => true]);

        $this->set(compact('people', 'privateCars', 'companyCars'));
    }
}
