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
        /** @var \App\Model\Table\AppUsersTable $users */
        $users = $this->fetchTable('AppUsers');

        // a private car belongs to whoever reports work with it
        /** @var \WorkReports\Model\Table\WorkReportWorkersTable $workers */
        $workers = $this->fetchTable('WorkReports.WorkReportWorkers');
        $this->set('owners', $this->usersForSelect(
            $users->find()->where(['AppUsers.id IN' => $workers->activeIds()]),
        ));
    }
}
