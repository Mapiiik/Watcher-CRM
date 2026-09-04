<?php
declare(strict_types=1);

namespace App\Controller;

use App\Addresses\Check\AddressCheckRegistry;
use App\Contracts\Check\ContractCheckRegistry;
use App\Contracts\Proposal\ProposalDocumentTypes;
use App\Contracts\Proposal\ProposalProjection;
use App\Controller\Traits\CommonViewVarListsTrait;
use App\Maps\ContractMap;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersionProposal;
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
use Cake\Utility\Hash;
use Cake\Validation\Validation;
use Cake\View\Helper\HtmlHelper;
use Cake\View\View;
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
                    'TaskStates',
                    'TaskTypes',
                    'Users',
                    'Collaborators',
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
                'Users',
                'Collaborators',
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
     * What does not add up on one contract, fetched on its own
     *
     * The checks come to about as much work as the rest of the page put together, and none of
     * it is what somebody opened the contract to read - so the page draws without them and
     * asks for them afterwards.
     *
     * Whoever opened this contract wants to see everything that does not add up on it, so the
     * filter that keeps the checks to what is running is lifted, and the ones that are
     * informational rather than faults are asked too. What would bury a listing of the whole
     * file is a line or two on one record.
     *
     * Most of the address checks are about a customer's address book rather than about one
     * contract and leave themselves out when asked; the one whose subject is the contracts
     * answers, which is why they are asked at all.
     *
     * @param string|null $id Contract id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function problems(?string $id = null): void
    {
        $contract = $this->Contracts->get($id);

        $problems = array_merge(
            (new ContractCheckRegistry(false, $contract->id))->findings(),
            (new AddressCheckRegistry(false, $contract->id))->findings(),
        );

        $this->viewBuilder()->setLayout('ajax');
        $this->set(compact('problems'));
    }

    /**
     * Map method
     *
     * Where the customer is and where the access point serving them stands, and how far that is.
     *
     * @param string|null $id Contract id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function map(?string $id = null): void
    {
        $contract = $this->Contracts->get($id, contain: [
            'Customers',
            'InstallationAddresses',
        ]);

        $connection = (new ContractMap(new HtmlHelper(new View())))->draw($contract);

        $this->set('mapMarkers', $connection->map->markers);
        $this->set('mapPolylines', $connection->map->polylines);
        $this->set('mapDistance', $connection->distance);
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
            $contract = $this->Contracts->patchEntity(
                $contract,
                $this->dataWithAdditionalParameters($this->Contracts, $this->getRequest()->getData()),
            );
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
        // initialize an empty form to be used for PDF generation (validation errors will be added to this form)
        $printForm = new Form();

        // What the page itself shows. The documents are drawn from the proposal's snapshot rather
        // than from any of this, but the page is still where somebody looks the contract over
        // before deciding which papers to draw up.
        $contract = $this->Contracts->get($id, contain: [
            'Commissions',
            'ContractStates',
            'ContractVersions',
            'Customers',
            'InstallationAddresses',
            'InstallationTechnicians',
            'ServiceTypes',
            'UninstallationTechnicians',
            'Creators',
            'Modifiers',
        ]);

        // Every proposal on the contract, across all of its versions - which is why the version is
        // no longer chosen here: it follows from whichever proposal is chosen.
        $proposals = $this->Contracts->ContractVersionProposals->find()
            ->contain(['ContractVersions'])
            ->where(['ContractVersionProposals.contract_id' => $contract->id])
            ->orderBy(['ContractVersionProposals.effective_from' => 'DESC'])
            ->all();

        $query = $this->getRequest()->getQuery();
        unset($query['submit_action']);

        $proposal = $this->chosenProposal($proposals, $query['proposal_id'] ?? null);

        try {
            $printType = ContractPrintType::from($query['document_type'] ?? $type ?? '');
        } catch (ValueError) {
            // tolerate invalid or missing document type for UI rendering
            $printType = null;
        }

        $documentTypes = $this->documentsFor($proposal);

        if (
            $this->getRequest()->getParam('_ext') === 'pdf'
            || $this->getRequest()->getQuery('submit_action') === 'pdf'
        ) {
            if ($printType === null) {
                $this->Flash->error(__('Invalid type of document.'));

                return $this->redirect(['action' => 'print', $id, '?' => $query]);
            }

            $data = $this->printDataFor($printType, $contract, $proposal);
            $errors = (new ContractPrintValidator())->validate($data, $query);

            if ($errors !== []) {
                foreach ($errors['Flash'] ?? [] as $error) {
                    $this->Flash->error($error);
                }
                unset($errors['Flash']);

                if ($this->getRequest()->getParam('_ext') !== 'pdf') {
                    $printForm->setErrors($errors);
                } else {
                    return $this->redirect(['action' => 'print', $id, '?' => $query]);
                }
            } else {
                if ($this->getRequest()->getParam('_ext') !== 'pdf') {
                    return $this->redirect(['action' => 'print', $id, '_ext' => 'pdf', '?' => $query]);
                }

                (new ContractPrintDataEnricher())->enrich($data, $query);

                return (new ContractPrintPdfOutput())->render($data);
            }
        }

        $this->set(compact(
            'printForm',
            'printType',
            'contract',
            'proposals',
            'proposal',
            'documentTypes',
        ));

        return null;
    }

    /**
     * The proposal the papers are for, of the ones this contract has.
     *
     * @param iterable<\App\Model\Entity\ContractVersionProposal> $proposals What it has.
     * @param mixed $chosen What was asked for.
     * @return \App\Model\Entity\ContractVersionProposal|null
     */
    private function chosenProposal(iterable $proposals, mixed $chosen): ?ContractVersionProposal
    {
        if (!is_string($chosen) || $chosen === '') {
            return null;
        }

        return (new Collection($proposals))->firstMatch(['id' => $chosen]);
    }

    /**
     * The documents that proposal may be printed as.
     *
     * @param \App\Model\Entity\ContractVersionProposal|null $proposal The chosen proposal.
     * @return array<string, string>
     */
    private function documentsFor(?ContractVersionProposal $proposal): array
    {
        if ($proposal === null) {
            return [];
        }

        $snapshot = $proposal->stateOfThings();
        $offered = (new ProposalDocumentTypes())->for(
            $proposal,
            (bool)($snapshot->part('contract')['service_type']['have_equipments'] ?? false),
            ($snapshot->part('version')['conclusion_date'] ?? null) !== null,
        );

        $documents = [];
        foreach ($offered as $document) {
            $documents[$document->value] = $document->label();
        }

        return $documents;
    }

    /**
     * What to print, put together from the proposal's snapshot rather than from the live records.
     *
     * @param \App\Model\Enum\ContractPrintType $type Which document.
     * @param \App\Model\Entity\Contract $contract The contract, for the page and its checks.
     * @param \App\Model\Entity\ContractVersionProposal|null $proposal The chosen proposal.
     * @return \App\Service\ContractPrint\ContractPrintData
     */
    private function printDataFor(
        ContractPrintType $type,
        Contract $contract,
        ?ContractVersionProposal $proposal,
    ): ContractPrintData {
        if ($proposal === null) {
            $data = new ContractPrintData($type, $contract, null, null);
            $data->proposal = null;

            return $data;
        }

        $snapshot = $proposal->stateOfThings();
        $projection = new ProposalProjection();
        $changes = $proposal->proposedChanges();

        $asItStood = $snapshot->hydrate();
        $executed = $projection->projectVersion($snapshot->hydrateVersion(), $changes->version);

        // A proposal that replaces an earlier version names it; one that ends the contract ends the
        // version it belongs to, so that is the one the termination paper is about.
        $replaced = $snapshot->hydrateTerminatedVersion();
        $terminated = match (true) {
            $replaced !== null => $projection->projectTerminatedVersion(
                $replaced,
                $proposal->effective_from,
            ),
            $changes->endsTheContract() => $executed,
            default => null,
        };

        $data = new ContractPrintData($type, $asItStood, $executed, $terminated);
        $data->proposal = $proposal;
        $data->contractNumberToBeTerminated = $proposal->terminated_contract_number;
        $data->effectiveDateOfAmendment = $proposal->effective_from;
        $data->projectedBillings = $projection->projectBillings(
            $asItStood->billings,
            $changes,
            $proposal->effective_from,
            $snapshot->servicesChosenBy($changes),
        );

        return $data;
    }
}
