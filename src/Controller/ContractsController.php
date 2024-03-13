<?php
declare(strict_types=1);

namespace App\Controller;

use App\ApiClient;
use App\View\PdfView;
use Cake\Collection\Collection;
use Cake\Database\Exception\MissingConnectionException;
use Cake\I18n\Date;
use Cake\I18n\Number;
use Cake\ORM\Query\SelectQuery;
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
     * Returns supported output types
     */
    public function viewClasses(): array
    {
        return [
            PdfView::class,
        ];
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        // filter
        $conditions = [];
        if (isset($this->customer_id)) {
            $conditions = ['Contracts.customer_id' => $this->customer_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Contracts.number ILIKE' => '%' . trim($search) . '%',
                    'Contracts.subscriber_verification_code' => trim($search),
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $contracts = $this->paginate($this->Contracts->find(
            'all',
            contain: [
                'Commissions',
                'ContractStates',
                'Customers',
                'InstallationAddresses',
                'InstallationTechnicians',
                'ServiceTypes',
                'UninstallationTechnicians',
            ],
            conditions: $conditions
        ));

        $this->set(compact('contracts'));
    }

    /**
     * View method
     *
     * @param string|null $id Contract id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $contract = $this->Contracts->get($id, contain: [
            'Billings' => [
                'Contracts' => [
                    'ContractStates',
                ],
                'Services',
            ],
            'BorrowedEquipments' => [
                'EquipmentTypes',
            ],
            'Commissions',
            'ContractStates',
            'ContractVersions',
            'Customers' => [
                'CustomerLabels' => [
                    'Labels',
                    'sort' => [
                        'Labels.name',
                    ],
                    'conditions' => [
                        'OR' => [
                            'CustomerLabels.contract_id' => $id,
                            'CustomerLabels.contract_id IS' => null,
                        ],
                    ],
                ],
                'Tasks' => [
                    'Contracts',
                    'Dealers',
                    'TaskStates',
                    'TaskTypes',
                    'conditions' => [
                        'OR' => [
                            'Tasks.contract_id !=' => $id,
                            'Tasks.contract_id IS' => null,
                        ],
                    ],
                ],
            ],
            'InstallationAddresses',
            'InstallationTechnicians',
            'IpAddresses',
            'IpNetworks',
            'RemovedIpAddresses',
            'RemovedIpNetworks',
            'ServiceTypes',
            'Tasks' => [
                'TaskTypes',
                'TaskStates',
                'Dealers',
            ],
            'SoldEquipments' => [
                'EquipmentTypes',
            ],
            'UninstallationTechnicians',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('contract'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $contract = $this->Contracts->newEmptyEntity();

        if (isset($this->customer_id)) {
            $contract->customer_id = $this->customer_id;
        }

        if ($this->getRequest()->is('post')) {
            $contract = $this->Contracts->patchEntity($contract, $this->getRequest()->getData());
            if ($this->Contracts->save($contract)) {
                $this->Flash->success(__('The contract has been saved.'));

                if (empty($contract->number)) {
                    $this->updateNumber($contract->id);
                }
                if (empty($contract->subscriber_verification_code)) {
                    $this->updateSubscriberVerificationCode($contract->id);
                }

                return $this->afterAddRedirect(['action' => 'view', $contract->id]);
            }
            $this->Flash->error(__('The contract could not be saved. Please, try again.'));
        }
        $customers = $this->Contracts->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        $contractStates = $this->Contracts->ContractStates->find('list', order: [
            'name',
        ]);
        $installationAddresses = $this->Contracts->InstallationAddresses->find(
            'list',
            order: [
                'company',
                'last_name',
                'first_name',
            ],
        );
        $serviceTypes = $this->Contracts->ServiceTypes->find('list', order: [
            'name',
        ]);
        $installationTechnicians = $this->Contracts->InstallationTechnicians
            ->find()
            ->where([
                'dealer' => 1, // only current dealers
            ])
            ->orderBy([
                'dealer',
                'company',
                'last_name',
                'first_name',
            ])
            ->all()
            ->map(function ($dealer) {
                return [
                    'value' => $dealer->id,
                    'text' => $dealer->name_for_lists,
                    'style' => $dealer->dealer === 1 ? null : 'color: darkgray;',
                ];
            });
        $uninstallationTechnicians = $this->Contracts->UninstallationTechnicians
            ->find()
            ->where([
                'dealer' => 1, // only current dealers
            ])
            ->orderBy([
                'dealer',
                'company',
                'last_name',
                'first_name',
            ])
            ->all()
            ->map(function ($dealer) {
                return [
                    'value' => $dealer->id,
                    'text' => $dealer->name_for_lists,
                    'style' => $dealer->dealer === 1 ? null : 'color: darkgray;',
                ];
            });
        $commissions = $this->Contracts->Commissions->find('list', order: [
            'name',
        ]);

        if (isset($this->customer_id)) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $installationAddresses->where([['InstallationAddresses.customer_id' => $this->customer_id]]);
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
    public function edit(?string $id = null)
    {
        $contract = $this->Contracts->get($id);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $contract = $this->Contracts->patchEntity($contract, $this->getRequest()->getData());
            if ($this->Contracts->save($contract)) {
                $this->Flash->success(__('The contract has been saved.'));

                if (empty($contract->number)) {
                    $this->updateNumber($contract->id);
                }
                if (empty($contract->subscriber_verification_code)) {
                    $this->updateSubscriberVerificationCode($contract->id);
                }

                return $this->afterEditRedirect(['action' => 'view', $contract->id]);
            }
            $this->Flash->error(__('The contract could not be saved. Please, try again.'));
        }
        $customers = $this->Contracts->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        $contractStates = $this->Contracts->ContractStates->find('list', order: [
            'name',
        ]);
        $installationAddresses = $this->Contracts->InstallationAddresses->find(
            'list',
            order: [
                'company',
                'last_name',
                'first_name',
            ],
        );
        $serviceTypes = $this->Contracts->ServiceTypes->find(
            'list',
            order: [
                'name',
            ],
        );
        $installationTechnicians = $this->Contracts->InstallationTechnicians
            ->find()
            ->orderBy([
                'dealer',
                'company',
                'last_name',
                'first_name',
            ])
            ->all()
            ->map(function ($dealer) {
                return [
                    'value' => $dealer->id,
                    'text' => $dealer->name_for_lists,
                    'style' => $dealer->dealer === 1 ? null : 'color: darkgray;',
                ];
            });
        $uninstallationTechnicians = $this->Contracts->UninstallationTechnicians
            ->find()
            ->orderBy([
                'dealer',
                'company',
                'last_name',
                'first_name',
            ])
            ->all()
            ->map(function ($dealer) {
                return [
                    'value' => $dealer->id,
                    'text' => $dealer->name_for_lists,
                    'style' => $dealer->dealer === 1 ? null : 'color: darkgray;',
                ];
            });
        $commissions = $this->Contracts->Commissions->find('list', order: [
            'name',
        ]);

        if (isset($this->customer_id)) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $installationAddresses->where([['InstallationAddresses.customer_id' => $this->customer_id]]);
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
    public function delete(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $contract = $this->Contracts->get($id);
        if ($this->Contracts->delete($contract)) {
            $this->Flash->success(__('The contract has been deleted.'));
        } else {
            $this->flashValidationErrors($contract->getErrors());
            $this->Flash->error(__('The contract could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }

    /**
     * Update contract number according to the format defined in the service type
     *
     * @param string|int|null $id Contract id.
     * @param bool $flash Enable flash messages
     * @return bool Return true on success false on failure
     */
    private function updateNumber(string|int|null $id = null, bool $flash = true)
    {
        $contract = $this->Contracts->get($id);
        $service_type = $this->Contracts->ServiceTypes->get($contract->service_type_id);

        // skip service types without defined number format
        if (empty($service_type->contract_number_format)) {
            return true;
        }

        // generate number
        $result = $this->Contracts->selectQuery()
            ->select([
                'number' => '(' . $service_type->contract_number_format . ')',
            ])
            ->contain([
                'Customers',
            ])
            ->where([
                'Contracts.id' => $contract->id,
            ])
            ->all();

        if ($result->count() == 1) {
            // assign a number for the contract
            $contract->number = $result->first()->number;

            if ($this->Contracts->save($contract)) {
                if ($flash) {
                    $this->Flash->success(__('The contract number has been updated.'));
                }

                return true;
            }
        }

        if ($flash) {
            $this->Flash->error(__('The contract number could not be updated. Please, try again.'));
        }

        return false;
    }

    /**
     * Update all contract numbers according to the format defined in the service type
     *
     * @param bool $force Update even where already set
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Http\Exception\MethodNotAllowedException When badly called.
     */
    public function updateAllNumbers(bool $force = false)
    {
        $this->getRequest()->allowMethod(['post']);

        $contracts = $this->Contracts->find()->all();

        $count = 0;

        foreach ($contracts as $contract) {
            if ($force || empty($contract->number)) {
                if ($this->updateNumber($contract->id, false)) {
                    $count++;
                } else {
                    $this->Flash->error(
                        __('The contract numbers could not be updated. Please, try again.')
                        . ' (ID: ' . $contract->id . ')'
                    );
                }
            }
        }

        $this->Flash->success(
            __('The contract numbers have been updated.') . ' (' . Number::format($count) . ')'
        );

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Update the subscriber verification code for the contract according to the format defined in the service type
     *
     * @param string|int|null $id Contract id.
     * @param bool $flash Enable flash messages
     * @return bool Return true on success false on failure
     */
    private function updateSubscriberVerificationCode(string|int|null $id = null, bool $flash = true)
    {
        $contract = $this->Contracts->get($id);
        $service_type = $this->Contracts->ServiceTypes->get($contract->service_type_id);

        // skip service types without defined subscriber verification code format
        if (empty($service_type->subscriber_verification_code_format)) {
            return true;
        }

        // generate subscriber verification code
        $result = $this->Contracts->selectQuery()
            ->select([
                'subscriber_verification_code' => '(' . $service_type->subscriber_verification_code_format . ')',
            ])
            ->where(['id' => $contract->id])
            ->all();

        if ($result->count() == 1) {
            // assign subscriber verification code for the contract
            $contract->subscriber_verification_code = $result->first()->subscriber_verification_code;

            if ($this->Contracts->save($contract)) {
                if ($flash) {
                    $this->Flash->success(__('The subscriber verification code has been updated.'));
                }

                return true;
            }
        }

        if ($flash) {
            $this->Flash->error(__('The subscriber verification code could not be updated. Please, try again.'));
        }

        return false;
    }

    /**
     * Update all subscriber verification codes for the contracts according to the format defined in the service type
     *
     * @param bool $force Update even where already set
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Http\Exception\MethodNotAllowedException When badly called.
     */
    public function updateAllSubscriberVerificationCodes(bool $force = false)
    {
        $this->getRequest()->allowMethod(['post']);

        $contracts = $this->Contracts->find()->all();

        $count = 0;

        foreach ($contracts as $contract) {
            if ($force || empty($contract->subscriber_verification_code)) {
                if ($this->updateSubscriberVerificationCode($contract->id, false)) {
                    $count++;
                } else {
                    $this->Flash->error(
                        __('The subscriber verification codes could not be updated. Please, try again.')
                        . ' (ID: ' . $contract->id . ')'
                    );
                }
            }
        }

        $this->Flash->success(
            __('The subscriber verification codes have been updated.') . ' (' . Number::format($count) . ')'
        );

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Set dates for related borrowed equipments
     *
     * @param string|null $id Contract id.
     * @return \Cake\Http\Response|null|void Redirects to view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function setDatesForRelatedBorrowedEquipments(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post']);

        $contract = $this->Contracts->get($id, contain: [
            'BorrowedEquipments' => [
                'EquipmentTypes',
            ],
        ]);

        $borrowed_equipments = new Collection($contract->borrowed_equipments);

        $borrowed_equipments_to_install = $borrowed_equipments->match(['borrowed_from' => null]);
        $borrowed_equipments_to_uninstall = $borrowed_equipments->match(['borrowed_until' => null]);

        if ($contract->__isset('installation_date')) {
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

        if ($contract->__isset('uninstallation_date')) {
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
    public function terminateRelatedBillings(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post']);

        $contract = $this->Contracts->get($id, contain: [
            'Billings' => [
                'Services',
            ],
        ]);

        $billings = new Collection($contract->billings);

        $billings_to_update = $billings->match(['billing_until' => null]);

        if ($contract->__isset('termination_date')) {
            if ($billings_to_update->isEmpty()) {
                $this->Flash->warning(__('No related billings to terminate.'));
            } else {
                foreach ($billings_to_update as $billing) {
                    $billing = $this->Contracts->Billings->patchEntity($billing, [
                        'billing_until' => $contract->termination_date,
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
    public function print(?string $id = null, ?string $type = null)
    {
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

        $contract = $this->Contracts->get($id, contain: [
            'Billings' => ['Services'],
            'BorrowedEquipments.EquipmentTypes' => function (SelectQuery $query) {
                return $query->where([
                    'BorrowedEquipments.borrowed_until IS NULL',
                ]);
            },
            'Commissions',
            'ContractStates',
            'ContractVersions',
            'Customers' => [
                'Addresses',
                'Emails',
                'Phones',
                'TaxRates',
            ],
            'InstallationAddresses',
            'InstallationTechnicians',
            'IpAddresses',
            'IpNetworks',
            'ServiceTypes',
            'SoldEquipments.EquipmentTypes' => function (SelectQuery $query) {
                return $query->where([
                    'SoldEquipments.date_of_sale IS NULL',
                ]);
            },
            'UninstallationTechnicians',
            'Creators',
            'Modifiers',
        ]);

        $contractVersions = (new Collection($contract->contract_versions))->map(function ($contract_version) {
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
            $contract_version = (new Collection($contract->contract_versions))->firstMatch([
                'id' => $query['contract_version_id'],
            ]);
        }
        if (isset($query['contract_version_to_be_replaced_id'])) {
            $contract_version_to_be_replaced = (new Collection($contract->contract_versions))->firstMatch([
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
                    if (!$contract_version->__isset('valid_until')) {
                        $this->Flash->error(__('Please set the date until which the contract version is valid.'));

                        return $this->redirect([
                            'controller' => 'ContractVersions',
                            'action' => 'edit',
                            $contract_version->id,
                        ]);
                    }

                    if (!$contract_version->__isset('conclusion_date')) {
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
                        $contract_version->valid_from = new Date($query['effective_date_of_the_amendment']);
                    }

                    if (!$contract_version->__isset('conclusion_date')) {
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

                    if (!$contract_version['old']->__isset('conclusion_date')) {
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
                    if (!$contract_version->__isset('valid_from')) {
                        $this->Flash->error(__('Please set the date from which the contract version is valid.'));

                        return $this->redirect(['action' => 'view', $id]);
                    }
                    */

                    break;

                case 'handover-protocol-uninstallation':
                    if (!$contract_version->__isset('valid_until')) {
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
                    if (!$contract_version->__isset('valid_from')) {
                        $this->Flash->error(__('Please set the date from which the contract version is valid.'));

                        return $this->redirect(['action' => 'view', $id]);
                    }
                    */

                    try {
                        //Try to load lastly added RADIUS account
                        /** @var \Radius\Model\Entity\Account $radius_account */
                        $radius_account = $this->fetchTable('Radius.Accounts')
                            ->find()
                            ->where([
                                'contract_id' => $contract->id,
                                'active' => true,
                            ])
                            ->orderBy([
                                'id' => 'DESC',
                            ])
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
                    } elseif ($contract->__isset('access_point')) {
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
                    return (
                            $billing->__isset('billing_from')
                            && $billing->billing_from > $contract_version->valid_from
                        ) || (
                            $billing->__isset('billing_until')
                            && $billing->billing_until < $contract_version->valid_from
                        );
                }
            );

            $contract['individual_billings'] = $active_billings_collection->filter(function ($billing, $key) {
                return $billing->__isset('price');
            })->toArray();

            $contract['standard_billings'] = $active_billings_collection->filter(function ($billing, $key) {
                return !$billing->__isset('price');
            })->toArray();

            $future_billings_collection = $billings_collection->reject(
                function ($billing, $key) use ($contract_version) {
                    return (
                            $billing->__isset('billing_from')
                            && $billing->billing_from <= $contract_version->valid_from
                        ) || (
                            $billing->__isset('billing_until')
                            && $billing->billing_until < $contract_version->valid_from
                        );
                }
            );

            $contract['future_individual_billings'] = $future_billings_collection->filter(function ($billing, $key) {
                return $billing->__isset('price');
            })->toArray();

            $contract['future_standard_billings'] = $future_billings_collection->filter(function ($billing, $key) {
                return !$billing->__isset('price');
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
