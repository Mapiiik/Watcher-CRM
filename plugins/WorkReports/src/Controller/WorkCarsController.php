<?php
declare(strict_types=1);

namespace WorkReports\Controller;

use Cake\ORM\Table;
use WorkReports\Controller\Trait\LookupControllerTrait;

/**
 * WorkCars Controller
 *
 * @property \WorkReports\Model\Table\WorkCarsTable $WorkCars
 */
class WorkCarsController extends AppController
{
    use LookupControllerTrait;

    /**
     * @inheritDoc
     */
    protected function lookupTable(): Table
    {
        return $this->WorkCars;
    }

    /**
     * @inheritDoc
     */
    protected function indexContain(): array
    {
        return ['Owners'];
    }

    /**
     * @inheritDoc
     */
    protected function setFormLists(): void
    {
        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');

        // a private car belongs to whoever reports work with it
        /** @var \WorkReports\Model\Table\WorkReportWorkersTable $workers */
        $workers = $this->fetchTable('WorkReports.WorkReportWorkers');
        // whoever owns the car stays on offer after leaving, an empty owner means a company car
        $owner = $this->viewBuilder()->getVar('record')?->get('owner_id');
        $this->set('owners', $this->usersForSelect(
            $users->find()->where(['OR' => ['Users.id IN' => $workers->activeIds(), 'Users.id IS' => $owner]]),
        ));
    }
}
