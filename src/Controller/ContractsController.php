<?php
declare(strict_types=1);

namespace App\Controller;

use App\ApiClient;
use Cake\Collection\Collection;
use Cake\Database\Exception\MissingConnectionException;
use Cake\Database\Query;
use Cake\I18n\FrozenDate;
use stdClass;

/**
 * Contracts Controller
 *
 * @property \App\Model\Table\ContractsTable $Contracts
 * @method \App\Model\Entity\Contract[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ContractsController extends AppController
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
            $conditions = ['Contracts.customer_id' => $customer_id];
        }

        // search
        $search = $this->request->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Contracts.number ILIKE' => '%' . trim($search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'contain' => [
                'Customers',
                'ContractStates',
                'InstallationAddresses',
                'ServiceTypes',
                'InstallationTechnicians',
                'UninstallationTechnicians',
                'Commissions',
            ],
            'order' => ['id' => 'DESC'],
            'conditions' => $conditions,
        ];
        $contracts = $this->paginate($this->Contracts);

        $this->set(compact('contracts'));
    }

    /**
     * View method
     *
     * @param string|null $id Contract id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $contract = $this->Contracts->get($id, [
            'contain' => [
                'Customers' => [
                    'CustomerLabels' => [
                        'Labels',
                        'sort' => [
                            'Labels.name',
                        ],
                    ],
                    'Tasks' => [
                        'TaskTypes',
                        'TaskStates',
                        'Contracts',
                        'Dealers',
                        'conditions' => [
                            'OR' => [
                                'Tasks.contract_id !=' => $id,
                                'Tasks.contract_id IS' => null,
                            ],
                        ],
                    ],
                ],
                'ContractStates',
                'ContractVersions',
                'InstallationAddresses',
                'ServiceTypes',
                'InstallationTechnicians',
                'UninstallationTechnicians',
                'Commissions',
                'Billings' => [
                    'Contracts' => ['ContractStates'],
                    'Services',
                ],
                'BorrowedEquipments' => [
                    'EquipmentTypes',
                ],
                'Ips',
                'RemovedIps',
                'IpNetworks',
                'RemovedIpNetworks',
                'SoldEquipments' => [
                    'EquipmentTypes',
                ],
                'Tasks' => [
                    'TaskTypes',
                    'TaskStates',
                    'Dealers',
                ],
                'Creators',
                'Modifiers',
            ],
        ]);

        $this->set('ip_address_types_of_use', $this->Contracts->Ips->types_of_use);
        $this->set('ip_network_types_of_use', $this->Contracts->IpNetworks->types_of_use);

        $this->set(compact('contract'));
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

        $contract = $this->Contracts->newEmptyEntity();

        if (isset($customer_id)) {
            $contract->customer_id = $customer_id;
        }

        if ($this->getRequest()->is('post')) {
            $contract = $this->Contracts->patchEntity($contract, $this->getRequest()->getData());
            if ($this->Contracts->save($contract)) {
                $this->Flash->success(__('The contract has been saved.'));

                $this->updateNumber($contract->id);

                return $this->redirect(['action' => 'view', $contract->id]);
            }
            $this->Flash->error(__('The contract could not be saved. Please, try again.'));
        }
        $customers = $this->Contracts->Customers->find('list', ['order' => ['company', 'last_name', 'first_name']]);
        $contractStates = $this->Contracts->ContractStates->find('list', ['order' => 'name']);
        $installationAddresses = $this->Contracts->InstallationAddresses->find('list', [
            'order' => ['company', 'last_name', 'first_name'],
        ]);
        $serviceTypes = $this->Contracts->ServiceTypes->find('list', ['order' => 'id']);
        $installationTechnicians = $this->Contracts->InstallationTechnicians
            ->find('all')
            ->where(['dealer' => 1]) // only current dealers
            ->order(['dealer', 'company', 'last_name', 'first_name'])
            ->all()
            ->map(function ($dealer) {
                return [
                    'value' => $dealer->id,
                    'text' => $dealer->name_for_lists,
                    'style' => $dealer->dealer === 1 ? null : 'color: darkgray;',
                ];
            });
        $uninstallationTechnicians = $this->Contracts->UninstallationTechnicians
            ->find('all')
            ->where(['dealer' => 1]) // only current dealers
            ->order(['dealer', 'company', 'last_name', 'first_name'])
            ->all()
            ->map(function ($dealer) {
                return [
                    'value' => $dealer->id,
                    'text' => $dealer->name_for_lists,
                    'style' => $dealer->dealer === 1 ? null : 'color: darkgray;',
                ];
            });
        $commissions = $this->Contracts->Commissions->find('list', ['order' => 'name']);

        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
            $installationAddresses->where([['customer_id' => $customer_id]]);
        }

        $this->set(compact('contract', 'customers'));
        $this->set(compact(
            'contractStates',
            'installationAddresses',
            'serviceTypes',
            'installationTechnicians',
            'uninstallationTechnicians',
            'commissions'
        ));

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
     * @param string|null $id Contract id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $contract = $this->Contracts->get($id, [
            'contain' => [],
        ]);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $contract = $this->Contracts->patchEntity($contract, $this->getRequest()->getData());
            if ($this->Contracts->save($contract)) {
                $this->Flash->success(__('The contract has been saved.'));

                $this->updateNumber($contract->id);

                return $this->redirect(['action' => 'view', $id]);
            }
            $this->Flash->error(__('The contract could not be saved. Please, try again.'));
        }
        $customers = $this->Contracts->Customers->find('list', ['order' => ['company', 'last_name', 'first_name']]);
        $contractStates = $this->Contracts->ContractStates->find('list', ['order' => 'name']);
        $installationAddresses = $this->Contracts->InstallationAddresses->find('list', [
            'order' => ['company', 'last_name', 'first_name'],
        ]);
        $serviceTypes = $this->Contracts->ServiceTypes->find('list', ['order' => 'id']);
        $installationTechnicians = $this->Contracts->InstallationTechnicians
            ->find('all')
            ->order(['dealer', 'company', 'last_name', 'first_name'])
            ->all()
            ->map(function ($dealer) {
                return [
                    'value' => $dealer->id,
                    'text' => $dealer->name_for_lists,
                    'style' => $dealer->dealer === 1 ? null : 'color: darkgray;',
                ];
            });
        $uninstallationTechnicians = $this->Contracts->UninstallationTechnicians
            ->find('all')
            ->order(['dealer', 'company', 'last_name', 'first_name'])
            ->all()
            ->map(function ($dealer) {
                return [
                    'value' => $dealer->id,
                    'text' => $dealer->name_for_lists,
                    'style' => $dealer->dealer === 1 ? null : 'color: darkgray;',
                ];
            });
        $commissions = $this->Contracts->Commissions->find('list', ['order' => 'name']);

        if (isset($customer_id)) {
            $customers->where(['id' => $customer_id]);
            $installationAddresses->where([['customer_id' => $customer_id]]);
        }

        $this->set(compact('contract', 'customers'));
        $this->set(compact(
            'contractStates',
            'installationAddresses',
            'serviceTypes',
            'installationTechnicians',
            'uninstallationTechnicians',
            'commissions'
        ));

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
     * @param string|null $id Contract id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $customer_id = $this->getRequest()->getParam('customer_id');

        $this->getRequest()->allowMethod(['post', 'delete']);
        $contract = $this->Contracts->get($id);
        if ($this->Contracts->delete($contract)) {
            $this->Flash->success(__('The contract has been deleted.'));
        } else {
            $this->Flash->error(__('The contract could not be deleted. Please, try again.'));
        }

        if (isset($customer_id)) {
            return $this->redirect(['controller' => 'Customers', 'action' => 'view', $customer_id]);
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Update contract number with format defined in service type
     *
     * @param string|int|null $id Contract id.
     * @return bool Return true on success false on failure
     */
    private function updateNumber($id = null)
    {
        $contract = $this->Contracts->get($id);
        $service_type = $this->Contracts->ServiceTypes->get($contract->service_type_id);

        $query = $this->Contracts->query();
        $query->update()
            ->set(['number = (' . $service_type->contract_number_format . ')'])
            ->where(['id' => $contract->id]);

        if ($query->execute()->rowCount() == 1) {
            $this->Flash->success(__('The contract number has been updated.'));

            return true;
        }

        $this->Flash->error(__('The contract number could not be updated. Please, try again.'));

        return false;
    }

    /**
     * Set dates for related borrowed equipments
     *
     * @param string|null $id Contract id.
     * @return \Cake\Http\Response|null|void Redirects to view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function setDatesForRelatedBorrowedEquipments($id = null)
    {
        $this->getRequest()->allowMethod(['post']);

        $contract = $this->Contracts->get($id, [
            'contain' => [
                'BorrowedEquipments' => [
                    'EquipmentTypes',
                ],
            ],
        ]);

        $borrowed_equipments = new Collection($contract->borrowed_equipments);

        $borrowed_equipments_to_install = $borrowed_equipments->match(['borrowed_from' => null]);
        $borrowed_equipments_to_uninstall = $borrowed_equipments->match(['borrowed_until' => null]);

        if ($contract->has('installation_date')) {
            if ($borrowed_equipments_to_install->isEmpty()) {
                $this->Flash->warning(__('No related borrowed equipments to install.'));
            } else {
                foreach ($borrowed_equipments_to_install as $borrowed_equipment) {
                    $borrowed_equipment = $this->Contracts->BorrowedEquipments->patchEntity($borrowed_equipment, [
                        'borrowed_from' => $contract->installation_date,
                    ]);

                    if ($this->Contracts->BorrowedEquipments->save($borrowed_equipment)) {
                        $this->Flash->success(
                            __('Installation') . ': '
                            . $borrowed_equipment->equipment_type->name
                            . ' - ' . __('The borrowed equipment has been saved.')
                        );
                    } else {
                        $this->Flash->error(
                            __('Installation') . ': '
                            . $borrowed_equipment->equipment_type->name
                            . ' - ' . __('The borrowed equipment could not be saved. Please, try again.')
                        );
                    }
                }
            }
        }

        if ($contract->has('uninstallation_date')) {
            if ($borrowed_equipments_to_uninstall->isEmpty()) {
                $this->Flash->warning(__('No related borrowed equipments to uninstall.'));
            } else {
                foreach ($borrowed_equipments_to_uninstall as $borrowed_equipment) {
                    $borrowed_equipment = $this->Contracts->BorrowedEquipments->patchEntity($borrowed_equipment, [
                        'borrowed_until' => $contract->uninstallation_date,
                    ]);

                    if ($this->Contracts->BorrowedEquipments->save($borrowed_equipment)) {
                        $this->Flash->success(
                            __('Uninstallation') . ': '
                            . $borrowed_equipment->equipment_type->name
                            . ' - ' . __('The borrowed equipment has been saved.')
                        );
                    } else {
                        $this->Flash->error(
                            __('Uninstallation') . ': '
                            . $borrowed_equipment->equipment_type->name
                            . ' - ' . __('The borrowed equipment could not be saved. Please, try again.')
                        );
                    }
                }
            }
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Terminate related billings
     *
     * @param string|null $id Contract id.
     * @return \Cake\Http\Response|null|void Redirects to view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function terminateRelatedBillings($id = null)
    {
        $this->getRequest()->allowMethod(['post']);

        $contract = $this->Contracts->get($id, [
            'contain' => [
                'Billings' => [
                    'Services',
                ],
                'ContractVersions',
            ],
        ]);

        $billings = new Collection($contract->billings);

        $billings_to_update = $billings->match(['billing_until' => null]);

        if (isset($contract->contract_versions[0]) && $contract->contract_versions[0]->has('valid_until')) {
            if ($billings_to_update->isEmpty()) {
                $this->Flash->warning(__('No related billings to terminate.'));
            } else {
                foreach ($billings_to_update as $billing) {
                    $billing = $this->Contracts->Billings->patchEntity($billing, [
                        'billing_until' => $contract->contract_versions[0]->valid_until,
                    ]);

                    if ($this->Contracts->Billings->save($billing)) {
                        $this->Flash->success(
                            $billing->name . ' - ' . __('The billing has been saved.')
                        );
                    } else {
                        $this->Flash->error(
                            $billing->name . ' - ' . __('The billing could not be saved. Please, try again.')
                        );
                    }
                }
            }
        } else {
            $this->Flash->error(__('Please set a date until which the contract is valid.'));
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Print method
     *
     * @param string|null $id Contract id.
     * @param string|null $type Document type.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function print($id = null, $type = null)
    {
        $customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $customer_id);

        $documentTypes = [
            __('Contracts') => [
                'contract-new' => __('Contract for the provision of services'),
                'contract-new-x' => __('Contract for the provision of services '
                    . '(with termination of the original contract)'),
                'contract-amendment' => __('Amendment to the contract for the provision of services'),
                'contract-termination' => __('Agreement to terminate contract for the provision of services'),
            ],
            __('Handover Protocols') => [
                'handover-protocol-installation' => __('Handover protocol - Installation of internet connection'),
                'handover-protocol-uninstallation' => __('Handover protocol - Internet connection uninstallation'),
            ],
        ];
        $this->set('documentTypes', $documentTypes);

        $contract = $this->Contracts->get($id, [
            'contain' => [
                'Customers' => ['Emails', 'Phones', 'Addresses', 'TaxRates'],
                'ContractStates',
                'ContractVersions',
                'InstallationAddresses',
                'ServiceTypes',
                'InstallationTechnicians',
                'UninstallationTechnicians',
                'Commissions',
                'Billings' => ['Services'],
                'Ips',
                'IpNetworks',
                'BorrowedEquipments.EquipmentTypes' => function (Query $query) {
                    return $query->where(['BorrowedEquipments.borrowed_until IS NULL']);
                },
                'SoldEquipments.EquipmentTypes' => function (Query $query) {
                    return $query->where(['SoldEquipments.date_of_sale IS NULL']);
                },
                'Creators',
                'Modifiers',
            ],
        ]);

        $contractVersions = collection($contract->contract_versions)->map(function ($contract_version) {
            return [
                'value' => $contract_version->id,
                'text' => $contract_version->valid_from
                    . ' - '
                    . ($contract_version->valid_until ? $contract_version->valid_until : __('indefinitely')),
            ];
        })->toArray();

        $query = $this->getRequest()->getQuery();
        if (isset($query['document_type'])) {
            $type = $query['document_type'];
        }
        if (isset($query['contract_version_id'])) {
            $contract_version = collection($contract->contract_versions)->firstMatch([
                'id' => $query['contract_version_id'],
            ]);
        }
        if (isset($query['contract_version_to_be_replaced_id'])) {
            $contract_version_to_be_replaced = collection($contract->contract_versions)->firstMatch([
                'id' => $query['contract_version_to_be_replaced_id'],
            ]);
        }

        if ($this->getRequest()->getParam('_ext') === 'pdf') {
            // check if borrowed equipment is added where it should be
            if (
                !$query['own_equipment']
                && $contract->service_type->normally_with_borrowed_equipment
                && empty($contract->borrowed_equipments)
            ) {
                $this->Flash->error(__(
                    'A borrowed equipment is not assigned, although it should normally be for this type of service.'
                    . ' Please confirm that the customer has their own equipment or add it.'
                ));

                return $this->redirect(['action' => 'print', $id, '?' => $query]);
            }

            if (empty($contract_version)) {
                $this->Flash->error(__('Invalid contract version requested.'));

                return $this->redirect(['action' => 'print', $id, '?' => $query]);
            } else {
                if (!empty($contract_version_to_be_replaced)) {
                    if ($contract_version->id == $contract_version_to_be_replaced->id) {
                        $this->Flash->error(__('Invalid contract version to be replaced requested.'));

                        return $this->redirect(['action' => 'print', $id, '?' => $query]);
                    }

                    $contract_version['old'] = $contract_version_to_be_replaced;
                }
                $this->set('contract_version', $contract_version);
            }

            switch ($type) {
                case 'contract-termination':
                    if (!$contract_version->has('valid_until')) {
                        $this->Flash->error(__('Please set the date until which the contract version is valid.'));

                        return $this->redirect([
                            'controller' => 'ContractVersions',
                            'action' => 'edit',
                            $contract_version->id,
                        ]);
                    }

                    if (!$contract_version->has('conclusion_date')) {
                        $this->Flash->error(__('Please set the date of conclusion of the contract version.'));

                        return $this->redirect([
                            'controller' => 'ContractVersions',
                            'action' => 'edit',
                            $contract_version->id,
                        ]);
                    }

                    if (empty($query['number_of_the_contract_to_be_terminated'])) {
                        $this->Flash->error(__('Please enter the number of the contract to be terminated.'));

                        return $this->redirect(['action' => 'print', $id, '?' => $query]);
                    } else {
                        $contract_version['number_of_the_contract_to_be_terminated']
                            = $query['number_of_the_contract_to_be_terminated'];
                    }

                    break;

                case 'contract-amendment':
                    if (empty($query['effective_date_of_the_amendment'])) {
                        $this->Flash->error(__('Please enter the effective date of the amendment.'));

                        return $this->redirect(['action' => 'print', $id, '?' => $query]);
                    } else {
                        $contract_version->valid_from = new FrozenDate($query['effective_date_of_the_amendment']);
                    }

                    if (!$contract_version->has('conclusion_date')) {
                        $this->Flash->error(__('Please set the date of conclusion of the contract version.'));

                        return $this->redirect([
                            'controller' => 'ContractVersions',
                            'action' => 'edit',
                            $contract_version->id,
                        ]);
                    }

                    break;

                case 'contract-new-x':
                    if (empty($contract_version['old'])) {
                        $this->Flash->error(__('Please select the contract version to be replaced.'));

                        return $this->redirect(['action' => 'print', $id, '?' => $query]);
                    }

                    if (!$contract_version['old']->has('conclusion_date')) {
                        $this->Flash->error(__('Please set the date of conclusion of the original contract version.'));

                        return $this->redirect([
                            'controller' => 'ContractVersions',
                            'action' => 'edit',
                            $contract_version['old']->id,
                        ]);
                    }

                    if (empty($query['number_of_the_contract_to_be_terminated'])) {
                        $this->Flash->error(__('Please enter the number of the contract to be terminated.'));

                        return $this->redirect(['action' => 'print', $id, '?' => $query]);
                    } else {
                        $contract_version['number_of_the_contract_to_be_terminated']
                            = $query['number_of_the_contract_to_be_terminated'];
                    }

                    break;

                case 'contract-new':
                    /*
                    if (!$contract_version->has('valid_from')) {
                        $this->Flash->error(__('Please set the date from which the contract version is valid.'));

                        return $this->redirect(['action' => 'view', $id]);
                    }
                    */

                    break;

                case 'handover-protocol-uninstallation':
                    if (!$contract_version->has('valid_until')) {
                        $this->Flash->error(__('Please set the date until which the contract version is valid.'));

                        return $this->redirect([
                            'controller' => 'ContractVersions',
                            'action' => 'edit',
                            $contract_version->id,
                        ]);
                    }

                    if (empty($query['number_of_the_contract_to_be_terminated'])) {
                        $this->Flash->error(__('Please enter the number of the contract to be terminated.'));

                        return $this->redirect(['action' => 'print', $id, '?' => $query]);
                    } else {
                        $contract_version['number_of_the_contract_to_be_terminated']
                            = $query['number_of_the_contract_to_be_terminated'];
                    }

                    // no break - checks will continue
                case 'handover-protocol-installation':
                    /*
                    if (!$contract_version->has('valid_from')) {
                        $this->Flash->error(__('Please set the date from which the contract version is valid.'));

                        return $this->redirect(['action' => 'view', $id]);
                    }
                    */

                    try {
                        //Try to load lastly added RADIUS account
                        /** @var \Radius\Model\Entity\Account $radius_account */
                        $radius_account = $this->fetchTable('Radius.Accounts')
                            ->find()
                            ->where(['contract_id' => $contract->id, 'active' => true])
                            ->order(['id' => 'DESC'])
                            ->limit(1)
                            ->first();
                        $radius_connected = true;
                    } catch (MissingConnectionException $connectionError) {
                        //Couldn't connect
                        $radius_account = null;
                        $radius_connected = false;
                    }

                    $technical_details = new stdClass();

                    if (!empty($query['access_point'])) {
                        $technical_details->access_point = $query['access_point'];
                    } elseif ($contract->has('access_point')) {
                        $technical_details->access_point = $contract->access_point['name'];
                    }

                    if (!empty($query['radius_username'])) {
                        $technical_details->radius_username = $query['radius_username'];
                    } elseif ($radius_connected && isset($radius_account->username)) {
                        $technical_details->radius_username = $radius_account->username;
                    }

                    if (!empty($query['radius_password'])) {
                        $technical_details->radius_password = $query['radius_password'];
                    } elseif ($radius_connected && isset($radius_account->password)) {
                        $technical_details->radius_password = $radius_account->password;
                    }

                    $this->set('technical_details', $technical_details);
                    break;

                default:
                    $this->Flash->error(__('Invalid type of document requested.'));

                    return $this->redirect(['action' => 'print', $id, '?' => $query]);
            }

            $billings_collection = new Collection($contract->billings);

            $active_billings_collection = $billings_collection->reject(
                function ($billing, $key) use ($contract_version) {
                    return ($billing->has('billing_from') && $billing->billing_from > $contract_version->valid_from)
                        || ($billing->has('billing_until') && $billing->billing_until < $contract_version->valid_from);
                }
            );

            $contract->individual_billings = $active_billings_collection->filter(function ($billing, $key) {
                return $billing->has('price');
            })->toArray();

            $contract->standard_billings = $active_billings_collection->filter(function ($billing, $key) {
                return !$billing->has('price');
            })->toArray();
        }
        $this->set(compact(
            'contract',
            'contractVersions',
            'type',
            'query'
        ));
    }
}
