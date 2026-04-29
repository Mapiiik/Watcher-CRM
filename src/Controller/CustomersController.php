<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Customer;
use App\Model\Enum\CustomerPrintType;
use App\Service\CustomerPrint\CustomerPrintData;
use App\Service\CustomerPrint\CustomerPrintPdfOutput;
use App\Service\CustomerPrint\CustomerPrintValidator;
use App\View\PdfView;
use Cake\Database\Expression\QueryExpression;
use Cake\Form\Form;
use Cake\I18n\Date;
use Cake\Utility\Hash;
use Cake\Validation\Validation;
use Override;
use ValueError;

// filter for fulltext search
const CUSTOMERS_FULLTEXT_SEARCH_FILTER = <<<SQL
    SELECT
        Customers.id
    FROM
        Customers
        LEFT JOIN (
            SELECT
                Contracts.customer_id,
                STRING_AGG(
                    CONCAT_WS(
                        ' ',
                        Contracts.number,
                        Contracts.subscriber_verification_code
                    ),
                    ' '
                ) AS txt
            FROM
                Contracts
            GROUP BY
                1
        ) Contracts ON (
            Contracts.customer_id = Customers.id
        ) 
        LEFT JOIN (
            SELECT 
                Addresses.customer_id, 
                STRING_AGG(
                    CONCAT_WS(
                        ' ',
                        Addresses.first_name,
                        Addresses.last_name,
                        Addresses.company,
                        Addresses.street, 
                        Addresses.number,
                        Addresses.city,
                        Addresses.zip
                    ), 
                    ' '
                ) AS txt 
            FROM 
                Addresses 
            GROUP BY 
                1
        ) Addresses ON (
            Addresses.customer_id = Customers.id
        ) 
        LEFT JOIN (
            SELECT 
                Emails.customer_id, 
                STRING_AGG(Emails.email, ' ') AS txt 
            FROM 
                Emails 
            GROUP BY 
                1
        ) Emails ON (
            Emails.customer_id = Customers.id
        ) 
        LEFT JOIN (
            SELECT 
                Phones.customer_id, 
                STRING_AGG(Phones.phone, ' ') AS txt_1,
                STRING_AGG(REPLACE(Phones.phone, ' ', ''), ' ') AS txt_2,
                STRING_AGG(REGEXP_REPLACE(REGEXP_REPLACE(Phones.phone, '\+\d+', ''), '\s', '', 'g'), ' ') AS txt_3
            FROM 
                Phones 
            GROUP BY 
                1
        ) Phones ON (
            Phones.customer_id = Customers.id
        ) 
        LEFT JOIN (
            SELECT 
            Ip_Addresses.customer_id, 
                STRING_AGG(Ip_Addresses.ip_address :: character varying, ' ') AS txt 
            FROM 
                Ip_Addresses
            GROUP BY 
                1
        ) Ip_Addresses ON (
            Ip_Addresses.customer_id = Customers.id
        ) 
    WHERE 
        to_tsvector (
            CONCAT_WS(
                ' ',
                Customers.nid + :customer_series,
                Customers.identity_number,
                Customers.vat_number,
                Customers.first_name, 
                Customers.last_name,
                Customers.company, 
                Contracts.txt,
                Addresses.txt,
                Emails.txt,
                Phones.txt_1,
                Phones.txt_2,
                Phones.txt_3,
                Ip_Addresses.txt
            )
        ) @@ websearch_to_tsquery(:search) 
    GROUP BY 
        Customers.id
    SQL;

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
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
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
        if ($allow_advanced_search && $advanced_search && !empty($search)) {
            // advanced search
            $customersQuery->where([
                'OR' => [
                    'Customers.company ILIKE' => '%' . trim($search) . '%',
                    'Customers.first_name ILIKE' => '%' . trim($search) . '%',
                    'Customers.last_name ILIKE' => '%' . trim($search) . '%',
                    'Customers.id IN (' . CUSTOMERS_FULLTEXT_SEARCH_FILTER . ')',
                ],
            ]);
            $customersQuery->bind(':customer_series', (int)env('CUSTOMER_SERIES', '0'), 'integer');
            $customersQuery->bind(':search', trim($search), 'string');
        } elseif (ctype_digit($search) && strlen($search) <= 10) { // strlen($search) <= 19 for BIGINT
            // search by customer number
            $customersQuery->where([
                'OR' => [
                    '(Customers.nid + ' . (int)env('CUSTOMER_SERIES', '0') . ') =' => (int)trim($search),
                    'Customers.identity_number' => trim($search),
                ],
            ]);
        } elseif (!empty($search) || !$allow_advanced_search) {
            // notify the required use of the customer number
            $this->Flash->set(__('Please use the customer number or company identification number in the search.'));
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
        if ($allow_advanced_search && is_array($label_ids) && !empty($label_ids)) {
            $customersQuery->where([
                'Customers.id IN ('
                . ' SELECT customer_id FROM customer_labels '
                . 'GROUP BY customer_id '
                . 'HAVING array_agg(label_id) @> ARRAY['
                    . implode(',', array_map(fn($label) => "'{$label}'::uuid", $label_ids))
                . ']'
                . ')',
            ]);
        }

        // filter by not labels
        if ($allow_advanced_search && is_array($not_label_ids) && !empty($not_label_ids)) {
            $customersQuery->where([
                'Customers.id NOT IN (
                    SELECT customer_id FROM customer_labels
                    WHERE label_id = ANY(ARRAY['
                        . implode(',', array_map(fn($label) => "'{$label}'::uuid", $not_label_ids))
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
        $customersQuery->contain([
            'Contracts' => [
                'ContractStates',
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
            'IpAddresses' => [
                'Contracts',
            ],
            'IpNetworks' => [
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
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $customer = $this->Customers->get($id, contain: [
            'Addresses' => [
                'Countries',
            ],
            'Billings' => [
                'Contracts' => [
                    'ContractStates',
                ],
                'Services',
                'conditions' => $this->request->getQuery('show_historical_records') === '1' ?
                    [] : [
                        'OR' => [
                            'Billings.billing_until >=' => Date::now()->firstOfMonth(),
                            'Billings.billing_until IS' => null,
                        ],
                    ],
            ],
            'BorrowedEquipments' => [
                'Contracts' => [
                    'ContractStates',
                ],
                'EquipmentTypes',
                'conditions' => $this->request->getQuery('show_historical_records') === '1' ?
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
                'Dealers',
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
        ] + (
            $this->request->getQuery('show_historical_records') === '1' ? [
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
            ] : []
        ));

        $this->set(compact(
            'customer',
        ));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $customer = $this->Customers->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $customer = $this->Customers->patchEntity($customer, $this->getRequest()->getData());
            if ($this->Customers->save($customer)) {
                $this->Flash->success(__('The customer has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $customer->id]);
            }
            $this->Flash->error(__('The customer could not be saved. Please, try again.'));
        }
        $accountingProfiles = $this->Customers->AccountingProfiles->find('list', order: [
            'name',
        ]);

        $this->set(compact('customer', 'accountingProfiles'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Customer id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $customer = $this->Customers->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $customer = $this->Customers->patchEntity($customer, $this->getRequest()->getData());
            if ($this->Customers->save($customer)) {
                $this->Flash->success(__('The customer has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $customer->id]);
            }
            $this->Flash->error(__('The customer could not be saved. Please, try again.'));
        }
        $accountingProfiles = $this->Customers->AccountingProfiles->find('list', order: [
            'name',
        ]);

        $this->set(compact('customer', 'accountingProfiles'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Customer id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
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
     * @return \Cake\Http\Response|null|void Renders print.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function print(?string $id = null, ?string $type = null)
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
            $errors = (new CustomerPrintValidator())->validate($data, $query);

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
    }

    /**
     * Identity Number Check
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function identityNumberCheck()
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
