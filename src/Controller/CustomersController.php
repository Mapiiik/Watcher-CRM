<?php
declare(strict_types=1);

namespace App\Controller;

use App\BusinessRegister\Dto\Subject;
use App\BusinessRegister\Registry;
use App\Database\Expression\FulltextSearchCustomersExpression;
use App\Model\Entity\Customer;
use App\Model\Enum\CustomerPrintType;
use App\Service\CustomerPrint\CustomerPrintData;
use App\Service\CustomerPrint\CustomerPrintPdfOutput;
use App\Service\CustomerPrint\CustomerPrintValidator;
use App\View\PdfView;
use Cake\Core\Configure;
use Cake\Database\Expression\QueryExpression;
use Cake\Form\Form;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\ORM\Association;
use Cake\Utility\Hash;
use Cake\Validation\Validation;
use Override;
use RuntimeException;
use Settings\Utility\Settings;
use ValueError;

/**
 * Customers Controller
 *
 * @property \App\Model\Table\CustomersTable $Customers
 */
class CustomersController extends AppController
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
     * @return void Renders view
     */
    public function index(): void
    {
        /**
         * Handle filters persistency in session
         */
        // advanced search enabled/disabled
        if (!is_null($this->getRequest()->getQuery('advanced_search'))) {
            $this->getRequest()->getSession()->write(
                'Config.Customers.filter.advanced_search',
                $this->getRequest()->getQuery('advanced_search') == '1',
            );
        }
        // filter by search term
        if (!is_null($this->getRequest()->getQuery('search'))) {
            $this->getRequest()->getSession()->write(
                'Config.Customers.filter.search',
                trim($this->getRequest()->getQuery('search')),
            );
        }
        // filter by contract state
        if (!is_null($this->getRequest()->getQuery('contract_state_id'))) {
            $this->getRequest()->getSession()->write(
                'Config.Customers.filter.contract_state_id',
                $this->getRequest()->getQuery('contract_state_id'),
            );
        }
        // filter by service type
        if (!is_null($this->getRequest()->getQuery('service_type_id'))) {
            $this->getRequest()->getSession()->write(
                'Config.Customers.filter.service_type_id',
                $this->getRequest()->getQuery('service_type_id'),
            );
        }
        // filter by labels
        if (!is_null($this->getRequest()->getQuery('label_ids'))) {
            $labels = [];
            if (is_array($this->getRequest()->getQuery('label_ids'))) {
                foreach ($this->getRequest()->getQuery('label_ids') as $labelId) {
                    if (is_string($labelId) && Validation::uuid($labelId)) {
                        $labels[] = $labelId;
                    }
                }
            }
            $this->getRequest()->getSession()->write(
                'Config.Customers.filter.label_ids',
                $labels,
            );
            unset($labels);
        }
        // filter by not labels
        if (!is_null($this->getRequest()->getQuery('not_label_ids'))) {
            $labels = [];
            if (is_array($this->getRequest()->getQuery('not_label_ids'))) {
                foreach ($this->getRequest()->getQuery('not_label_ids') as $labelId) {
                    if (is_string($labelId) && Validation::uuid($labelId)) {
                        $labels[] = $labelId;
                    }
                }
            }
            $this->getRequest()->getSession()->write(
                'Config.Customers.filter.not_label_ids',
                $labels,
            );
            unset($labels);
        }
        $filter = $this->getRequest()->getSession()->read('Config.Customers.filter');

        // filter
        $advanced_search = $filter['advanced_search']
            ?? Hash::get($this->user_settings, 'customers.advanced_search', false);
        $search = (string)($filter['search'] ?? '');
        $contract_state_id = $filter['contract_state_id'] ?? null;
        $service_type_id = $filter['service_type_id'] ?? null;
        $label_ids = $filter['label_ids'] ?? [];
        $not_label_ids = $filter['not_label_ids'] ?? [];
        $allow_advanced_search = in_array($this->getRequest()->getAttribute('identity')['role'] ?? null, [
            'network-manager',
            'sales-representative',
            'sales-manager',
            'bookkeeper',
            'admin',
        ]);

        $customersQuery = $this->Customers->find();

        // search
        if ($allow_advanced_search && $advanced_search && ($search !== '' && $search !== '0')) {
            // advanced search
            $customersQuery->where([
                'OR' => [
                    'Customers.company ILIKE' => '%' . trim($search) . '%',
                    'Customers.first_name ILIKE' => '%' . trim($search) . '%',
                    'Customers.last_name ILIKE' => '%' . trim($search) . '%',
                    new FulltextSearchCustomersExpression(trim($search)),
                ],
            ]);
        } elseif (ctype_digit($search) && strlen($search) <= 10) { // strlen($search) <= 19 for BIGINT
            // search by customer number
            $customersQuery->where([
                'OR' => [
                    '(Customers.nid + ' . (int)Configure::read('Customers.series') . ') =' => (int)trim($search),
                    'Customers.identity_number' => trim($search),
                ],
            ]);
        } elseif ($search !== '' && $search !== '0' || !$allow_advanced_search) {
            // notify the required use of the customer number
            $this->Flash->info(__('Please use the customer number or company identification number in the search.'));
            $customersQuery->where([
                'false',
            ]);
        }

        // filter by contract state
        if ($allow_advanced_search && is_string($contract_state_id) && Validation::uuid($contract_state_id)) {
            $subquery = $this->Customers->Contracts->find()
                ->select(['id'])
                ->where(function (QueryExpression $exp) {
                    return $exp->equalFields(
                        'Contracts.customer_id',
                        'Customers.id',
                    );
                })
                ->andWhere([
                    'Contracts.contract_state_id' => $contract_state_id,
                ]);

            $customersQuery->where(function (QueryExpression $exp) use ($subquery) {
                return $exp->exists($subquery);
            });
        }

        // filter by service type
        if ($allow_advanced_search && is_string($service_type_id) && Validation::uuid($service_type_id)) {
            $subquery = $this->Customers->Contracts->find()
                ->select(['id'])
                ->where(function (QueryExpression $exp) {
                    return $exp->equalFields(
                        'Contracts.customer_id',
                        'Customers.id',
                    );
                })
                ->andWhere([
                    'Contracts.service_type_id' => $service_type_id,
                ]);

            $customersQuery->where(function (QueryExpression $exp) use ($subquery) {
                return $exp->exists($subquery);
            });
        }

        // filter by labels
        if ($allow_advanced_search && is_array($label_ids) && $label_ids !== []) {
            $customersQuery->where([
                'Customers.id IN ('
                . ' SELECT customer_id FROM customer_labels '
                . 'GROUP BY customer_id '
                . 'HAVING array_agg(label_id) @> ARRAY['
                    . implode(',', array_map(fn(string $label): string => sprintf("'%s'::uuid", $label), $label_ids))
                . ']'
                . ')',
            ]);
        }

        // filter by not labels
        if ($allow_advanced_search && is_array($not_label_ids) && $not_label_ids !== []) {
            $customersQuery->where([
                'Customers.id NOT IN (
                    SELECT customer_id FROM customer_labels
                    WHERE label_id = ANY(ARRAY['
                        . implode(',', array_map(
                            fn(string $label): string => sprintf("'%s'::uuid", $label),
                            $not_label_ids,
                        ))
                    . '])
                )',
            ]);
        }

        // filter form
        $filterForm = new Form();
        $filterForm->setData([
            'advanced_search' => $advanced_search,
            'search' => $search,
            'contract_state_id' => $contract_state_id,
            'service_type_id' => $service_type_id,
            'label_ids' => $label_ids,
            'not_label_ids' => $not_label_ids,
        ]);
        $this->set('filterForm', $filterForm);

        // contain related data
        //
        // Each of these is fetched with the `select` strategy rather than the `subquery` one
        // CakePHP 5.4 made the default for hasMany. That strategy joins the listing query in as a
        // derived table - the whole query, not the ids it returned - so the listing is run again
        // for every association, and it is several times slower here at any page size the page
        // size selector offers.
        $customersQuery->contain([
            'Contracts' => [
                'strategy' => Association::STRATEGY_SELECT,
                'ContractStates',
            ],
            'CustomerLabels' => [
                'strategy' => Association::STRATEGY_SELECT,
                'Labels',
                'sort' => [
                    'Labels.name',
                ],
                'conditions' => [
                    'CustomerLabels.contract_id IS' => null,
                ],
            ],
            'IpAddresses' => [
                'strategy' => Association::STRATEGY_SELECT,
                'Contracts',
            ],
            'IpNetworks' => [
                'strategy' => Association::STRATEGY_SELECT,
                'Contracts',
            ],
            'AccountingProfiles',
        ]);

        // pagination settings
        $this->paginate = [
            'order' => [
                'Customers.nid' => 'DESC',
            ],
        ];

        // paginate results
        $customers = $this->paginate($customersQuery);

        $contractStates = $this->Customers->Contracts->ContractStates->find('list', order: [
            'name',
        ]);
        $serviceTypes = $this->Customers->Contracts->ServiceTypes->find('list', order: [
            'name',
        ]);

        $labels = $this->Customers->CustomerLabels->Labels->find('list', order: [
            'name',
        ]);

        $this->set(compact('customers', 'labels', 'allow_advanced_search', 'contractStates', 'serviceTypes'));
    }

    /**
     * View method
     *
     * @param string|null $id Customer id.
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
            'Addresses' => [
                'Countries',
            ],
            'Billings' => [
                'Contracts' => [
                    'ContractStates',
                ],
                'Services',
                'finder' => $show_historical_records ? 'all' : 'activeOrFuture',
            ],
            'BorrowedEquipments' => [
                'Contracts' => [
                    'ContractStates',
                ],
                'EquipmentTypes',
                'conditions' => $show_historical_records ?
                    [] : [
                        'OR' => [
                            'BorrowedEquipments.borrowed_until >=' => Date::now()->firstOfMonth(),
                            'BorrowedEquipments.borrowed_until IS' => null,
                        ],
                    ],
            ],
            'Contracts' => [
                'ContractStates',
                'ServiceTypes',
                'InstallationAddresses',
                'ContractVersions' => [
                    'fields' => [
                        'ContractVersions.id',
                        'ContractVersions.contract_id',
                        'ContractVersions.obligation_until',
                    ],
                ],
            ],
            'CustomerLabels' => [
                'Labels',
                'sort' => [
                    'Labels.name',
                ],
                'conditions' => [
                    'CustomerLabels.contract_id IS' => null,
                ],
            ],
            'Emails',
            'Logins',
            'Phones',
            'SoldEquipments' => [
                'Contracts' => [
                    'ContractStates',
                ],
                'EquipmentTypes',
            ],
            'Tasks' => [
                'Contracts',
                'TaskTypes',
                'TaskStates',
                'Users',
                'Collaborators',
            ],
            'AccountingProfiles',
            'IpAddresses' => [
                'Contracts' => [
                    'ContractStates',
                ],
            ],
            'IpNetworks' => [
                'Contracts' => [
                    'ContractStates',
                ],
            ],
            'Creators',
            'Modifiers',
        ];

        if ($show_historical_records) {
            $contain = array_merge($contain, [
                'RemovedIpAddresses' => [
                    'Contracts' => [
                        'ContractStates',
                    ],
                ],
                'RemovedIpNetworks' => [
                    'Contracts' => [
                        'ContractStates',
                    ],
                ],
            ]);
        }

        $customer = $this->Customers->get($id, contain: $contain);

        $this->set(compact(
            'customer',
        ));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $customer = $this->Customers->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $customer = $this->Customers->patchEntity($customer, $this->getRequest()->getData());

            if ($this->getRequest()->getData('refresh') == 'refresh' || $customer->hasErrors()) {
                // only refresh

                // perform a lookup to pre-fill the customer fields based on the selected register entry
                $this->patchFromBusinessRegister($customer);
            } else {
                if ($this->Customers->save($customer)) {
                    $this->Flash->success(__('The customer has been saved.'));

                    return $this->afterAddRedirect(['action' => 'view', $customer->id]);
                }
                $this->Flash->error(__('The customer could not be saved. Please, try again.'));
            }
        }
        $accountingProfiles = $this->Customers->AccountingProfiles->find('list', order: [
            'name',
        ]);

        // set the registers offered by the company search widget (Select2)
        $businessRegisterSources = Registry::options();
        $businessRegisterDefaultSource = Registry::defaultKey(
            trim(Settings::getString('core.business_register.default_source')) ?: null,
        );

        // what the form is working from, so a second submit still knows which company it was
        $businessRegisterSelection = $this->businessRegisterSelection();

        $this->set(compact(
            'customer',
            'accountingProfiles',
            'businessRegisterSources',
            'businessRegisterDefaultSource',
            'businessRegisterSelection',
        ));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Customer id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $customer = $this->Customers->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $customer = $this->Customers->patchEntity($customer, $this->getRequest()->getData());

            if ($this->getRequest()->getData('refresh') == 'refresh' || $customer->hasErrors()) {
                // only refresh

                // perform a lookup to pre-fill the customer fields based on the selected register entry
                $this->patchFromBusinessRegister($customer);
            } else {
                if ($this->Customers->save($customer)) {
                    $this->Flash->success(__('The customer has been saved.'));

                    return $this->afterEditRedirect(['action' => 'view', $customer->id]);
                }
                $this->Flash->error(__('The customer could not be saved. Please, try again.'));
            }
        }
        $accountingProfiles = $this->Customers->AccountingProfiles->find('list', order: [
            'name',
        ]);

        // set the registers offered by the company search widget (Select2)
        $businessRegisterSources = Registry::options();
        $businessRegisterDefaultSource = Registry::defaultKey(
            trim(Settings::getString('core.business_register.default_source')) ?: null,
        );

        // what the form is working from, so a second submit still knows which company it was
        $businessRegisterSelection = $this->businessRegisterSelection();

        $this->set(compact(
            'customer',
            'accountingProfiles',
            'businessRegisterSources',
            'businessRegisterDefaultSource',
            'businessRegisterSelection',
        ));

        return null;
    }

    /**
     * Fills the customer in from the register entry the operator picked, if they picked one.
     *
     * Validation is skipped on the way in: an identification number that does not hold up is
     * still what the register says, and the operator is the one to decide what to do about it.
     *
     * @param \App\Model\Entity\Customer $customer The customer being added or edited.
     * @return void
     */
    private function patchFromBusinessRegister(Customer $customer): void
    {
        $businessRegisterKey = $this->getRequest()->getData('business_register_search');
        if (empty($businessRegisterKey) || !is_string($businessRegisterKey)) {
            return;
        }

        $officerKey = $this->getRequest()->getData('business_register_officer');

        try {
            $this->Customers->patchEntity(
                $customer,
                $this->loadPatchDataFromBusinessRegister(
                    $businessRegisterKey,
                    is_string($officerKey) && $officerKey !== '' ? $officerKey : null,
                ),
                ['validate' => false],
            );
        } catch (RuntimeException $e) {
            $this->Flash->error(__(
                'Could not retrieve the company from the business register: {0}',
                $e->getMessage(),
            ));
        }
    }

    /**
     * What the form is working from: the entry that was picked, what to call it, and who the
     * company may be represented by.
     *
     * The picked entry has to come back with the form. The search field is filled in by the
     * script as it is searched, so a form rendered again knows nothing of it - and a second
     * submit, which is what choosing a person is, would arrive without the company.
     *
     * The entry has been read once already by then, so this is a cache read rather than another
     * request to the register.
     *
     * @return array{key: ?string, label: ?string, officer: ?string, officers: list<\App\BusinessRegister\Dto\Officer>}
     */
    private function businessRegisterSelection(): array
    {
        $nothing = ['key' => null, 'label' => null, 'officer' => null, 'officers' => []];

        $businessRegisterKey = $this->getRequest()->getData('business_register_search');
        if (empty($businessRegisterKey) || !is_string($businessRegisterKey)) {
            return $nothing;
        }

        try {
            $subject = $this->subjectFromBusinessRegister($businessRegisterKey);
        } catch (RuntimeException) {
            // the form already says what went wrong, and it was said once
            return $nothing;
        }

        $officerKey = $this->getRequest()->getData('business_register_officer');
        $officerKey = is_string($officerKey) && $subject->officer($officerKey) !== null ? $officerKey : null;

        return [
            'key' => $businessRegisterKey,
            'label' => trim((string)$subject->name) ?: $businessRegisterKey,
            'officer' => $officerKey,
            'officers' => $subject->officers,
        ];
    }

    /**
     * The entry a business register holds under the provided key.
     *
     * @param string $businessRegisterKey Expected format: "source|reference" (e.g., "ares|27074358").
     * @return \App\BusinessRegister\Dto\Subject The entry as the register answered it.
     * @throws \RuntimeException If the key makes no sense, or the register no longer holds it.
     */
    private function subjectFromBusinessRegister(string $businessRegisterKey): Subject
    {
        // expect format "source|reference", e.g. "ares|27074358"
        [
            $businessRegisterSource,
            $businessRegisterReference,
        ] = explode('|', $businessRegisterKey, limit: 2) + [null, null];

        if (empty($businessRegisterSource) || empty($businessRegisterReference)) {
            throw new RuntimeException(__('Invalid business register reference.'));
        }

        /** @var \App\BusinessRegister\Dto\Subject|null $subject */
        $subject = Registry::byReferenceFromCache(
            key: $businessRegisterSource,
            reference: $businessRegisterReference,
        )->orFail(__('The business register is not configured.'));

        if ($subject === null) {
            throw new RuntimeException(__('The company is no longer held by the register.'));
        }

        return $subject;
    }

    /**
     * Loads patch data from a business register based on the provided key.
     *
     * @param string $businessRegisterKey Expected format: "source|reference" (e.g., "ares|27074358").
     * @param string|null $officerKey The person the company is to be represented by, of those the
     *      register named, or null to take the entry as it stands.
     * @return array The patch data for the customer.
     * @throws \RuntimeException If the register cannot be reached or refuses the request.
     */
    private function loadPatchDataFromBusinessRegister(
        string $businessRegisterKey,
        ?string $officerKey = null,
    ): array {
        // The company may be represented by any of several people, and which one is the operator's
        // choice. A key naming nobody is one left over from a company picked before this one, and
        // what the entry says of itself stands instead.
        $subject = $this->subjectFromBusinessRegister($businessRegisterKey)->representedBy($officerKey);

        // every field is handed over, nulls included: a sole trader picked after a company has to
        // clear the company out, or the CRM would go on reading them as a legal entity
        return [
            'company' => $subject->company,
            'title' => $subject->title,
            'first_name' => $subject->firstName,
            'last_name' => $subject->lastName,
            'suffix' => $subject->suffix,
            'date_of_birth' => $subject->dateOfBirth,
            'identity_number' => $subject->identityNumber,
            'vat_number' => $subject->vatNumber,
        ];
    }

    /**
     * Delete method
     *
     * @param string|null $id Customer id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $customer = $this->Customers->get($id);
        if ($this->Customers->delete($customer)) {
            $this->Flash->success(__('The customer has been deleted.'));
        } else {
            $this->flashValidationErrors($customer->getErrors());
            $this->Flash->error(__('The customer could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }

    /**
     * Print method
     *
     * @param string|null $id Customer id.
     * @param string|null $type Document type.
     * @return \Cake\Http\Response|null Renders print.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function print(?string $id = null, ?string $type = null): ?Response
    {
        // prepare supported document types for selection in the print view
        $documentTypes = [
            CustomerPrintType::GdprNew->value => CustomerPrintType::GdprNew->label(),
            CustomerPrintType::GdprChange->value => CustomerPrintType::GdprChange->label(),
        ];
        $this->set('documentTypes', $documentTypes);

        // initialize an empty form to be used for PDF generation (validation errors will be added to this form)
        $printForm = new Form();

        // load the customer with all related data needed for rendering the print views and generating the PDF documents
        $customer = $this->Customers->get($id, contain: [
            'AccountingProfiles',
            'Addresses' => ['Countries'],
            'Emails',
            'Phones',
            'Creators',
            'Modifiers',
        ]);

        // load query parameters from the request
        $query = $this->getRequest()->getQuery();

        // keep only relevant query parameters for PDF generation in the query string
        unset($query['submit_action']);

        // load the print type from the query string or use the one from the URL parameter
        try {
            $printType = CustomerPrintType::from($query['document_type'] ?? $type ?? '');
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

            // prepare data for validation
            $data = new CustomerPrintData(
                type: $printType,
                customer: $customer,
            );

            // validate the data for the requested document type
            $errors = (new CustomerPrintValidator())->validate($data);

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

                // render the PDF document based on the enriched data
                return (new CustomerPrintPdfOutput())->render($data);
            }
        }

        // render the print view for HTML requests
        $this->set(compact(
            'printForm',
            'printType',
            'customer',
        ));

        return null;
    }

    /**
     * Identity Number Check
     *
     * @return void Renders view
     */
    public function identityNumberCheck(): void
    {
        $customers = $this->Customers
            ->find()
            ->where('identity_number IS NOT NULL')
            ->orderBy([
                'Customers.nid' => 'DESC',
            ])
            ->all()
            ->filter(
                function (Customer $customer): bool {
                    return !$customer->verifyIdentityNumber();
                },
            );

        $this->set(compact('customers'));
    }
}
