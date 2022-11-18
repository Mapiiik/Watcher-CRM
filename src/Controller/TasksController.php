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

        $contract_id = $this->getRequest()->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        // persistent filter data
        if (!is_null($this->getRequest()->getQuery('show_completed'))) {
            $this->getRequest()->getSession()->write(
                'Config.Tasks.filter.show_completed',
                $this->getRequest()->getQuery('show_completed')
            );
        }
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
        if (!is_null($this->getRequest()->getQuery('task_state_id'))) {
            $this->getRequest()->getSession()->write(
                'Config.Tasks.filter.task_state_id',
                $this->getRequest()->getQuery('task_state_id')
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
        if (isset($contract_id)) {
            $conditions[] = [
                'Tasks.contract_id' => $contract_id,
            ];
        }

        $show_completed = $filter['show_completed'] ?? null;
        if (empty($show_completed)) {
            $conditions[] = [
                'TaskStates.completed' => 0,
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
        $task_state_id = $filter['task_state_id'] ?? null;
        if (!empty($task_state_id)) {
            $conditions[] = [
                'Tasks.task_state_id' => $task_state_id,
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
            'show_completed' => $show_completed,
            'dealer_id' => $dealer_id,
            'task_type_id' => $task_type_id,
            'task_state_id' => $task_state_id,
            'access_point_id' => $access_point_id,
            'search' => $search,
        ]);
        $this->set('filterForm', $filterForm);

        $this->paginate = [
            'contain' => ['Customers', 'Contracts', 'Dealers', 'TaskTypes', 'TaskStates'],
            'order' => [
                'Tasks.task_state_id' => 'ASC',
                'Tasks.id' => 'DESC',
            ],
            'conditions' => $conditions,
        ];

        $tasks = $this->paginate($this->Tasks);
        $dealers = $this->Tasks->Dealers
            ->find('all')
            ->order(['dealer', 'company', 'last_name', 'first_name'])
            ->all()
            ->map(function ($dealer) {
                return [
                    'value' => $dealer->id,
                    'text' => $dealer->name_for_lists,
                    'style' => $dealer->dealer === 1 ? null : 'color: gray;',
                ];
            });
        $taskTypes = $this->Tasks->TaskTypes->find('list', ['order' => 'name']);
        $taskStates = $this->Tasks->TaskStates->find('list', ['order' => 'name']);

        $this->set(compact('tasks', 'taskTypes', 'taskStates', 'dealers'));

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
                'Contracts',
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

        $contract_id = $this->getRequest()->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $task = $this->Tasks->newEmptyEntity();

        if (isset($customer_id)) {
            $task->customer_id = $customer_id;
        }

        if (isset($contract_id)) {
            $task->contract_id = $contract_id;
        }

        if ($this->getRequest()->is('post')) {
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
                        $this->sendNotificationEmail(strval($task->id), true);
                    }

                    $this->Flash->success(__('The task has been saved.'));

                    return $this->redirect(['action' => 'view', $task->id]);
                }
                $this->Flash->error(__('The task could not be saved. Please, try again.'));
            }
        }
        $taskTypes = $this->Tasks->TaskTypes->find('list', ['order' => 'name']);
        $customers = $this->Tasks->Customers->find('list', ['order' => ['company', 'last_name', 'first_name']]);
        $contracts = [];
        $dealers = $this->Tasks->Dealers
            ->find('all')
            ->where(['dealer' => 1]) // only current dealers
            ->order(['dealer', 'company', 'last_name', 'first_name'])
            ->all()
            ->map(function ($dealer) {
                return [
                    'value' => $dealer->id,
                    'text' => $dealer->name_for_lists,
                    'style' => $dealer->dealer === 1 ? null : 'color: gray;',
                ];
            });
        $taskStates = $this->Tasks->TaskStates->find('list', ['order' => 'name']);

        // load customer data
        if (isset($task->customer_id)) {
            $customer = $this->Tasks->Customers->get($task->customer_id, [
                'contain' => ['Addresses', 'Phones', 'Emails'],
            ]);

            // retrieve list of customer contracts
            $contracts = $this->Tasks->Contracts
                ->find('list', [
                    'order' => 'Contracts.number',
                    'contain' => ['ServiceTypes', 'InstallationAddresses'],
                ])
                ->where(['Contracts.customer_id' => $task->customer_id]);
        }

        // load contract data
        if (isset($task->contract_id)) {
            $contract = $this->Tasks->Contracts->get($task->contract_id, [
                'contain' => ['InstallationAddresses'],
            ]);
        }

        if (isset($customer)) {
            // preset subject
            if (empty($task->subject)) {
                $task->subject = isset($contract) ?
                    $contract->number
                    . ' - ' . ($contract->installation_address->full_address ?? $customer->name)
                    . ', ' . $customer->phone
                    :
                    $customer->number
                    . ' - ' . $customer->name
                    . ', ' . $customer->phone;
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
                if (isset($contract)) {
                    // contract assigned
                    if (isset($contract->installation_address)) {
                        // add the installation address from the contract to the text
                        $task->text .= __('Installation Address') . ': ';
                        $task->text .= $contract->installation_address->full_address . PHP_EOL;
                    }
                } else {
                    // contract unknown
                    foreach ($customer->addresses as $address) {
                        // add all customer installation addresses to the text
                        if ($address->type === 0) {
                            $task->text .= $this->Tasks->Customers->Addresses->types[$address->type] . ': ';
                            $task->text .= $address->full_address . PHP_EOL;
                        }
                    }
                }
                $task->text .= __('Email') . ': ' . $customer->email . PHP_EOL;
                $task->text .= __('Phone') . ': ' . $customer->phone . PHP_EOL;
            }
        }

        // clear customer/contract variables
        unset($customer);
        unset($contract);

        if (isset($customer_id)) {
            $customers->where(['Customers.id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['Contracts.id' => $contract_id]);
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

        $this->set(compact('task', 'taskTypes', 'customers', 'contracts', 'dealers', 'taskStates'));

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

        $contract_id = $this->getRequest()->getParam('contract_id');
        $this->set('contract_id', $contract_id);

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
        $customers = $this->Tasks->Customers->find('list', ['order' => ['company', 'last_name', 'first_name']]);
        $contracts = [];
        $dealers = $this->Tasks->Dealers
            ->find('all')
            ->order(['dealer', 'company', 'last_name', 'first_name'])
            ->all()
            ->map(function ($dealer) {
                return [
                    'value' => $dealer->id,
                    'text' => $dealer->name_for_lists,
                    'style' => $dealer->dealer === 1 ? null : 'color: gray;',
                ];
            });
        $taskStates = $this->Tasks->TaskStates->find('list', ['order' => 'name']);

        if (isset($task->customer_id)) {
            $contracts = $this->Tasks->Contracts
                ->find('list', [
                    'order' => 'Contracts.number',
                    'contain' => ['ServiceTypes', 'InstallationAddresses'],
                ])
                ->where(['Contracts.customer_id' => $task->customer_id]);
        }

        if (isset($customer_id)) {
            $customers->where(['Customers.id' => $customer_id]);
        }

        if (isset($contract_id)) {
            $contracts->where(['Contracts.id' => $contract_id]);
        }

        // add task text header
        if (!empty($task->text)) {
            $task->text .= PHP_EOL . PHP_EOL;
        }
        $task->text .= $this->taskTextHeader();

        $this->set(compact('task', 'taskTypes', 'customers', 'contracts', 'dealers', 'taskStates'));

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
                'Contracts',
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
