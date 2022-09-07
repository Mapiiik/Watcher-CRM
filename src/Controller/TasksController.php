<?php
declare(strict_types=1);

namespace App\Controller;

use App\ApiClient;
use Cake\Form\Form;
use Cake\I18n\FrozenTime;
use Cake\Mailer\Mailer;

/**
 * Tasks Controller
 *
 * @property \App\Model\Table\TasksTable $Tasks
 * @method \App\Model\Entity\Task[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class TasksController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        // persistent filter data
        if (!is_null($this->getRequest()->getQuery('dealer_id'))) {
            $this->getRequest()->getSession()->write(
                'Config.Tasks.filter.dealer_id',
                $this->getRequest()->getQuery('dealer_id')
            );
        }
        if (!is_null($this->getRequest()->getQuery('task_type_id'))) {
            $this->getRequest()->getSession()->write(
                'Config.Tasks.filter.task_type_id',
                $this->getRequest()->getQuery('task_type_id')
            );
        }
        if (!is_null($this->getRequest()->getQuery('access_point_id'))) {
            $this->getRequest()->getSession()->write(
                'Config.Tasks.filter.access_point_id',
                $this->getRequest()->getQuery('access_point_id')
            );
        }
        if (!is_null($this->getRequest()->getQuery('search'))) {
            $this->getRequest()->getSession()->write(
                'Config.Tasks.filter.search',
                $this->getRequest()->getQuery('search')
            );
        }
        $filter = $this->getRequest()->getSession()->read('Config.Tasks.filter');

        // filter
        $conditions = [];
        if (isset($customer_id)) {
            $conditions[] = [
                'Tasks.customer_id' => $customer_id,
            ];
        }

        $dealer_id = $filter['dealer_id'] ?? $this->getRequest()->getAttribute('identity')['customer_id'] ?? null;
        if (!empty($dealer_id)) {
            $conditions[] = [
                'Tasks.dealer_id' => $dealer_id,
            ];
        }
        $task_type_id = $filter['task_type_id'] ?? null;
        if (!empty($task_type_id)) {
            $conditions[] = [
                'Tasks.task_type_id' => $task_type_id,
            ];
        }
        $access_point_id = $filter['access_point_id'] ?? null;
        if (!empty($access_point_id)) {
            $conditions[] = [
                'Tasks.access_point_id' => $access_point_id,
            ];
        }
        $search = $filter['search'] ?? null;
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Tasks.subject ILIKE' => '%' . trim($search) . '%',
                    'Tasks.text ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        // filter form
        $filterForm = new Form();
        $filterForm->setData([
            'dealer_id' => $dealer_id,
            'task_type_id' => $task_type_id,
            'access_point_id' => $access_point_id,
            'search' => $search,
        ]);
        $this->set('filterForm', $filterForm);

        $this->paginate = [
            'contain' => ['TaskTypes', 'Customers', 'Dealers', 'TaskStates'],
            'order' => [
                'Tasks.task_state_id' => 'ASC',
                'Tasks.id' => 'DESC',
            ],
            'conditions' => $conditions,
        ];

        $tasks = $this->paginate($this->Tasks);
        $dealers = $this->Tasks->Dealers
            ->find('all')
            ->all()
            ->sortBy(function ($dealer) {
                return ($dealer->active ? '##' : '__') . $dealer->first_name . '-' . $dealer->last_name;
            }, SORT_ASC, SORT_LOCALE_STRING)
            ->map(function ($dealer) {
                return [
                    'value' => $dealer->id,
                    'text' => $dealer->name,
                    'style' => $dealer->active ? null : 'color: gray;',
                ];
            });
        $taskTypes = $this->Tasks->TaskTypes->find('list', ['order' => 'name']);

        $this->set(compact('tasks', 'taskTypes', 'dealers'));

        $this->set('priorities', $this->Tasks->priorities);

        // load access points from NMS if possible
        $accessPoints = ApiClient::getAccessPoints();
        if ($accessPoints) {
            $this->set('accessPoints', $accessPoints->sortBy('name', SORT_ASC, SORT_NATURAL)->combine('id', 'name'));
        } else {
            $this->Flash->warning(__('The access points list could not be loaded. Please, try again.'));
            $this->set('accessPoints', []);
        }
    }

    /**
     * View method
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $task = $this->Tasks->get($id, [
            'contain' => [
                'TaskTypes',
                'Customers',
                'Dealers',
                'TaskStates',
                'Creators',
                'Modifiers',
            ],
        ]);

        $this->set(compact('task'));

        $this->set('priorities', $this->Tasks->priorities);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $task = $this->Tasks->newEmptyEntity();

        if (isset($customer_id)) {
            $task->customer_id = $customer_id;
        }

        if ($this->getRequest()->is('post')) {
            $task = $this->Tasks->patchEntity($task, $this->getRequest()->getData());
            if ($this->Tasks->save($task)) {
                // send email notification
                if (
                    $task->has('dealer_id')
                    && $task->dealer_id != ($this->getRequest()->getAttribute('identity')['customer_id'] ?? null)
                ) {
                    $this->sendNotificationEmail(strval($task->id), true);
                }

                $this->Flash->success(__('The task has been saved.'));

                return $this->redirect(['action' => 'view', $task->id]);
            }
            $this->Flash->error(__('The task could not be saved. Please, try again.'));
        }
        $taskTypes = $this->Tasks->TaskTypes->find('list', ['order' => 'name']);
        $customers = $this->Tasks->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $dealers = $this->Tasks->Dealers
            ->find('all')
            ->all()
            ->sortBy(function ($dealer) {
                return ($dealer->active ? '##' : '__') . $dealer->first_name . '-' . $dealer->last_name;
            }, SORT_ASC, SORT_LOCALE_STRING)
            ->map(function ($dealer) {
                return [
                    'value' => $dealer->id,
                    'text' => $dealer->name,
                    'style' => $dealer->active ? null : 'color: gray;',
                ];
            });
        $taskStates = $this->Tasks->TaskStates->find('list', ['order' => 'name']);

        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);

            $customer = $this->Tasks->Customers->get($customer_id, ['contain' => ['Addresses', 'Phones', 'Emails']]);

            // preset subject
            if (empty($task->subject)) {
                $task->subject = $customer->number . ' - ' . $customer->name;
            }
            // preset email
            if (empty($task->email)) {
                $task->email = $customer->email;
            }
            // preset phone
            if (empty($task->phone)) {
                $task->phone = $customer->phone;
            }
            // add customer details to text
            if (empty($task->text)) {
                foreach ($customer->addresses as $address) {
                    // list all installation addresses
                    if ($address->type == 0) {
                        $task->text .= $this->Tasks->Customers->Addresses->types[$address->type] . ': ';
                        $task->text .= $address->full_address . PHP_EOL;
                    }
                }
                $task->text .= __('Email') . ': ' . $customer->email . PHP_EOL;
                $task->text .= __('Phone') . ': ' . $customer->phone . PHP_EOL;
            }
        }

        // preset start date
        if (empty($task->start_date)) {
            $task->start_date = FrozenTime::create();
        }
        // preset dealer
        if (empty($task->dealer_id)) {
            $task->dealer_id = $this->getRequest()->getAttribute('identity')['customer_id'] ?? null;
        }

        // add task text header
        $task->text .= $this->taskTextHeader();

        $this->set(compact('task', 'taskTypes', 'customers', 'dealers', 'taskStates'));

        $this->set('priorities', $this->Tasks->priorities);

        // load access points from NMS if possible
        $accessPoints = ApiClient::getAccessPoints();
        if ($accessPoints) {
            $this->set('accessPoints', $accessPoints->sortBy('name', SORT_ASC, SORT_NATURAL)->combine('id', 'name'));
        } else {
            $this->Flash->warning(__('The access points list could not be loaded. Please, try again.'));
            $this->set('accessPoints', []);
        }
    }

    /**
     * Edit method
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $task = $this->Tasks->get($id, [
            'contain' => [],
        ]);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $task = $this->Tasks->patchEntity($task, $this->getRequest()->getData());

            if ($this->getRequest()->getData('refresh') == 'refresh') {
                // only refresh
            } else {
                if ($this->Tasks->save($task)) {
                    // send email notification
                    if (
                        $task->has('dealer_id')
                        && $task->dealer_id != ($this->getRequest()->getAttribute('identity')['customer_id'] ?? null)
                    ) {
                        $this->sendNotificationEmail(strval($task->id), false);
                    }

                    $this->Flash->success(__('The task has been saved.'));

                    return $this->redirect(['action' => 'view', $task->id]);
                }
                $this->Flash->error(__('The task could not be saved. Please, try again.'));
            }
        }
        $taskTypes = $this->Tasks->TaskTypes->find('list', ['order' => 'name']);
        $customers = $this->Tasks->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $dealers = $this->Tasks->Dealers
            ->find('all')
            ->all()
            ->sortBy(function ($dealer) {
                return ($dealer->active ? '##' : '__') . $dealer->first_name . '-' . $dealer->last_name;
            }, SORT_ASC, SORT_LOCALE_STRING)
            ->map(function ($dealer) {
                return [
                    'value' => $dealer->id,
                    'text' => $dealer->name,
                    'style' => $dealer->active ? null : 'color: gray;',
                ];
            });
        $taskStates = $this->Tasks->TaskStates->find('list', ['order' => 'name']);

        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
        }

        // add task text header
        if (!empty($task->text)) {
            $task->text .= PHP_EOL . PHP_EOL;
        }
        $task->text .= $this->taskTextHeader();

        $this->set(compact('task', 'taskTypes', 'customers', 'dealers', 'taskStates'));

        $this->set('priorities', $this->Tasks->priorities);

        // load access points from NMS if possible
        $accessPoints = ApiClient::getAccessPoints();
        if ($accessPoints) {
            $this->set('accessPoints', $accessPoints->sortBy('name', SORT_ASC, SORT_NATURAL)->combine('id', 'name'));
        } else {
            $this->Flash->warning(__('The access points list could not be loaded. Please, try again.'));
            $this->set('accessPoints', []);
        }
    }

    /**
     * Delete method
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $task = $this->Tasks->get($id);
        if ($this->Tasks->delete($task)) {
            $this->Flash->success(__('The task has been deleted.'));
        } else {
            $this->Flash->error(__('The task could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Task text header method
     *
     * @return string Task text header.
     */
    private function taskTextHeader()
    {
        $text = '';

        $identity = $this->getRequest()->getAttribute('identity');
        $text .= '------------------------------------------------------------' . PHP_EOL;
        $text .= ' ' . ($identity['first_name'] ?? '') . ' ' . ($identity['last_name'] ?? '');
        $text .= ' (' . FrozenTime::create() . ')' . PHP_EOL;
        $text .= '------------------------------------------------------------' . PHP_EOL;
        unset($identity);

        return $text;
    }

    /**
     * Send a task notification email
     *
     * @param string|null $id Task id.
     * @param bool $new This is new task.
     * @return bool Successfull?
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    private function sendNotificationEmail($id = null, bool $new = false): bool
    {
        $task = $this->Tasks->get($id, [
            'contain' => [
                'TaskTypes',
                'TaskStates',
                'Customers',
                'Dealers' => ['Emails'],
                'Creators',
                'Modifiers',
            ],
        ]);

        $mailer = new Mailer('default');

        foreach ($task->dealer->emails as $email) {
            $mailer->addTo($email->email, $task->dealer->name);
        }

        if ($new) {
            $title = __('You have a new task # {0}', $task->id);
        } else {
            $title = __('You have changes in task # {0}', $task->id);
        }

        $mailer->setSubject($title . ' - ' . $task->subject);
        $mailer->setEmailFormat('html');

        $mailer->viewBuilder()
            ->setLayout('default')
            ->setTemplate('task-notification');

        $mailer->setViewVars(['title' => $title, 'task' => $task, 'priorities' => $this->Tasks->priorities]);

        try {
            $mailer->deliver();
            $this->Flash->success(__('Notification email sent.') . ' (' . $task->dealer->email . ')');

            return true;
        } catch (\Exception $e) {
            $this->Flash->error(__('The notification email could not be sent.') . ' (' . $e->getMessage() . ')');

            return false;
        }
    }
}
