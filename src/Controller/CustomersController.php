<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Form\Form;

// filter for fulltext search
const CUSTOMERS_FULLTEXT_SEARCH_FILTER = "SELECT
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
            STRING_AGG(Phones.phone, ' ') AS txt 
        FROM 
            Phones 
        GROUP BY 
            1
    ) Phones ON (
        Phones.customer_id = Customers.id
    ) 
    LEFT JOIN (
        SELECT 
            Ips.customer_id, 
            STRING_AGG(Ips.ip :: character varying, ' ') AS txt 
        FROM 
            Ips
        GROUP BY 
            1
    ) Ips ON (
        Ips.customer_id = Customers.id
    ) 
WHERE 
    to_tsvector (
        CONCAT_WS(
            ' ',
            Customers.id + :customer_series,
            Customers.ic,
            Customers.dic,
            Customers.first_name, 
            Customers.last_name,
            Customers.company, 
            Contracts.txt,
            Addresses.txt,
            Emails.txt,
            Phones.txt, 
            Ips.txt
        )
    ) @@ websearch_to_tsquery(:search) 
GROUP BY 
    Customers.id
";

/**
 * Customers Controller
 *
 * @property \App\Model\Table\CustomersTable $Customers
 * @method \App\Model\Entity\Customer[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class CustomersController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        // persistent filter data
        if (!is_null($this->getRequest()->getQuery('advanced_search'))) {
            $this->getRequest()->getSession()->write(
                'Config.Customers.filter.advanced_search',
                $this->getRequest()->getQuery('advanced_search') == '1'
            );
        }
        if (!is_null($this->getRequest()->getQuery('search'))) {
            $this->getRequest()->getSession()->write(
                'Config.Customers.filter.search',
                trim($this->getRequest()->getQuery('search'))
            );
        }
        if (!is_null($this->getRequest()->getQuery('labels'))) {
            $labels = [];
            if (is_array($this->getRequest()->getQuery('labels'))) {
                foreach ($this->getRequest()->getQuery('labels') as $label) {
                    if (is_numeric($label)) {
                        $labels[] = (int)$label;
                    }
                }
            }
            $this->getRequest()->getSession()->write(
                'Config.Customers.filter.labels',
                $labels
            );
            unset($labels);
        }
        $filter = $this->getRequest()->getSession()->read('Config.Customers.filter');

        // filter
        $advanced_search = $filter['advanced_search'] ?? false;
        $search = (string)($filter['search'] ?? '');
        $labels = $filter['labels'] ?? [];
        $allow_advanced_search = in_array($this->getRequest()->getAttribute('identity')['role'] ?? null, [
            'admin',
            'network-manager',
            'sales-manager',
        ]);

        $customersQuery = $this->Customers->find();

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
        } elseif (is_numeric($search)) {
            // search by customer number
            $customersQuery->where([
                'OR' => [
                    '(Customers.id::bigint + ' . (int)env('CUSTOMER_SERIES', '0') . ') =' => (int)trim($search),
                    'Customers.ic' => trim($search),
                ],
            ]);
        } elseif (!empty($search) || !$allow_advanced_search) {
            // notify the required use of the customer number
            $this->Flash->set(__('Please use the customer number or company identification number in the search.'));
            $customersQuery->where([
                'false',
            ]);
        }

        // filter labels
        if ($labels) {
            $customersQuery->where([
                'Customers.id IN ('
                . ' SELECT customer_id FROM customer_labels '
                . 'GROUP BY customer_id '
                . 'HAVING array_agg(label_id) @> ARRAY[' . implode(',', $labels) . ']'
                . ')',
            ]);
        }

        // filter form
        $filterForm = new Form();
        $filterForm->setData([
            'advanced_search' => $advanced_search,
            'search' => $search,
            'labels' => $labels,
        ]);
        $this->set('filterForm', $filterForm);

        $this->paginate = [
            'contain' => [
                'TaxRates',
                'Contracts' => ['ContractStates'],
                'CustomerLabels' => [
                    'Labels',
                    'sort' => ['Labels.name'],
                ],
                'Ips' => ['Contracts'],
                'IpNetworks' => ['Contracts'],
            ],
            'order' => [
                'Customers.id' => 'DESC',
            ],
        ];

        $customers = $this->paginate($customersQuery);
        $labels = $this->Customers->CustomerLabels->Labels->find('list', ['order' => 'name']);

        $invoice_delivery_types = $this->Customers->invoice_delivery_types;

        $this->set(compact('customers', 'labels', 'invoice_delivery_types', 'allow_advanced_search'));
    }

    /**
     * View method
     *
     * @param string|null $id Customer id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $customer = $this->Customers->get($id, [
            'contain' => [
                'TaxRates',
                'Addresses' => ['Countries'],
                'Billings' => ['Contracts' => ['ContractStates'], 'Services'],
                'BorrowedEquipments' => ['Contracts' => ['ContractStates'], 'EquipmentTypes'],
                'Contracts' => [
                    'ContractStates',
                    'ServiceTypes',
                    'InstallationAddresses',
                ],
                'Emails',
                'CustomerLabels' => [
                    'Labels',
                    'sort' => ['Labels.name'],
                ],
                'Logins',
                'Phones',
                'SoldEquipments' => ['Contracts' => ['ContractStates'], 'EquipmentTypes'],
                'Tasks' => ['Contracts', 'TaskTypes', 'TaskStates', 'Dealers'],
                'Ips' => ['Contracts' => ['ContractStates']],
                'RemovedIps' => ['Contracts' => ['ContractStates']],
                'IpNetworks' => ['Contracts' => ['ContractStates']],
                'RemovedIpNetworks' => ['Contracts' => ['ContractStates']],
                'Creators',
                'Modifiers',
            ],
        ]);

        $invoice_delivery_types = $this->Customers->invoice_delivery_types;
        $address_types = $this->Customers->Addresses->types;
        $login_rights = $this->Customers->Logins->rights;

        $this->set('ip_address_types_of_use', $this->Customers->Ips->types_of_use);
        $this->set('ip_network_types_of_use', $this->Customers->IpNetworks->types_of_use);

        $this->set(compact(
            'customer',
            'invoice_delivery_types',
            'address_types',
            'login_rights'
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

                return $this->redirect(['action' => 'view', $customer->id]);
            }
            $this->Flash->error(__('The customer could not be saved. Please, try again.'));
        }
        $taxRates = $this->Customers->TaxRates->find('list', ['order' => 'name']);

        $invoice_delivery_types = $this->Customers->invoice_delivery_types;

        $this->set(compact('customer', 'taxRates', 'invoice_delivery_types'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Customer id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $customer = $this->Customers->get($id, [
            'contain' => [],
        ]);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $customer = $this->Customers->patchEntity($customer, $this->getRequest()->getData());
            if ($this->Customers->save($customer)) {
                $this->Flash->success(__('The customer has been saved.'));

                return $this->redirect(['action' => 'view', $customer->id]);
            }
            $this->Flash->error(__('The customer could not be saved. Please, try again.'));
        }
        $taxRates = $this->Customers->TaxRates->find('list', ['order' => 'name']);

        $invoice_delivery_types = $this->Customers->invoice_delivery_types;

        $this->set(compact('customer', 'taxRates', 'invoice_delivery_types'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Customer id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $customer = $this->Customers->get($id);
        if ($this->Customers->delete($customer)) {
            $this->Flash->success(__('The customer has been deleted.'));
        } else {
            $this->Flash->error(__('The customer could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Print method
     *
     * @param string|null $id Contract id.
     * @param string|null $type Document type.
     * @return \Cake\Http\Response|null|void Renders print.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function print($id = null, $type = null)
    {
        $documentTypes = [
            'gdpr-new' => __('Consent to the processing of personal data'),
            'gdpr-change' => __('Consent to the processing of personal data (change)'),
        ];
        $this->set('documentTypes', $documentTypes);

        $customer = $this->Customers->get($id, [
            'contain' => [
                'TaxRates',
                'Addresses' => ['Countries'],
                'Emails',
                'Phones',
                'Creators',
                'Modifiers',
            ],
        ]);

        $invoice_delivery_types = $this->Customers->invoice_delivery_types;
        $address_types = $this->Customers->Addresses->types;

        $query = $this->getRequest()->getQuery();
        if (isset($query['document_type'])) {
            $type = $query['document_type'];
        }

        if ($this->getRequest()->getParam('_ext') === 'pdf') {
            switch ($type) {
                case 'gdpr-new':
                case 'gdpr-change':
                    break;

                default:
                    $this->Flash->error(__('Invalid type of document requested.'));

                    return $this->redirect(['action' => 'print', $id, '?' => $query]);
            }
        }
        $this->set(compact('customer', 'type', 'query', 'address_types', 'invoice_delivery_types'));
    }
}
