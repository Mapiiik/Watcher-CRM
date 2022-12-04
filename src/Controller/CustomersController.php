<?php
declare(strict_types=1);

namespace App\Controller;

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
        // search
        $search = $this->getRequest()->getQuery('search');
        $advanced_search = $this->getRequest()->getQuery('advanced_search');
        $allow_advanced_search = in_array($this->getRequest()->getAttribute('identity')['role'] ?? null, [
            'admin',
            'sales-manager',
        ]);

        $customersQuery = $this->Customers->find();

        if ($allow_advanced_search && $advanced_search && !empty($search)) {
            // advanced search
            $filter = 'to_tsvector('
                    . "Customers.id || ' ' || "
                    . 'Customers.id + ' . (int)env('CUSTOMER_SERIES', '0') . " || ' ' || "
                    . "COALESCE(Contracts.id::text, '') || ' ' || "
                    . "COALESCE(Contracts.number, '') || ' ' || "
                    . "COALESCE(Customers.first_name, '') || ' ' || "
                    . "COALESCE(Customers.last_name, '') || ' ' || "
                    . "COALESCE(Customers.company, '')  || "
                    . "COALESCE(Addresses.first_name, '') || ' ' || "
                    . "COALESCE(Addresses.last_name, '') || ' ' || "
                    . "COALESCE(Addresses.company, '') || ' ' || "
                    . "COALESCE(Addresses.street, '') || ' ' || "
                    . "COALESCE(Addresses.number, '') || ' ' || "
                    . "COALESCE(Addresses.city, '') || ' ' || "
                    . "COALESCE(Addresses.zip, '') || ' ' || "
                    . "COALESCE(Emails.email, '') || ' ' || "
                    . "COALESCE(Phones.phone, '') || ' ' || "
                    . "COALESCE(Customers.ic, '') || ' ' || "
                    . "COALESCE(Customers.dic, '') || ' ' || "
                    . "COALESCE(Ips.ip, '0.0.0.0'::inet)"
                . ') @@ plainto_tsquery(:search)';
            $filter = '('
                    . 'SELECT customers.id FROM customers '
                    . 'LEFT JOIN contracts ON (customers.id = contracts.customer_id) '
                    . 'LEFT JOIN emails ON (customers.id = emails.customer_id) '
                    . 'LEFT JOIN phones ON (customers.id = phones.customer_id) '
                    . 'LEFT JOIN addresses ON (customers.id = addresses.customer_id) '
                    . 'LEFT JOIN ips ON (customers.id = ips.customer_id) '
                    . 'WHERE ' . $filter . ' GROUP BY customers.id'
                . ')';

            $customersQuery->where([
                'OR' => [
                    'Customers.company ILIKE' => '%' . trim($search) . '%',
                    'Customers.first_name ILIKE' => '%' . trim($search) . '%',
                    'Customers.last_name ILIKE' => '%' . trim($search) . '%',
                    'Customers.id IN ' . $filter,
                ],
            ]);
            $customersQuery->bind(':search', trim($search), 'string');

            unset($filter);
        } elseif (is_numeric($search)) {
            // search by customer number
            $customersQuery->where([
                'OR' => [
                    'Customers.id' => (int)trim($search),
                    '(Customers.id + ' . (int)env('CUSTOMER_SERIES', '0') . ') =' => (int)trim($search),
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

        $this->paginate = [
            'contain' => ['TaxRates', 'Contracts', 'Ips' => ['Contracts']],
            'order' => [
                'Customers.id' => 'DESC',
            ],
        ];

        $customers = $this->paginate($customersQuery);

        $invoice_delivery_types = $this->Customers->invoice_delivery_types;

        $this->set(compact('customers', 'invoice_delivery_types', 'allow_advanced_search'));
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
