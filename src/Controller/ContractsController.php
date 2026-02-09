<?php
declare(strict_types=1);

namespace App\Controller;

use App\ApiClient;
use App\Model\Enum\ContractPrintType;
use App\Model\Enum\CustomerDealer;
use App\Service\ContractPrint\ContractPrintData;
use App\Service\ContractPrint\ContractPrintDataEnricher;
use App\Service\ContractPrint\ContractPrintPdfOutput;
use App\Service\ContractPrint\ContractPrintValidator;
use App\View\PdfView;
use Cake\Collection\Collection;
use Cake\Form\Form;
use Cake\I18n\Number;
use Cake\ORM\Query\SelectQuery;
use Cake\Validation\Validation;
use Override;
use ValueError;

/**
 * Contracts Controller
 *
 * @property \App\Model\Table\ContractsTable $Contracts
 */
class ContractsController extends AppController
{
    /**
     * Returns supported output types
     */
    #[Override]
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

        // filter by contract state
        $contract_state_id = $this->getRequest()->getQuery('contract_state_id');
        if (Validation::uuid($contract_state_id)) {
            $conditions[] = [
                'Contracts.contract_state_id' => $contract_state_id,
            ];
        }

        // filter by service type
        $service_type_id = $this->getRequest()->getQuery('service_type_id');
        if (Validation::uuid($service_type_id)) {
            $conditions[] = [
                'Contracts.service_type_id' => $service_type_id,
            ];
        }

