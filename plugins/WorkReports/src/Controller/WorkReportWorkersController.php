<?php
declare(strict_types=1);

namespace WorkReports\Controller;

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
        return ['Users', 'Supervisors', 'DefaultPrivateCars', 'DefaultCompanyCars'];
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
