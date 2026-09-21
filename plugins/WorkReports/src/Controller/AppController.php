<?php
declare(strict_types=1);

namespace WorkReports\Controller;

use App\Controller\AppController as BaseController;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\Query\SelectQuery;

class AppController extends BaseController
{
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
     * Whether the user signed in may see the reports of the worker: their own, those of the
     * people they supervise, and anybody's for an admin.
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

        return $workers->isSupervisorOf($this->identityId(), $userId);
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

        if ($this->seesEverybody()) {
            // anybody who can be given work, which is who reports it
            $others = ['AppUsers.holds_tasks' => true];
        } else {
            /** @var \WorkReports\Model\Table\WorkReportWorkersTable $workers */
            $workers = $this->fetchTable('WorkReports.WorkReportWorkers');
            $others = ['AppUsers.id IN' => $workers->find()
                ->select(['user_id'])
                ->where(['supervisor_id' => $this->identityId()])];
        }

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