        // pagination settings
        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];

        // paginate results
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
            conditions: $conditions,
        ));

        $contractStates = $this->Contracts->ContractStates->find('list', order: [
            'name',
        ]);
        $serviceTypes = $this->Contracts->ServiceTypes->find('list', order: [
            'name',
        ]);

        $this->set(compact('contracts', 'contractStates', 'serviceTypes'));
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
        $contractStates = $this->Contracts->ContractStates
            ->find('list', order: [
                'name',
            ])
            ->where([
                'ContractStates.usable_for_new_contract' => true,
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
                'dealer' => CustomerDealer::Current,
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
                    'style' => $dealer->dealer == CustomerDealer::Current ? null : 'color: darkgray;',
                ];
            });
        $uninstallationTechnicians = $this->Contracts->UninstallationTechnicians
            ->find()
            ->where([
                'dealer' => CustomerDealer::Current,
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
                    'style' => $dealer->dealer == CustomerDealer::Current ? null : 'color: darkgray;',
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
            'commissions',
        ));

        // load access points from NMS if possible
        $accessPoints = ApiClient::getAccessPoints();
        $ipAddressRanges = ApiClient::searchIpAddressRanges([]);
        if ($accessPoints && $ipAddressRanges) {
            $this->set(
                'accessPoints',
                $accessPoints = $accessPoints
                    ->sortBy('name', SORT_ASC, SORT_NATURAL)
                    ->map(
                        function ($accessPoint) use ($ipAddressRanges) {
                            $text = $accessPoint['name'];

                            $ranges = $ipAddressRanges
                                ->match(['access_point_id' => $accessPoint['id']])
                                ->sortBy('name', SORT_ASC, SORT_NATURAL);

                            if (!$ranges->isEmpty()) {
                                $rangeNames = $ranges->extract('name');
                                $text .= '     ' . '[' . implode(', ', $rangeNames->toArray()) . ']';
                            }

                            return [
                                'value' => $accessPoint['id'],
                                'text' => $text,
                            ];
                        },
                    ),
            );
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
                    'style' => $dealer->dealer == CustomerDealer::Current ? null : 'color: darkgray;',
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
                    'style' => $dealer->dealer == CustomerDealer::Current ? null : 'color: darkgray;',
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
            'commissions',
        ));

        // load access points from NMS if possible
        $accessPoints = ApiClient::getAccessPoints();
        $ipAddressRanges = ApiClient::searchIpAddressRanges([]);
        if ($accessPoints && $ipAddressRanges) {
            $this->set(
                'accessPoints',
                $accessPoints = $accessPoints
                    ->sortBy('name', SORT_ASC, SORT_NATURAL)
                    ->map(
                        function ($accessPoint) use ($ipAddressRanges) {
                            $text = $accessPoint['name'];

                            $ranges = $ipAddressRanges
                                ->match(['access_point_id' => $accessPoint['id']])
                                ->sortBy('name', SORT_ASC, SORT_NATURAL);

                            if (!$ranges->isEmpty()) {
                                $rangeNames = $ranges->extract('name');
                                $text .= '     ' . '[' . implode(', ', $rangeNames->toArray()) . ']';
                            }

                            return [
                                'value' => $accessPoint['id'],
                                'text' => $text,
                            ];
                        },
                    ),
            );
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

            if (
                $this->Contracts->save($contract, [
                    'skipContractStateValidation' => true,
                ])
            ) {
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
                        . ' (ID: ' . $contract->id . ')',
                    );
                }
            }
        }

        $this->Flash->success(
            __('The contract numbers have been updated.') . ' (' . Number::format($count) . ')',
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

            if (
                $this->Contracts->save($contract, [
                    'skipContractStateValidation' => true,
                ])
            ) {
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
                        . ' (ID: ' . $contract->id . ')',
                    );
                }
            }
        }

        $this->Flash->success(
            __('The subscriber verification codes have been updated.') . ' (' . Number::format($count) . ')',
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
                            . ' - ' . __('The borrowed equipment has been saved.'),
                        );
                    } else {
                        $this->Flash->error(
                            __('Installation') . ': '
                            . $borrowed_equipment->equipment_type->name
                            . ' - ' . __('The borrowed equipment could not be saved. Please, try again.'),
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
                            . ' - ' . __('The borrowed equipment has been saved.'),
                        );
                    } else {
                        $this->Flash->error(
                            __('Uninstallation') . ': '
                            . $borrowed_equipment->equipment_type->name
                            . ' - ' . __('The borrowed equipment could not be saved. Please, try again.'),
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
                            $billing->name . ' - ' . __('The billing has been saved.'),
                        );
                    } else {
                        $this->Flash->error(
                            $billing->name . ' - ' . __('The billing could not be saved. Please, try again.'),
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
        // prepare supported document types for selection in the print view
        $documentTypes = [
            __('Contracts') => [
                ContractPrintType::ContractNew->value => ContractPrintType::ContractNew->label(),
                ContractPrintType::ContractNewX->value => ContractPrintType::ContractNewX->label(),
                ContractPrintType::ContractAmendment->value => ContractPrintType::ContractAmendment->label(),
                ContractPrintType::ContractTermination->value => ContractPrintType::ContractTermination->label(),
            ],
            __('Handover Protocols') => [
                ContractPrintType::HandoverInstallation->value => ContractPrintType::HandoverInstallation->label(),
                ContractPrintType::HandoverUninstallation->value => ContractPrintType::HandoverUninstallation->label(),
            ],
        ];
        $this->set('documentTypes', $documentTypes);

        // initialize an empty form to be used for PDF generation (validation errors will be added to this form)
        $printForm = new Form();

        // load the contract with all related data needed for rendering the print views and generating the PDF documents
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
                'AccountingProfiles',
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

        // prepare contract versions for selection in the print view
        $contractVersions = (new Collection($contract->contract_versions))->map(function ($contract_version) {
            return [
                'value' => $contract_version->id,
                'text' => $contract_version->valid_from
                    . ' - '
                    . ($contract_version->valid_until ?: __('indefinitely')),
            ];
        })->toArray();

        // load query parameters from the request
        $query = $this->getRequest()->getQuery();

        // keep only relevant query parameters for PDF generation in the query string
        unset($query['refresh']);
        unset($query['submit_action']);

        // load the print type from the query string or use the one from the URL parameter
        try {
            $printType = ContractPrintType::from($query['document_type'] ?? $type ?? '');
        } catch (ValueError) {
            // tolerate invalid or missing document type for UI rendering
            $printType = null;
        }

        // PDF request: validate input, enrich data and render PDF output
        if (
            $this->getRequest()->getParam('_ext') === 'pdf'
            || $this->getRequest()->getQuery('submit_action') === 'pdf'
        ) {
            // if the print type is invalid or missing, show an error and redirect back to the print view
            if ($printType === null) {
                $this->Flash->error(__('Invalid type of document requested.'));

                return $this->redirect(['action' => 'print', $id, '?' => $query]);
            }

            // load the contract version to be printed from the query string
            $contractVersion = null;
            if (!empty($query['contract_version_id'])) {
                $contractVersion = (new Collection($contract->contract_versions))->firstMatch([
                    'id' => $query['contract_version_id'],
                ]);

                if ($contractVersion === null) {
                    $this->Flash->error(__('Invalid contract version requested.'));

                    return $this->redirect(['action' => 'print', $id, '?' => $query]);
                }
            }

            // load the contract version to be replaced from the query string
            $contractVersionToBeReplaced = null;
            if (!empty($query['contract_version_to_be_replaced_id'])) {
                $contractVersionToBeReplaced = (new Collection($contract->contract_versions))->firstMatch([
                    'id' => $query['contract_version_to_be_replaced_id'],
                ]);

                if ($contractVersionToBeReplaced === null) {
                    $this->Flash->error(__('Invalid contract version to be replaced requested.'));

                    return $this->redirect(['action' => 'print', $id, '?' => $query]);
                }
            }

            // prepare data for validation
            $data = new ContractPrintData(
                type: $printType,
                contract: $contract,
                contractVersion: $contractVersion,
                contractVersionToBeReplaced: $contractVersionToBeReplaced,
            );

            // validate the data for the requested document type
            $errors = (new ContractPrintValidator())->validate($data, $query);

            // if there are validation errors, process them
            if (!empty($errors)) {
                // flash error messages for the user
                foreach ($errors['Flash'] ?? [] as $error) {
                    $this->Flash->error($error);
                }
                unset($errors['Flash']);

                if ($this->getRequest()->getParam('_ext') !== 'pdf') {
                    // Set validation errors on the form to be displayed in the print view
                    $printForm->setErrors($errors);
                } else {
                    // if the request is already a PDF request, redirect to the same URL without the PDF extension
                    return $this->redirect(['action' => 'print', $id, '?' => $query]);
                }
            } else {
                // if the request is not already a PDF request, redirect to the same URL with the PDF extension to trigger PDF rendering
                if ($this->getRequest()->getParam('_ext') !== 'pdf') {
                    return $this->redirect(['action' => 'print', $id, '_ext' => 'pdf', '?' => $query]);
                }

                // enrich the data for the requested document type (e.g. add technical details for handover protocols)
                (new ContractPrintDataEnricher())->enrich($data, $query);

                // render the PDF document based on the enriched data
                return (new ContractPrintPdfOutput())->render($data);
            }
        }

        // render the print view for HTML requests
        $this->set(compact(
            'printForm',
            'printType',
            'contract',
            'contractVersions',
        ));
    }
}
