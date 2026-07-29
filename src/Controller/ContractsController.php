<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\CommonViewVarListsTrait;
use App\Model\Enum\ContractPrintType;
use App\Model\Enum\CustomerDealer;
use App\Service\ContractPrint\ContractPrintData;
use App\Service\ContractPrint\ContractPrintDataEnricher;
use App\Service\ContractPrint\ContractPrintPdfOutput;
use App\Service\ContractPrint\ContractPrintValidator;
use App\View\PdfView;
use Cake\Collection\Collection;
use Cake\Form\Form;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\I18n\Number;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Hash;
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
    use CommonViewVarListsTrait;

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
     * @return void Renders view
     */
    public function index(): void
    {
        // filter
        $conditions = [];
        if ($this->customer_id !== null) {
            $conditions = ['Contracts.customer_id' => $this->customer_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Contracts.number ILIKE' => '%' . trim((string)$search) . '%',
                    'Contracts.subscriber_verification_code' => trim((string)$search),
                ],
            ];
        }

        // filter by contract state
        $contract_state_id = $this->getRequest()->getQuery('contract_state_id');
        if (is_string($contract_state_id) && Validation::uuid($contract_state_id)) {
            $conditions[] = [
                'Contracts.contract_state_id' => $contract_state_id,
            ];
        }

        // filter by service type
        $service_type_id = $this->getRequest()->getQuery('service_type_id');
        if (is_string($service_type_id) && Validation::uuid($service_type_id)) {
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
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        // determine whether to show historical records based on the user settings and query parameter
        $show_historical_records = $this->request->getQuery('show_historical_records');
        $show_historical_records ??= Hash::get($this->user_settings, 'customers.show_historical_records', false);
        $show_historical_records = (bool)$show_historical_records;
        // pass the value to the view
        $this->set('show_historical_records', $show_historical_records);

        $contain = [
            'Billings' => [
                'Contracts' => [
                    'ContractStates',
                ],
                'Services',
                'finder' => $show_historical_records ? 'all' : 'activeOrFuture',
            ],
            'BorrowedEquipments' => [
                'EquipmentTypes',
                'conditions' => $show_historical_records ?
                    [] : [
                        'OR' => [
                            'BorrowedEquipments.borrowed_until >=' => Date::now()->firstOfMonth(),
                            'BorrowedEquipments.borrowed_until IS' => null,
                        ],
                    ],
            ],
            'Commissions',
            'ContractStates',
            'ContractVersions' => [
                'conditions' => $show_historical_records ?
                    [] : [
                        'OR' => [
                            'ContractVersions.valid_until >=' => Date::now()->firstOfMonth(),
                            'ContractVersions.valid_until IS' => null,
                        ],
                    ],
            ],
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
        ];

        if ($show_historical_records) {
            $contain = array_merge($contain, [
                'RemovedIpAddresses',
                'RemovedIpNetworks',
            ]);
        }

        $contract = $this->Contracts->get($id, contain: $contain);

        $this->set(compact('contract'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $contract = $this->Contracts->newEmptyEntity();

        if ($this->customer_id !== null) {
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
            ->map(function ($dealer): array {
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
            ->map(function ($dealer): array {
                return [
                    'value' => $dealer->id,
                    'text' => $dealer->name_for_lists,
                    'style' => $dealer->dealer == CustomerDealer::Current ? null : 'color: darkgray;',
                ];
            });
        $commissions = $this->Contracts->Commissions->find('list', order: [
            'name',
        ]);

        if ($this->customer_id !== null) {
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

        // load access points with ranges from NMS if possible (only active)
        $this->setAccessPointsViewVarListWithRanges(onlyActive: true);

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Contract id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
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
            ->map(function ($dealer): array {
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
            ->map(function ($dealer): array {
                return [
                    'value' => $dealer->id,
                    'text' => $dealer->name_for_lists,
                    'style' => $dealer->dealer == CustomerDealer::Current ? null : 'color: darkgray;',
                ];
            });
        $commissions = $this->Contracts->Commissions->find('list', order: [
            'name',
        ]);

        if ($this->customer_id !== null) {
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

        // load access points with ranges from NMS if possible
        $this->setAccessPointsViewVarListWithRanges(onlyActive: false);

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Contract id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
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
    private function updateNumber(string|int|null $id = null, bool $flash = true): bool
    {
        $contract = $this->Contracts->get($id);
        $service_type = $this->Contracts->ServiceTypes->get($contract->service_type_id);

        // skip service types without defined number format
        if (empty($service_type->contract_number_format)) {
            return true;
        }

        // generate number
        /** @var \Cake\Datasource\ResultSetInterface<array-key, \App\Model\Entity\Contract> $result */
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
            $firstResult = $result->first();
            if ($firstResult === null || $firstResult->number === null) {
                if ($flash) {
                    $this->Flash->error(__(
                        'The contract number could not be generated.'
                        . ' Please, check the format defined for the service type and try again.',
                    ));
                }

                return false;
            }

            // assign a number for the contract
            $contract->number = $firstResult->number;

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
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Http\Exception\MethodNotAllowedException When badly called.
     */
    public function updateAllNumbers(bool $force = false): ?Response
    {
        $this->getRequest()->allowMethod(['post']);

        /** @var \Cake\Datasource\ResultSetInterface<array-key, \App\Model\Entity\Contract> $contracts */
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
    private function updateSubscriberVerificationCode(string|int|null $id = null, bool $flash = true): bool
    {
        $contract = $this->Contracts->get($id);
        $service_type = $this->Contracts->ServiceTypes->get($contract->service_type_id);

        // skip service types without defined subscriber verification code format
        if (empty($service_type->subscriber_verification_code_format)) {
            return true;
        }

        // generate subscriber verification code
        /** @var \Cake\Datasource\ResultSetInterface<array-key, \App\Model\Entity\Contract> $result */
        $result = $this->Contracts->selectQuery()
            ->select([
                'subscriber_verification_code' => '(' . $service_type->subscriber_verification_code_format . ')',
            ])
            ->where(['id' => $contract->id])
            ->all();

        if ($result->count() == 1) {
            $firstResult = $result->first();
            // check if subscriber verification code could be generated
            if ($firstResult === null || $firstResult->subscriber_verification_code === null) {
                if ($flash) {
                    $this->Flash->error(__(
                        'The subscriber verification code could not be generated.'
                        . ' Please, check the format defined for the service type and try again.',
                    ));
                }

                return false;
            }
            // assign subscriber verification code for the contract
            $contract->subscriber_verification_code = $firstResult->subscriber_verification_code;

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
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Http\Exception\MethodNotAllowedException When badly called.
     */
    public function updateAllSubscriberVerificationCodes(bool $force = false): ?Response
    {
        $this->getRequest()->allowMethod(['post']);

        /** @var \Cake\Datasource\ResultSetInterface<array-key, \App\Model\Entity\Contract> $contracts */
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
     * @return \Cake\Http\Response|null Redirects to view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function setDatesForRelatedBorrowedEquipments(?string $id = null): ?Response
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

        if ($contract->installation_date !== null) {
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

        if ($contract->uninstallation_date !== null) {
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
     * @return \Cake\Http\Response|null Redirects to view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function terminateRelatedBillings(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post']);

        $contract = $this->Contracts->get($id, contain: [
            'Billings' => [
                'Services',
            ],
        ]);

        $billings = new Collection($contract->billings);

        $billings_to_update = $billings->match(['billing_until' => null]);

        if ($contract->termination_date !== null) {
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
     * @return \Cake\Http\Response|null Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function print(?string $id = null, ?string $type = null): ?Response
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
        $contractVersions = (new Collection($contract->contract_versions))->map(function ($contract_version): array {
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
                $this->Flash->error(__('Invalid type of document.'));

                return $this->redirect(['action' => 'print', $id, '?' => $query]);
            }

            // load the contract version to be printed from the query string
            $contractVersionToBeExecuted = null;
            if (!empty($query['contract_version_to_be_executed_id'])) {
                $contractVersionToBeExecuted = (new Collection($contract->contract_versions))->firstMatch([
                    'id' => $query['contract_version_to_be_executed_id'],
                ]);

                if ($contractVersionToBeExecuted === null) {
                    $this->Flash->error(__('Invalid contract version to be executed.'));

                    return $this->redirect(['action' => 'print', $id, '?' => $query]);
                }
            }

            // load the contract version to be terminated from the query string
            $contractVersionToBeTerminated = null;
            if (!empty($query['contract_version_to_be_terminated_id'])) {
                $contractVersionToBeTerminated = (new Collection($contract->contract_versions))->firstMatch([
                    'id' => $query['contract_version_to_be_terminated_id'],
                ]);

                if ($contractVersionToBeTerminated === null) {
                    $this->Flash->error(__('Invalid contract version to be terminated.'));

                    return $this->redirect(['action' => 'print', $id, '?' => $query]);
                }
            }

            // prepare data for validation
            $data = new ContractPrintData(
                type: $printType,
                contract: $contract,
                contractVersionToBeExecuted: $contractVersionToBeExecuted,
                contractVersionToBeTerminated: $contractVersionToBeTerminated,
            );

            // validate the data for the requested document type
            $errors = (new ContractPrintValidator())->validate($data, $query);

            // if there are validation errors, process them
            if ($errors !== []) {
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

        return null;
    }
}
