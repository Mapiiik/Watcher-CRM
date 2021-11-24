<?php
declare(strict_types=1);

namespace App\Controller;

use App\Form\SearchForm;

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
        $this->paginate = [
            'contain' => ['Taxes', 'Contracts', 'Ips' => ['Contracts']],
        ];

        $search = new SearchForm();
        if ($this->request->is(['get']) && ($this->request->getQuery('search')) !== null) {
            if ($search->execute(['search' => $this->request->getQuery('search')])) {
                $this->Flash->success(__('Search Set.'));
            } else {
                $this->Flash->error(__('There was a problem setting search.'));
            }
        }
        $this->set('search', $search);

        if (in_array($this->request->getSession()->read('Auth.role'), ['admin'])) {
            if ($search->getData('search') <> '') {
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
                    . ") @@ to_tsquery('" . mb_ereg_replace('\s{1,}', '&', \trim($search->getData('search'))) . "')";
                $filter = '('
                        . 'SELECT customers.id FROM customers '
                        . 'LEFT JOIN contracts ON (customers.id = contracts.customer_id) '
                        . 'LEFT JOIN emails ON (customers.id = emails.customer_id) '
                        . 'LEFT JOIN phones ON (customers.id = phones.customer_id) '
                        . 'LEFT JOIN addresses ON (customers.id = addresses.customer_id) '
                        . 'LEFT JOIN ips ON (customers.id = ips.customer_id) '
                        . 'WHERE ' . $filter . ' GROUP BY customers.id'
                    . ')';

                $this->paginate['conditions']['OR'] = [
                    'Customers.company ILIKE' => '%' . \trim($search->getData('search')) . '%',
                    'Customers.first_name ILIKE' => '%' . \trim($search->getData('search')) . '%',
                    'Customers.last_name ILIKE' => '%' . \trim($search->getData('search')) . '%',
                    'Customers.id IN ' . $filter,
                ];
                unset($filter);
            }
        } else {
            if (is_numeric($search->getData('search'))) {
                $this->paginate['conditions']['OR'] = [
                    'Customers.id' => (int)$search->getData('search'),
                    '(Customers.id + ' . (int)env('CUSTOMER_SERIES', '0') . ') =' => (int)$search->getData('search'),
                ];
            } else {
                $this->Flash->set(__('Please use the customer number in the search.'));
                $this->paginate['conditions'] = ['false'];
            }
        }

        $customers = $this->paginate($this->Customers);

        $invoice_delivery_types = $this->Customers->invoice_delivery_types;

        $this->set(compact('customers', 'invoice_delivery_types'));
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
                'Taxes',
                'Addresses' => ['Countries'],
                'Billings' => ['Contracts', 'Services'],
                'BorrowedEquipments' => ['Contracts', 'EquipmentTypes'],
                'Contracts' => ['ServiceTypes', 'InstallationAddresses'],
                'Emails',
                'Ips' => ['Contracts'],
                'LabelCustomers',
                'Logins',
                'Phones',
                'RemovedIps' => ['Contracts'],
                'SoldEquipments' => ['Contracts', 'EquipmentTypes'],
                'Tasks' => ['TaskTypes', 'TaskStates', 'Dealers']],
        ]);

        $invoice_delivery_types = $this->Customers->invoice_delivery_types;
        $address_types = $this->Customers->Addresses->types;
        $login_rights = $this->Customers->Logins->rights;

        $this->set(compact('customer', 'invoice_delivery_types', 'address_types', 'login_rights'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $customer = $this->Customers->newEmptyEntity();
        if ($this->request->is('post')) {
            $customer = $this->Customers->patchEntity($customer, $this->request->getData());
            if ($this->Customers->save($customer)) {
                $this->Flash->success(__('The customer has been saved.'));

                return $this->redirect(['action' => 'view', $customer->id]);
            }
            $this->Flash->error(__('The customer could not be saved. Please, try again.'));
        }
        $taxes = $this->Customers->Taxes->find('list', ['order' => 'name']);

        $invoice_delivery_types = $this->Customers->invoice_delivery_types;

        $this->set(compact('customer', 'taxes', 'invoice_delivery_types'));
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
        if ($this->request->is(['patch', 'post', 'put'])) {
            $customer = $this->Customers->patchEntity($customer, $this->request->getData());
            if ($this->Customers->save($customer)) {
                $this->Flash->success(__('The customer has been saved.'));

                return $this->redirect(['action' => 'view', $customer->id]);
            }
            $this->Flash->error(__('The customer could not be saved. Please, try again.'));
        }
        $taxes = $this->Customers->Taxes->find('list', ['order' => 'name']);

        $invoice_delivery_types = $this->Customers->invoice_delivery_types;

        $this->set(compact('customer', 'taxes', 'invoice_delivery_types'));
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
        $this->request->allowMethod(['post', 'delete']);
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
                'Taxes',
                'Addresses' => ['Countries'],
                'Billings' => ['Contracts', 'Services'],
                'BorrowedEquipments' => ['Contracts', 'EquipmentTypes'],
                'Contracts' => ['ServiceTypes', 'InstallationAddresses'],
                'Emails',
                'Ips' => ['Contracts'],
                'LabelCustomers',
                'Logins',
                'Phones',
                'RemovedIps' => ['Contracts'],
                'SoldEquipments' => ['Contracts', 'EquipmentTypes'],
                'Tasks' => ['TaskTypes', 'TaskStates', 'Dealers']],
        ]);

        $invoice_delivery_types = $this->Customers->invoice_delivery_types;
        $address_types = $this->Customers->Addresses->types;
        $login_rights = $this->Customers->Logins->rights;

        $query = $this->request->getQuery();
        if (isset($query['document_type'])) {
            $type = $query['document_type'];
        }

        if ($this->request->getParam('_ext') === 'pdf') {
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
