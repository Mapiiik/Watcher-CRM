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
     * Whether the user signed in may see the reports of the worker: their own, those they get,
     * and anybody's for an admin.
     *
     * @param string $userId Worker.
     * @return bool
     */
    protected function maySee(string $userId): bool
    {
        if ($userId === $this->identityId() || $this->seesEverybody()) {
            return true;
        }

        /** @var \WorkReports\Model\Table\WorkReportWorkersTable $workers */
        $workers = $this->fetchTable('WorkReports.WorkReportWorkers');

        return $workers->isRecipientOf($this->identityId(), $userId);
    }

    /**
     * Whether the user signed in may change the reports of the worker: their own, those they get
     * with the right to change them, and anybody's for an admin.
     *
     * @param string $userId Worker.
     * @return bool
     */
    protected function mayEdit(string $userId): bool
    {
        if ($userId === $this->identityId() || $this->seesEverybody()) {
            return true;
        }

        /** @var \WorkReports\Model\Table\WorkReportWorkersTable $workers */
        $workers = $this->fetchTable('WorkReports.WorkReportWorkers');

        return $workers->mayEdit($this->identityId(), $userId);
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
     * The workers whose reports the user signed in may see, for a select. Whoever is signed in
     * is on the list even when they report no work, so that they get to their own month.
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

        return $this->usersForSelect($users->find()
            ->where(['OR' => [['AppUsers.id' => $this->identityId()], $others]]));
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
