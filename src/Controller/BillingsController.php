<?php
declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\CommonViewVarListsTrait;
use App\Model\Entity\Billing;
use App\Model\Enum\CustomerMessageBodyFormat;
use App\Model\Enum\CustomerMessageDeliveryStatus;
use App\Model\Enum\CustomerMessageDirection;
use App\Model\Enum\CustomerMessageType;
use App\Model\Table\CustomerMessagesTable;
use App\Utility\ServiceChangeMessageBuilder;
use Cake\Http\Response;
use Cake\Utility\Text;
use Cake\Validation\Validation;
use Settings\Utility\Settings;
use SplObjectStorage;

/**
 * Billings Controller
 *
 * @property \App\Model\Table\BillingsTable $Billings
 */
class BillingsController extends AppController
{
    use CommonViewVarListsTrait;

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
            $conditions += ['Billings.customer_id' => $this->customer_id];
        }
        if ($this->contract_id !== null) {
            $conditions += ['Billings.contract_id' => $this->contract_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Billings.text ILIKE' => '%' . trim((string)$search) . '%',
                    'Services.name ILIKE' => '%' . trim((string)$search) . '%',
                    'Contracts.number ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $billings = $this->paginate($this->Billings->find(
            'all',
            contain: [
                'Contracts' => [
                    'ContractStates',
                ],
                'Customers',
                'Services',
            ],
            conditions: $conditions,
        ));

        $this->set(compact('billings'));
    }

    /**
     * View method
     *
     * @param string|null $id Billing id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $billing = $this->Billings->get($id, contain: [
            'Contracts' => ['ContractStates'],
            'Customers',
            'Services',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('billing'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $billing = $this->Billings->newEmptyEntity();

        if ($this->customer_id !== null) {
            $billing->customer_id = $this->customer_id;
        }
        if ($this->contract_id !== null) {
            $billing->contract_id = $this->contract_id;
        }

        if ($this->getRequest()->is('post')) {
            $billing = $this->Billings->patchEntity($billing, $this->getRequest()->getData());

            if ($this->getRequest()->getData('refresh') == 'refresh') {
                // only refresh
            } elseif (
                !isset($billing->service_id) && !isset($billing->text)
            ) {
                $this->Flash->error(__('Billing text must be entered or service selected.'));
            } elseif (
                isset($billing->contract_id) && isset($billing->service_id)
                && isset($this->Billings->Services->get($billing->service_id)->service_type_id)
                && (
                    $this->Billings->Contracts->get($billing->contract_id)->service_type_id
                    != $this->Billings->Services->get($billing->service_id)->service_type_id
                )
            ) {
                $this->Flash->error(__('The service type does not match the selected contract.'));
            } else {
                if ($this->Billings->save($billing)) {
                    $this->Flash->success(__('The billing has been saved.'));

                    return $this->afterAddRedirect(['action' => 'view', $billing->id]);
                }
                $this->Flash->error(__('The billing could not be saved. Please, try again.'));
            }
        }
        $customers = $this->Billings->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        $contracts = $this->Billings->Contracts->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );
        $services = $this->Billings->Services->find('list', order: [
            'name',
        ]);

        if ($this->customer_id !== null) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if ($this->contract_id !== null) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
            $services->where(['OR' => [
                'service_type_id' => $this->Billings->Contracts->get($this->contract_id)->service_type_id,
                'service_type_id IS NULL',
            ]]);
        } elseif (isset($billing->contract_id)) {
            $services->where(['OR' => [
                'service_type_id' => $this->Billings->Contracts->get($billing->contract_id)->service_type_id,
                'service_type_id IS NULL',
            ]]);
        }

        // only services available for new customers
        $services->andWhere(['Services.not_for_new_customers' => false]);

        $this->set(compact('billing', 'customers', 'services', 'contracts'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Billing id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $billing = $this->Billings->get($id, contain: []);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $billing = $this->Billings->patchEntity($billing, $this->getRequest()->getData());

            if ($this->getRequest()->getData('refresh') == 'refresh') {
                // only refresh
            } elseif (
                !isset($billing->service_id) && !isset($billing->text)
            ) {
                $this->Flash->error(__('Billing text must be entered or service selected.'));
            } elseif (
                isset($billing->contract_id) && isset($billing->service_id)
                && isset($this->Billings->Services->get($billing->service_id)->service_type_id)
                && (
                    $this->Billings->Contracts->get($billing->contract_id)->service_type_id
                    != $this->Billings->Services->get($billing->service_id)->service_type_id
                )
            ) {
                $this->Flash->error(__('The service type does not match the selected contract.'));
            } else {
                if ($this->Billings->save($billing)) {
                    $this->Flash->success(__('The billing has been saved.'));

                    return $this->afterEditRedirect(['action' => 'view', $billing->id]);
                }
                $this->Flash->error(__('The billing could not be saved. Please, try again.'));
            }
        }
        $customers = $this->Billings->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);
        $contracts = $this->Billings->Contracts->find(
            'list',
            contain: [
                'InstallationAddresses',
                'ServiceTypes',
            ],
            order: [
                'Contracts.number',
            ],
        );
        $services = $this->Billings->Services->find('list', order: [
            'name',
        ]);

        if ($this->customer_id !== null) {
            $customers->where(['Customers.id' => $this->customer_id]);
            $contracts->where(['Contracts.customer_id' => $this->customer_id]);
        }
        if ($this->contract_id !== null) {
            $contracts->where(['Contracts.id' => $this->contract_id]);
            $services->where(['OR' => [
                'service_type_id' => $this->Billings->Contracts->get($this->contract_id)->service_type_id,
                'service_type_id IS NULL',
            ]]);
        } elseif (isset($billing->contract_id)) {
            $services->where(['OR' => [
                'service_type_id' => $this->Billings->Contracts->get($billing->contract_id)->service_type_id,
                'service_type_id IS NULL',
            ]]);
        }

        $this->set(compact('billing', 'customers', 'services', 'contracts'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Billing id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $billing = $this->Billings->get($id);
        if ($this->Billings->delete($billing)) {
            $this->Flash->success(__('The billing has been deleted.'));
        } else {
            $this->flashValidationErrors($billing->getErrors());
            $this->Flash->error(__('The billing could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }

    /**
     * Service Change method
     *
     * @param string|null $id Billing id.
     * @return \Cake\Http\Response|null Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function serviceChange(?string $id = null): ?Response
    {
        $billing = $this->Billings->get($id, contain: [
            'Contracts' => [
                'ContractStates',
                'InstallationAddresses',
            ],
            'Customers' => [
                'Emails',
            ],
            'Services',
            'Creators',
            'Modifiers',
        ]);

        // process change request
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $new_billing = $this->processServiceChange($billing);

            if ($new_billing === false) {
                return $this->redirect([]);
            }

            $this->Flash->success(__('The billing has been saved.'));

            return $this->redirect(['action' => 'view', $new_billing->id]);
        }

        // get data
        $services = $this->Billings->Services
            ->find('list')
            ->orderBy(['name'])
            ->where(['OR' => [
                'service_type_id' => $billing->contract->service_type_id,
                'service_type_id IS NULL',
            ]])
            ->all();

        // set data
        $this->set(compact('billing', 'services'));

        return null;
    }

    /**
     * Bulk Service Change method
     *
     * @return \Cake\Http\Response|null Renders view
     */
    public function bulkServiceChange(): ?Response
    {
        // query
        $billingsQuery = $this->Billings->find(
            'all',
            contain: [
                'Contracts' => [
                    'ContractStates',
                    'InstallationAddresses',
                ],
                'Customers' => [
                    'Emails',
                ],
                'Services',
            ],
        );

        // filter
        $original_service_id = $this->getRequest()->getQuery('original_service_id');
        if (is_string($original_service_id) && Validation::uuid($original_service_id)) {
            $billingsQuery->where(['Billings.service_id' => $original_service_id]);
        } else {
            $billingsQuery->where(['FALSE']);
        }

        $active_on_date = $this->getRequest()->getQuery('active_on_date');
        if (is_string($active_on_date) && Validation::date($active_on_date)) {
            $billingsQuery->where([
                'Billings.billing_from <=' => $active_on_date,
                'OR' => [
                    'Billings.billing_until IS NULL',
                    'Billings.billing_until >=' => $active_on_date,
                ],
            ]);
        } else {
            $billingsQuery->where(['FALSE']);
        }

        $standard_prices_only = $this->getRequest()->getQuery('standard_prices_only');
        if ($standard_prices_only !== '0') {
            $billingsQuery->where([
                'Billings.price IS NULL',
                'Billings.fixed_discount IS NULL',
                'Billings.percentage_discount IS NULL',
            ]);
        } else {
            $empty_is_null = $this->getRequest()->getQuery('empty_is_null') !== '0';
            $price = $this->getRequest()->getQuery('price');
            if (is_numeric($price)) {
                $billingsQuery->where([
                    'Billings.price' => (float)$price,
                ]);
            } elseif ($empty_is_null) {
                $billingsQuery->where([
                    'Billings.price IS NULL',
                ]);
            }
            $fixed_discount = $this->getRequest()->getQuery('fixed_discount');
            if (is_numeric($fixed_discount)) {
                $billingsQuery->where([
                    'Billings.fixed_discount' => (float)$fixed_discount,
                ]);
            } elseif ($empty_is_null) {
                $billingsQuery->where([
                    'Billings.fixed_discount IS NULL',
                ]);
            }
            $percentage_discount = $this->getRequest()->getQuery('percentage_discount');
            if (is_numeric($percentage_discount)) {
                $billingsQuery->where([
                    'Billings.percentage_discount' => (float)$percentage_discount,
                ]);
            } elseif ($empty_is_null) {
                $billingsQuery->where([
                    'Billings.percentage_discount IS NULL',
                ]);
            }
        }

        $processing_limit = $this->getRequest()->getQuery('processing_limit');
        if (is_numeric($processing_limit)) {
            $billingsQuery->limit((int)$processing_limit);
        }

        $access_point_id = $this->getRequest()->getQuery('access_point_id');
        if (is_string($access_point_id) && Validation::uuid($access_point_id)) {
            $billingsQuery->where(['Contracts.access_point_id' => $access_point_id]);
        }

        // get data
        $billings = $billingsQuery->all();
        $services = $this->Billings->Services->find('list', order: ['name'])->all();

        // load access points from NMS if possible (only active)
        $this->setAccessPointsViewVarList(onlyActive: true);

        // process change request
        if ($this->getRequest()->is('post')) {
            foreach ($billings as $original_billing) {
                /** @var \App\Model\Entity\Billing $original_billing */
                if ($this->processServiceChange($original_billing) === false) {
                    return $this->redirect([]);
                }
            }

            $this->Flash->success(__('The billing has been saved.'));

            return $this->redirect([]);
        }

        // set data
        $this->set(compact('billings', 'services'));

        return null;
    }

    /**
     * Process Service Change method
     *
     * @param \App\Model\Entity\Billing $originalBilling Original billing entity
     * @return \App\Model\Entity\Billing|false New billing entity
     */
    private function processServiceChange(Billing $originalBilling): Billing|false
    {
        $request = $this->getRequest();

        // Determine if customer notification should be sent
        $sendCustomerNotification =
            ($request->getData('send_customer_notification') === '1');

        // Determine if version without legislative information should be used for customer message
        $versionWithoutLegislativeInformation =
            ($request->getData('version_without_legislative_information') === '1');

        // Prepare new billing entity
        $originalBillingData = $originalBilling->toArray();
        unset(
            $originalBillingData['id'],
            $originalBillingData['created'],
            $originalBillingData['created_by'],
            $originalBillingData['modified'],
            $originalBillingData['modified_by'],
            $originalBillingData['contract'],
            $originalBillingData['customer'],
            $originalBillingData['service'],
        );

        $newBilling = $this->Billings->newEntity($originalBillingData);
        $newBilling = $this->Billings->patchEntity($newBilling, $request->getData());
        $newBilling->service = $this->Billings->Services->get($newBilling->service_id); // load associated service

        // Update original billing
        $originalBilling = $this->Billings->patchEntity($originalBilling, [
            'billing_until' => $newBilling->billing_from->subDays(1),
        ]);

        // Persist both entities
        if (
            $this->Billings->saveMany(
                [
                    $originalBilling,
                    $newBilling,
                ],
                [
                    // saveMany audit options kept intentionally:
                    // - mapiiik/audit-log (5.x, 6.x) logs nothing without them
                    // - even audit-stash 2.0.1+ groups the batch under one transaction id only
                    //   when they're passed (otherwise each record gets its own)
                    '_auditQueue' => new SplObjectStorage(),
                    '_auditTransaction' => Text::uuid(),
                ],
            ) === false
        ) {
            $this->Flash->error(
                __('The billing could not be saved. Please, try again.')
                . ' (' . __('Contract Number') . ': ' . $originalBilling->contract->number . ')',
            );

            return false;
        }

        // Create customer message (email) if requested
        if ($sendCustomerNotification) {
            /** @var \App\Model\Table\CustomerMessagesTable $customerMessagesTable */
            $customerMessagesTable = $this->fetchTable(CustomerMessagesTable::class);

            $body = ServiceChangeMessageBuilder::buildEmailBody(
                // Customer and contract details
                customerName: $originalBilling->customer->name,
                contractNumber: $originalBilling->contract->number ?? '',
                installationAddress: $originalBilling->contract->installation_address->address ?? null,
                // Original billing details
                originalBillingName: $originalBilling->name,
                originalBillingSum: $originalBilling->sum->toFloat(),
                originalBillingTotalPrice: $originalBilling->total_price->toFloat(),
                // New billing details
                newBillingName: $newBilling->name,
                newBillingSum: $newBilling->sum->toFloat(),
                newBillingTotalPrice: $newBilling->total_price->toFloat(),
                // Other details
                newBillingFrom: (string)$newBilling->billing_from,
                versionWithoutLegislativeInformation: $versionWithoutLegislativeInformation,
            );

            $subject = strtr(
                Settings::getString('core.emails.service_change.subject'),
                [
                    '{new_billing_from}' => (string)$newBilling->billing_from,
                    '{contract_number}' => $originalBilling->contract->number ?? '',
                ],
            );

            // Create customer message entity
            $customerMessage = $customerMessagesTable->newEmptyEntity();

            $customerMessage->type = CustomerMessageType::EmailContracts;
            $customerMessage->direction = CustomerMessageDirection::Outgoing;
            $customerMessage->body_format = CustomerMessageBodyFormat::Plaintext;
            $customerMessage->delivery_status = CustomerMessageDeliveryStatus::Pending;

            $customerMessage->customer_id = $originalBilling->customer_id;
            $customerMessage->recipients = array_column($originalBilling->customer->billing_emails, 'email');
            $customerMessage->subject = $subject;
            $customerMessage->body = $body;

            // Persist customer message entity
            $customerMessagesTable->saveOrFail($customerMessage);
        }

        return $newBilling;
    }
}
