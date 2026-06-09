<?php
declare(strict_types=1);

namespace App\Controller;

use App\Addresses\Resolver as AddressesResolver;
use App\Controller\Traits\CommonViewVarListsTrait;
use App\Model\Enum\CustomerMessageDeliveryStatus;
use App\Model\Enum\CustomerMessageDirection;
use App\Model\Enum\CustomerMessageType;
use App\Model\Table\LabelsTable;
use Cake\Http\Response;
use Cake\Utility\Text;
use Cake\Validation\Validation;
use RuntimeException;
use SplObjectStorage;

/**
 * CustomerMessages Controller
 *
 * @property \App\Model\Table\CustomerMessagesTable $CustomerMessages
 */
class CustomerMessagesController extends AppController
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
            $conditions = ['CustomerMessages.customer_id' => $this->customer_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'CustomerMessages.subject ILIKE' => '%' . trim((string)$search) . '%',
                    'CustomerMessages.body ILIKE' => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $query = $this->CustomerMessages->find()
            ->contain([
                'Customers',
            ])
            ->where($conditions);

        $customerMessages = $this->paginate($query, [
            'order' => [
                'CustomerMessages.created' => 'DESC',
            ],
        ]);

        $this->set(compact('customerMessages'));
    }

    /**
     * View method
     *
     * @param string|null $id Customer Message id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $customerMessage = $this->CustomerMessages->get($id, contain: [
            'Customers',
            'Creators',
            'Modifiers',
        ]);
        $this->set(compact('customerMessage'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $customerMessage = $this->CustomerMessages->newEmptyEntity();
        if ($this->request->is('post')) {
            $customerMessage = $this->CustomerMessages->patchEntity($customerMessage, $this->request->getData());
            if ($this->CustomerMessages->save($customerMessage)) {
                $this->Flash->success(__('The customer message has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $customerMessage->id]);
            }
            $this->Flash->error(__('The customer message could not be saved. Please, try again.'));
        }
        $customers = $this->CustomerMessages->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ])->all();
        $this->set(compact('customerMessage', 'customers'));

        return null;
    }

    /**
     * Add Bulk method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function addBulk(): ?Response
    {
        // load labels
        $labelsTable = $this->fetchTable(LabelsTable::class);
        $labels = $labelsTable->find('list', order: [
            'name',
        ])->all();

        // Load addresses from national address registry for existing installation addresses
        /** @var \Cake\Datasource\ResultSetInterface<int, \App\Model\Entity\Address> $installationAddresses */
        $installationAddresses = $this->CustomerMessages->Customers->Contracts->InstallationAddresses
            ->find()
            ->where([
                'address_registry_source IS NOT' => null,
                'address_registry_reference IS NOT' => null,
            ])
            ->all();

        $registryAddresses = [];
        try {
            $registryAddresses = AddressesResolver::dropdownMap($installationAddresses);
        } catch (RuntimeException $e) {
            $this->Flash->warning(__(
                'Could not retrieve addresses from national address registry: {0}',
                $e->getMessage(),
            ));
        }

        // customers filter
        $customersFilter = [];

        $labelId = $this->getRequest()->getQuery('label_id');
        if (is_string($labelId) && Validation::uuid($labelId)) {
            $filterQuery = $labelsTable->CustomerLabels->find()
                ->select([
                    'customer_id',
                ])
                ->distinct()
                ->where([
                    'CustomerLabels.label_id IS' => $labelId,
                ]);

            $customersFilter[] = [
                'Customers.id IN' => $filterQuery,
            ];
            unset($filterQuery);
        }

        $accessPointId = $this->getRequest()->getQuery('access_point_id');
        if (is_string($accessPointId) && Validation::uuid($accessPointId)) {
            $filterQuery = $this->CustomerMessages->Customers->Contracts->find()
                ->select([
                    'customer_id',
                ])
                ->distinct()
                ->where([
                    'Contracts.access_point_id IS' => $accessPointId,
                ]);

            $customersFilter[] = [
                'Customers.id IN' => $filterQuery,
            ];
            unset($filterQuery);
        }

        $registryAddressId = $this->getRequest()->getQuery('registry_address_id');
        if (is_string($registryAddressId)) {
            // expect format "source|reference", e.g. "cz|12345678"
            [
                $address_registry_source,
                $address_registry_reference,
            ] = explode('|', $registryAddressId, limit: 2) + [null, null];

            $filterQuery = $this->CustomerMessages->Customers->Contracts->find()
                ->select([
                    'customer_id',
                ])
                ->contain([
                    'InstallationAddresses',
                ])
                ->distinct()
                ->where([
                    'InstallationAddresses.address_registry_reference IS' => $address_registry_reference,
                    'InstallationAddresses.address_registry_source IS' => $address_registry_source,
                ]);

            $customersFilter[] = [
                'Customers.id IN' => $filterQuery,
            ];
            unset($filterQuery);
        }

        if ($customersFilter !== []) {
            $customers = $this->CustomerMessages->Customers->find()
            ->contain([
                'Emails',
                'Phones',
            ])
            ->where($customersFilter)
            ->orderBy([
                'Customers.company',
                'Customers.last_name',
                'Customers.first_name',
            ]);
        } else {
            $customers = [];
        }
        /** @var iterable<\App\Model\Entity\Customer> $customers */

        $customerMessage = $this->CustomerMessages->newEmptyEntity();
        if ($this->request->is('post')) {
            if (empty($customers)) {
                $this->Flash->error(__('No customers were selected.'));
            } else {
                $customerMessage = $this->CustomerMessages->patchEntity($customerMessage, $this->request->getData());

                $customerMessage->direction = CustomerMessageDirection::Outgoing;
                $customerMessage->delivery_status = CustomerMessageDeliveryStatus::Pending;

                $customerMessages = [];
                foreach ($customers as $customer) {
                    $thisMessage = clone $customerMessage;
                    $thisMessage->customer_id = $customer->id;
                    $thisMessage->recipients = match ($thisMessage->type) {
                        CustomerMessageType::Sms => $customer->phones,
                        CustomerMessageType::Email,
                        CustomerMessageType::EmailContracts,
                        CustomerMessageType::EmailInvoices,
                        CustomerMessageType::EmailSupport => $customer->emails,
                    };

                    // skip messages without recipients
                    if (empty($thisMessage->recipients)) {
                        $this->Flash->warning(__('No contact was found for customer number {number}.', [
                            'number' => $customer->number,
                        ]));

                        continue;
                    }

                    $customerMessages[] = $thisMessage;
                    unset($thisMessage);
                }

                if (
                    $this->CustomerMessages->saveMany(
                        $customerMessages,
                        [
                            // saveMany audit options kept intentionally:
                            // - mapiiik/audit-log (5.x, 6.x) logs nothing without them
                            // - even audit-stash 2.0.1+ groups the batch under one transaction id only
                            //   when they're passed (otherwise each record gets its own)
                            '_auditQueue' => new SplObjectStorage(),
                            '_auditTransaction' => Text::uuid(),
                        ],
                    )
                ) {
                    $this->Flash->success(__('The bulk customer message has been saved.'));

                    return $this->afterAddRedirect(['action' => 'index']);
                }
                $this->Flash->error(__('The bulk customer message could not be saved. Please, try again.'));
            }
        }
        $this->set(compact(
            'customerMessage',
            'labels',
            'registryAddresses',
            'customers',
        ));

        // load access points from NMS if possible (only active)
        $this->setAccessPointsViewVarList(onlyActive: true);

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Customer Message id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $customerMessage = $this->CustomerMessages->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $customerMessage = $this->CustomerMessages->patchEntity($customerMessage, $this->request->getData());
            if ($this->CustomerMessages->save($customerMessage)) {
                $this->Flash->success(__('The customer message has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $customerMessage->id]);
            }
            $this->Flash->error(__('The customer message could not be saved. Please, try again.'));
        }
        $customers = $this->CustomerMessages->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ])->all();
        $this->set(compact('customerMessage', 'customers'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Customer Message id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $customerMessage = $this->CustomerMessages->get($id);
        if ($this->CustomerMessages->delete($customerMessage)) {
            $this->Flash->success(__('The customer message has been deleted.'));
        } else {
            $this->flashValidationErrors($customerMessage->getErrors());
            $this->Flash->error(__('The customer message could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
