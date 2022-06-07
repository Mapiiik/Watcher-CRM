<?php
declare(strict_types=1);

namespace App\Controller;

use App\ApiClient;
use Cake\I18n\FrozenTime;

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

        // filter
        $conditions = [];
        if (isset($customer_id)) {
            $conditions[] = [
                'Tasks.customer_id' => $customer_id,
            ];
        }
        $dealer_id = $this->getRequest()->getQuery('dealer_id');
        if (!empty($dealer_id)) {
            $conditions[] = [
                'Tasks.dealer_id' => $dealer_id,
            ];
        }
        $task_type_id = $this->getRequest()->getQuery('task_type_id');
        if (!empty($task_type_id)) {
            $conditions[] = [
                'Tasks.task_type_id' => $task_type_id,
            ];
        }
        $access_point_id = $this->getRequest()->getQuery('access_point_id');
        if (!empty($access_point_id)) {
            $conditions[] = [
                'Tasks.access_point_id' => $access_point_id,
            ];
        }

        // initially load only own tasks if assigned Auth.customer_id
        if (is_null($dealer_id)) {
            if ($this->getRequest()->getSession()->read('Auth.customer_id') !== null) {
                return $this->redirect([
                    '?' => [
                        'dealer_id' => $this->getRequest()->getSession()->read('Auth.customer_id'),
                    ] + $this->getRequest()->getQueryParams(),
                ]);
            }
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Tasks.subject ILIKE' => '%' . trim($search) . '%',
                    'Tasks.text ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

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
            'contain' => ['TaskTypes', 'Customers', 'Dealers', 'TaskStates'],
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
                $this->Flash->success(__('The task has been saved.'));

                return $this->redirect(['action' => 'view', $task->id]);
            }
            $this->Flash->error(__('The task could not be saved. Please, try again.'));
        }
        $taskTypes = $this->Tasks->TaskTypes->find('list', ['order' => 'name']);
        $customers = $this->Tasks->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $dealers = $this->Tasks->Dealers
            ->find('all')
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
            $task->dealer_id = $this->getRequest()->getSession()->read('Auth.customer_id');
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
            if ($this->Tasks->save($task)) {
                $this->Flash->success(__('The task has been saved.'));

                return $this->redirect(['action' => 'view', $task->id]);
            }
            $this->Flash->error(__('The task could not be saved. Please, try again.'));
        }
        $taskTypes = $this->Tasks->TaskTypes->find('list', ['order' => 'name']);
        $customers = $this->Tasks->Customers->find('list', ['order' => ['company', 'first_name', 'last_name']]);
        $dealers = $this->Tasks->Dealers
            ->find('all')
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

        $session = $this->getRequest()->getSession();
        $text .= '------------------------------------------------------------' . PHP_EOL;
        $text .= ' ' . $session->read('Auth.first_name') . ' ' . $session->read('Auth.last_name');
        $text .= ' (' . FrozenTime::create() . ')' . PHP_EOL;
        $text .= '------------------------------------------------------------' . PHP_EOL;
        unset($session);

        return $text;
    }
}
