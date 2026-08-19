<?php
declare(strict_types=1);

namespace Tasks\Controller\Trait;

use Cake\Http\Response;

/**
 * The actions a task state has, in both applications.
 *
 * The views come from this plugin, so the builder is pointed at it. An application that wants its
 * own puts them under `templates/plugin/Tasks/`.
 *
 * The one thing the two applications disagree about is what is read together with the record on
 * its own page, which {@see self::viewContain()} answers.
 *
 * @property \Cake\ORM\Table $TaskStates
 * @method \Cake\Http\Response|null afterAddRedirect(array|string $url)
 * @method \Cake\Http\Response|null afterEditRedirect(array|string $url)
 * @method \Cake\Http\Response|null afterDeleteRedirect(array|string $url)
 * @method void flashValidationErrors(array $errors)
 * @psalm-require-extends \Cake\Controller\Controller
 */
trait TaskStatesControllerTrait
{
    /**
     * What a state is read together with on its own page. The applications file tasks under
     * different things, so each names its own.
     *
     * @return array<mixed>
     */
    abstract protected function viewContain(): array;

    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        // filter
        $conditions = [];

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'TaskStates.name ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'name' => 'ASC',
            ],
        ];
        $taskStates = $this->paginate($this->TaskStates->find(
            'all',
            contain: [],
            conditions: $conditions,
        ));

        $this->viewBuilder()->setPlugin('Tasks');
        $this->set(compact('taskStates'));
    }

    /**
     * View method
     *
     * @param string|null $id Task State id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $taskState = $this->TaskStates->get($id, contain: $this->viewContain());

        $this->viewBuilder()->setPlugin('Tasks');
        $this->set(compact('taskState'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $taskState = $this->TaskStates->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $taskState = $this->TaskStates->patchEntity($taskState, $this->getRequest()->getData());
            if ($this->TaskStates->save($taskState)) {
                $this->Flash->success(__d('tasks', 'The task state has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $taskState->id]);
            }
            $this->Flash->error(__d('tasks', 'The task state could not be saved. Please, try again.'));
        }

        $this->viewBuilder()->setPlugin('Tasks');
        $this->set(compact('taskState'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Task State id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $taskState = $this->TaskStates->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $taskState = $this->TaskStates->patchEntity($taskState, $this->getRequest()->getData());
            if ($this->TaskStates->save($taskState)) {
                $this->Flash->success(__d('tasks', 'The task state has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $taskState->id]);
            }
            $this->Flash->error(__d('tasks', 'The task state could not be saved. Please, try again.'));
        }

        $this->viewBuilder()->setPlugin('Tasks');
        $this->set(compact('taskState'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Task State id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $taskState = $this->TaskStates->get($id);
        if ($this->TaskStates->delete($taskState)) {
            $this->Flash->success(__d('tasks', 'The task state has been deleted.'));
        } else {
            $this->flashValidationErrors($taskState->getErrors());
            $this->Flash->error(__d('tasks', 'The task state could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }
}
