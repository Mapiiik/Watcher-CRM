<?php
declare(strict_types=1);

namespace App\Controller;

use App\ApiClient;
use App\Mailer\QueueMailer;
use Cake\Utility\Text;
use SplObjectStorage;

/**
 * Billings Controller
 *
 * @property \App\Model\Table\BillingsTable $Billings
 * @method \App\Model\Entity\Billing[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class BillingsController extends AppController
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

        // filter
        $conditions = [];
        if (isset($customer_id)) {
            $conditions += ['Billings.customer_id' => $customer_id];
        }
        if (isset($contract_id)) {
            $conditions += ['Billings.contract_id' => $contract_id];
        }

        // search
        $search = $this->request->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Billings.text ILIKE' => '%' . trim($search) . '%',
                    'Services.name ILIKE' => '%' . trim($search) . '%',
                    'Contracts.number ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $billings = $this->paginate($this->Billings->find(
            'all',
            contain: [
                'Contracts' => [
                    'ContractStates',
                ],
                'Customers',
                'Services',
            ],
            conditions: $conditions
        ));

        $this->set(compact('billings'));
    }

    /**
     * View method
     *
     * @param string|null $id Billing id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $billing = $this->Billings->get($id, contain: [
            'Contracts' => ['ContractStates'],
            'Customers',
            'Services',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('billing'));
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

        $billing = $this->Billings->newEmptyEntity();

        if (isset($customer_id)) {
            $billing->customer_id = $customer_id;
        }
        if (isset($contract_id)) {
            $billing->contract_id = $contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $billing = $this->Billings->patchEntity($billing, $this->getRequest()->getData());

            if ($this->getRequest()->getData('refresh') == 'refresh') {
                // only refresh
            } elseif (
                !isset($billing->service_id) && !isset($billing->text)
            ) {
                $this->Flash->error(__('Billing text must be entered or service selected.'));
            } elseif (
                isset($billing->contract_id) && isset($billing->service_id)
                && isset($this->Billings->Services->get($billing->service_id)->service_type_id)
                && (
                    $this->Billings->Contracts->get($billing->contract_id)->service_type_id
                    <> $this->Billings->Services->get($billing->service_id)->service_type_id
                )
            ) {
                $this->Flash->error(__('The service type does not match the selected contract.'));
            } else {
                if ($this->Billings->save($billing)) {
                    $this->Flash->success(__('The billing has been saved.'));

                    return $this->redirect(['action' => 'index']);
                }
                $this->Flash->error(__('The billing could not be saved. Please, try again.'));
            }
        }
        $customers = $this->Billings->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        $contracts = $this->Billings->Contracts->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );
        $services = $this->Billings->Services->find('list', order: [
            'name',
        ]);

        if (isset($customer_id)) {
            $customers->where(['Customers.id' => $customer_id]);
            $contracts->where(['Contracts.customer_id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['Contracts.id' => $contract_id]);
            $services->where(['OR' => [
                'service_type_id' => $this->Billings->Contracts->get($contract_id)->service_type_id,
                'service_type_id IS NULL',
            ]]);
        } elseif (isset($billing->contract_id)) {
            $services->where(['OR' => [
                'service_type_id' => $this->Billings->Contracts->get($billing->contract_id)->service_type_id,
                'service_type_id IS NULL',
            ]]);
        }

        // only services available for new customers
        $services->andWhere(['Services.not_for_new_customers' => false]);

        $this->set(compact('billing', 'customers', 'services', 'contracts'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Billing id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract_id = $this->getRequest()->getParam('contract_id');
        $this->set('contract_id', $contract_id);

        $billing = $this->Billings->get($id, contain: []);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $billing = $this->Billings->patchEntity($billing, $this->getRequest()->getData());

            if ($this->getRequest()->getData('refresh') == 'refresh') {
                // only refresh
            } elseif (
                !isset($billing->service_id) && !isset($billing->text)
            ) {
                $this->Flash->error(__('Billing text must be entered or service selected.'));
            } elseif (
                isset($billing->contract_id) && isset($billing->service_id)
                && isset($this->Billings->Services->get($billing->service_id)->service_type_id)
                && (
                    $this->Billings->Contracts->get($billing->contract_id)->service_type_id
                    <> $this->Billings->Services->get($billing->service_id)->service_type_id
                )
            ) {
                $this->Flash->error(__('The service type does not match the selected contract.'));
            } else {
                if ($this->Billings->save($billing)) {
                    $this->Flash->success(__('The billing has been saved.'));

                    return $this->redirect(['action' => 'index']);
                }
                $this->Flash->error(__('The billing could not be saved. Please, try again.'));
            }
        }
        $customers = $this->Billings->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        $contracts = $this->Billings->Contracts->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );
        $services = $this->Billings->Services->find('list', order: [
            'name',
        ]);

        if (isset($customer_id)) {
            $customers->where(['Customers.id' => $customer_id]);
            $contracts->where(['Contracts.customer_id' => $customer_id]);
        }
        if (isset($contract_id)) {
            $contracts->where(['Contracts.id' => $contract_id]);
            $services->where(['OR' => [
                'service_type_id' => $this->Billings->Contracts->get($contract_id)->service_type_id,
                'service_type_id IS NULL',
            ]]);
        } elseif (isset($billing->contract_id)) {
            $services->where(['OR' => [
                'service_type_id' => $this->Billings->Contracts->get($billing->contract_id)->service_type_id,
                'service_type_id IS NULL',
            ]]);
        }

        $this->set(compact('billing', 'customers', 'services', 'contracts'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Billing id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $contract_id = $this->getRequest()->getParam('contract_id');

        $this->getRequest()->allowMethod(['post', 'delete']);
        $billing = $this->Billings->get($id);
        if ($this->Billings->delete($billing)) {
            $this->Flash->success(__('The billing has been deleted.'));
        } else {
            $this->Flash->error(__('The billing could not be deleted. Please, try again.'));
        }

        if (isset($contract_id)) {
            return $this->redirect(['controller' => 'Contracts', 'action' => 'view', $contract_id]);
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Bulk Service Change method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function bulkServiceChange()
    {
        // query
        $billingsQuery = $this->Billings->find(
            'all',
            contain: [
                'Contracts' => [
                    'ContractStates',
                    'InstallationAddresses',
                ],
                'Customers' => [
                    'Emails',
                ],
                'Services',
            ],
        );

        // filter
        $original_service_id = $this->request->getQuery('original_service_id');
        if (!empty($original_service_id)) {
            $billingsQuery->where(['Billings.service_id' => $original_service_id]);
        } else {
            $billingsQuery->where(['FALSE']);
        }

        $active_on_date = $this->request->getQuery('active_on_date');
        if (!empty($active_on_date)) {
            $billingsQuery->where([
                'Billings.billing_from <=' => $active_on_date,
                'OR' => [
                    'Billings.billing_until IS NULL',
                    'Billings.billing_until >=' => $active_on_date,
                ],
            ]);
        } else {
            $billingsQuery->where(['FALSE']);
        }

        $standard_prices_only = $this->request->getQuery('standard_prices_only');
        if ($standard_prices_only !== '0') {
            $billingsQuery->where([
                'Billings.price IS NULL',
                'Billings.fixed_discount IS NULL',
                'Billings.percentage_discount IS NULL',
            ]);
        }

        $processing_limit = $this->request->getQuery('processing_limit');
        if (is_numeric($processing_limit)) {
            $billingsQuery->limit((int)$processing_limit);
        }

        $access_point_id = $this->request->getQuery('access_point_id');
        if (!empty($access_point_id)) {
            $billingsQuery->where(['Contracts.access_point_id' => $access_point_id]);
        }

        // get data
        $billings = $billingsQuery->all();
        $services = $this->Billings->Services->find('list', order: ['name'])->all();

        // load access points from NMS if possible
        $accessPoints = ApiClient::getAccessPoints();
        if ($accessPoints) {
            $this->set('accessPoints', $accessPoints->sortBy('name', SORT_ASC, SORT_NATURAL)->combine('id', 'name'));
        } else {
            $this->Flash->warning(__('The access points list could not be loaded. Please, try again.'));
            $this->set('accessPoints', []);
        }

        // process change request
        if ($this->getRequest()->is('post')) {
            foreach ($billings as $original_billing) {
                /** @var \App\Model\Entity\Billing $original_billing */

                // create new billing entity
                $original_billing_data = $original_billing->toArray();
                unset(
                    $original_billing_data['id'],
                    $original_billing_data['created'],
                    $original_billing_data['created_by'],
                    $original_billing_data['modified'],
                    $original_billing_data['modified_by'],
                    $original_billing_data['contract'],
                    $original_billing_data['customer'],
                    $original_billing_data['service'],
                );
                $new_billing = $this->Billings->newEntity($original_billing_data);
                $new_billing = $this->Billings->patchEntity($new_billing, $this->getRequest()->getData());
                $new_billing->service = $this->Billings->Services->get($new_billing->service_id); // load associated service

                // update original entity
                $original_billing = $this->Billings->patchEntity($original_billing, [
                    'billing_until' => $new_billing->billing_from->subDays(1),
                ]);

                // save new and modified entity to database
                if (
                    $this->Billings->saveMany(
                        [
                            $original_billing,
                            $new_billing,
                        ],
                        [
                            '_auditQueue' => new SplObjectStorage(),
                            '_auditTransaction' => Text::uuid(),
                        ]
                    ) === false
                ) {
                    $this->Flash->error(
                        __('The billing could not be saved. Please, try again.')
                        . ' (' . __('Contract Number') . ': ' . $original_billing->contract->number . ')'
                    );

                    return $this->redirect([]);
                } else {
                    $mailer = new QueueMailer();
                    $mailer->push(
                        'serviceChange',
                        [
                            array_column($original_billing->customer->billing_emails, 'email'),
                            [
                                'customer_name' => $original_billing->customer->name,
                                'contract_number' => $original_billing->contract->number,
                                'installation_address' => $original_billing->contract->installation_address->address,
                                'original_billing_name' => $original_billing->name,
                                'original_billing_sum' => $original_billing->sum,
                                'new_billing_name' => $new_billing->name,
                                'new_billing_sum' => $new_billing->sum,
                                'new_billing_from' => h($new_billing->billing_from),
                            ],
                        ],
                        options: [
                            'mailerConfig' => 'contracts',
                        ]
                    );
                }

                unset($original_billing);
                unset($new_billing);
            }

            $this->Flash->success(__('The billing has been saved.'));

            return $this->redirect([]);
        }

        // set data
        $this->set(compact('billings', 'services'));
    }
}
