<?php
declare(strict_types=1);

namespace WorkReports\Controller;

use App\Controller\AppController as BaseController;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\Query\SelectQuery;
use CakeDC\Auth\Traits\IsAuthorizedTrait;

class AppController extends BaseController
{
    use IsAuthorizedTrait;

    /**
     * The user signed in.
     *
     * @return string
     */
    protected function identityId(): string
    {
        return (string)$this->getRequest()->getAttribute('identity')['id'];
    }

    /**
     * Whether the user signed in sees the reports of everybody.
     *
     * @return bool
     */
    protected function seesEverybody(): bool
    {
        return ($this->getRequest()->getAttribute('identity')['role'] ?? null) === 'admin';
    }

    /**
     * Whether the user signed in may see the reports of the worker: their own if they are one,
     * those they get, and anybody's for an admin.
     *
     * A month belongs to whoever is on the list of workers. Somebody who is not on it has no
     * report to look at, admin or not, and the way there is not offered to them.
     *
     * @param string $userId Worker.
     * @return bool
     */
    protected function maySee(string $userId): bool
    {
        /** @var \WorkReports\Model\Table\WorkReportWorkersTable $workers */
        $workers = $this->fetchTable('WorkReports.WorkReportWorkers');

        if ($userId === $this->identityId()) {
            return $workers->isWorker($userId);
        }

        return $this->seesEverybody() || $workers->isRecipientOf($this->identityId(), $userId);
    }

    /**
     * Whether the user signed in may change the reports of the worker: their own while they
     * report work, those they get with the right to change them, and anybody's for an admin.
     *
     * A worker whose row has been switched off keeps the months they wrote, to look at. Writing
     * into them is then somebody else's to do.
     *
     * @param string $userId Worker.
     * @return bool
     */
    protected function mayEdit(string $userId): bool
    {
        /** @var \WorkReports\Model\Table\WorkReportWorkersTable $workers */
        $workers = $this->fetchTable('WorkReports.WorkReportWorkers');

        if ($userId === $this->identityId()) {
            return $workers->isActiveWorker($userId);
        }

        return $this->seesEverybody() || $workers->mayEdit($this->identityId(), $userId);
    }

    /**
     * Whether the user signed in may return a submitted report of the worker. The worker does not
     * return their own, unless they are an admin.
     *
     * @param string $userId Worker.
     * @return bool
     */
    protected function mayReopen(string $userId): bool
    {
        if ($this->seesEverybody()) {
            return true;
        }

        return $userId !== $this->identityId() && $this->mayEdit($userId);
    }

    /**
     * Whether the user signed in may say an item was invoiced. It is whoever may mark the work to
     * invoice in bulk, so that the permissions say it in one place.
     *
     * @return bool
     */
    protected function mayInvoice(): bool
    {
        return $this->isAuthorized([
            'plugin' => 'WorkReports',
            'controller' => 'WorkOverviews',
            'action' => 'markInvoiced',
        ]);
    }

    /**
     * Stops the request unless the user signed in may change the reports of the worker.
     *
     * @param string $userId Worker.
     * @return void
     * @throws \Cake\Http\Exception\ForbiddenException
     */
    protected function checkMayEdit(string $userId): void
    {
        if (!$this->mayEdit($userId)) {
            throw new ForbiddenException(__d('work_reports', 'These reports are not yours to change.'));
        }
    }

    /**
     * Stops the request unless the user signed in may see the reports of the worker.
     *
     * @param string $userId Worker.
     * @return void
     * @throws \Cake\Http\Exception\ForbiddenException
     */
    protected function checkMaySee(string $userId): void
    {
        if (!$this->maySee($userId)) {
            throw new ForbiddenException(__d('work_reports', 'These reports are not yours to see.'));
        }
    }

    /**
     * The workers whose reports the user signed in may see, for a select.
     *
     * @return list<array{value: string, text: string, style: string|null}>
     */
    protected function visibleWorkers(): array
    {
        /** @var \App\Model\Table\AppUsersTable $users */
        $users = $this->fetchTable('AppUsers');

        /** @var \WorkReports\Model\Table\WorkReportWorkersTable $workers */
        $workers = $this->fetchTable('WorkReports.WorkReportWorkers');

        // whoever reports their work, or of them those whose reports the user gets
        $others = $this->seesEverybody()
            ? ['AppUsers.id IN' => $workers->activeIds()]
            : ['AppUsers.id IN' => $workers->workersOf($this->identityId())];

        // and the user themselves, so that they get to their own months - but only if they keep any
        $own = $workers->isWorker($this->identityId())
            ? [['AppUsers.id' => $this->identityId()]]
            : [];

        return $this->usersForSelect($users->find()
            ->where(['OR' => array_merge([$others], $own)]));
    }

    /**
     * Users as the selects of tasks list them: the active ones first, those who can no longer
     * sign in greyed at the end.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Users to list.
     * @return list<array{value: string, text: string, style: string|null}>
     */
    protected function usersForSelect(SelectQuery $query): array
    {
        $list = [];
        $query->orderBy(['AppUsers.active' => 'DESC', 'AppUsers.last_name', 'AppUsers.first_name'], true);
        /** @var \App\Model\Entity\AppUser $user */
        foreach ($query->all() as $user) {
            $list[] = [
                'value' => $user->id,
                'text' => $user->name_for_lists,
                'style' => $user->active ? null : 'color: darkgray;',
            ];
        }

        return $list;
    }
}
