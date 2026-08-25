<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\Http\Exception\BadRequestException;
use Cake\Validation\Validation;

/**
 * Tasks Controller
 *
 * This application is where the tasks of the company are kept, so this is where another one asks
 * about them. What it may ask is narrow on purpose: {@see self::search()} takes the same cuts the
 * task listing offers rather than a query of the caller's own devising.
 *
 * @property \App\Model\Table\TasksTable $Tasks
 */
class TasksController extends AppController
{
    /**
     * What a task is read together with, wherever one is handed out.
     *
     * The account is cut down to what names the person. The rest of it - the address to reach
     * them at, what they may do here, how they have their own dashboard - is this application's
     * business and has nothing to do with the task.
     *
     * @var array<mixed>
     */
    private const CONTAIN = [
        'TaskTypes',
        'TaskStates',
        'Users' => ['fields' => ['id', 'username', 'first_name', 'last_name']],
    ];

    /**
     * Unfinished before finished, and the pressing before the rest.
     *
     * By the state rather than by whether it is finished, which keeps the order among the
     * unfinished ones as well. Whoever lists these beside a record of their own gets the same
     * order as they would here.
     *
     * @var array<string, string>
     */
    private const ORDER = [
        'TaskStates.priority' => 'DESC',
        'Tasks.priority' => 'DESC',
        'Tasks.nid' => 'DESC',
    ];

    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        $tasks = $this->Tasks
            ->find('all', contain: self::CONTAIN, order: self::ORDER)
            ->all();

        $this->set('tasks', $tasks);
        $this->viewBuilder()->setOption('serialize', ['tasks']);
    }

    /**
     * Search method
     *
     * Every parameter only narrows, so asking for none of them is asking for all the tasks. Each
     * one stands for a finder the task listing already uses - what is offered here is the same
     * set of cuts, not a second way of choosing.
     *
     * `total` is counted before the limit, so a caller drawing the first few of them can say how
     * many there are in all.
     *
     * @return void Renders view
     */
    public function search(): void
    {
        $query = $this->Tasks->find(contain: self::CONTAIN);
        $request = $this->getRequest();

        $accessPointId = $request->getQuery('access_point_id');
        if (is_string($accessPointId) && $accessPointId !== '') {
            if (!Validation::uuid($accessPointId)) {
                throw new BadRequestException(__('That is not the identifier of an access point.'));
            }

            $query->where(['Tasks.access_point_id' => $accessPointId]);
        }

        if ($request->getQuery('active') !== null) {
            $query->find('active');
        }

        if ($request->getQuery('unassigned') !== null) {
            $query->find('unassigned');
        }

        $pressing = $request->getQuery('pressing');
        if ($pressing !== null) {
            $query->find('pressing', within_days: (int)$pressing);
        }

        $stale = $request->getQuery('stale');
        if ($stale !== null) {
            $query->find('stale', days: (int)$stale);
        }

        $username = $request->getQuery('user');
        if (is_string($username) && $username !== '') {
            $user = $this->Tasks->Users->find()
                ->where(['Users.username' => $username])
                ->first();

            // A name this application has never heard of holds no tasks here. That is an answer,
            // not a fault - the caller asked whose they were, and they are nobody's.
            if ($user === null) {
                $this->answerWith([], 0);

                return;
            }

            $query->find('forUser', user_id: (string)$user->get('id'));
        }

        $total = $query->count();

        $limit = $request->getQuery('limit');
        if ($limit !== null) {
            $query->limit(max(0, (int)$limit));
        }

        $this->answerWith(array_values($query->orderBy(self::ORDER)->all()->toList()), $total);
    }

    /**
     * View method
     *
     * @param string|null $id Task id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $task = $this->Tasks->get($id, contain: self::CONTAIN);

        $this->set('task', $task);
        $this->viewBuilder()->setOption('serialize', ['task']);
    }

    /**
     * Add method
     *
     * @return void Redirects on successful add, renders view otherwise.
     */
    public function add(): void
    {
        $this->getRequest()->allowMethod(['post', 'put']);
        $task = $this->Tasks->newEntity($this->getRequest()->getData());
        $message = $this->Tasks->save($task) ? 'Saved' : 'Error';
        $this->set([
            'message' => $message,
            'task' => $task,
        ]);
        $this->viewBuilder()->setOption('serialize', ['task', 'message']);
    }

    /**
     * Edit method
     *
     * @param string|null $id Task id.
     * @return void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): void
    {
        $this->getRequest()->allowMethod(['patch', 'post', 'put']);
        $task = $this->Tasks->get($id);
        $task = $this->Tasks->patchEntity($task, $this->getRequest()->getData());

        $message = $this->Tasks->save($task) ? 'Saved' : 'Error';
        $this->set([
            'message' => $message,
            'task' => $task,
        ]);
        $this->viewBuilder()->setOption('serialize', ['task', 'message']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Task id.
     * @return void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): void
    {
        $this->getRequest()->allowMethod(['delete']);
        $task = $this->Tasks->get($id);
        $message = $this->Tasks->delete($task) ? 'Deleted' : 'Error';
        $this->set('message', $message);
        $this->viewBuilder()->setOption('serialize', ['message']);
    }

    /**
     * The one shape a search comes back in, whether anything was found or not.
     *
     * @param list<\Cake\Datasource\EntityInterface> $tasks The tasks to hand over.
     * @param int $total How many there were before any limit.
     * @return void
     */
    private function answerWith(array $tasks, int $total): void
    {
        $this->set([
            'tasks' => $tasks,
            'total' => $total,
        ]);
        $this->viewBuilder()->setOption('serialize', ['tasks', 'total']);
    }
}
