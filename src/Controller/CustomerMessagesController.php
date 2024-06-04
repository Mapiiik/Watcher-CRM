<?php
declare(strict_types=1);

namespace App\Controller;

use App\ApiClient;
use App\Model\Enum\CustomerMessageDeliveryStatus;
use App\Model\Enum\CustomerMessageDirection;
use App\Model\Enum\CustomerMessageType;
use Cake\Utility\Text;
use Cake\Validation\Validation;
use SplObjectStorage;

/**
 * CustomerMessages Controller
 *
 * @property \App\Model\Table\CustomerMessagesTable $CustomerMessages
 */
class CustomerMessagesController extends AppController
{
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
            $conditions = ['CustomerMessages.customer_id' => $this->customer_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'CustomerMessages.subject ILIKE' => '%' . trim($search) . '%',
                    'CustomerMessages.body ILIKE' => '%' . trim($search) . '%',
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
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
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
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
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
    }

    /**
     * Add Bulk method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function addBulk()
    {
        // load labels
        /** @var \App\Model\Table\LabelsTable $labelsTable */
        $labelsTable = $this->fetchTable('Labels');
        $labels = $labelsTable->find('list', order: [
            'name',
        ])->all();

        // load RUIAN addresses
        /** @var \Ruian\Model\Table\AddressesTable $ruianAddressesTable */
        $ruianAddressesTable = $this->fetchTable('Ruian.Addresses');
        $ruianAddresses = $ruianAddressesTable->find(
            'list',
            valueField: 'address',
            where: [
                'Addresses.kod_adm IN' =>
                    $this->CustomerMessages->Customers->Contracts->InstallationAddresses
                        ->find(
                            'list',
                            valueField: 'ruian_gid'
                        )
                        ->all()
                        ->toArray()
                ,
            ],
            order: [
                'obec_nazev',
                'cast_obce_nazev',
                'ulice_nazev',
                'typ_so',
                'cislo_domovni',
                'cislo_orientacni',
                'cislo_orientacni_znak',
            ]
        )->all();

        // customers filter
        $customersFilter = [];

        $labelId = $this->getRequest()->getQuery('label_id');
        if (Validation::uuid($labelId)) {
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
        if (Validation::uuid($accessPointId)) {
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

        $ruianAddressId = $this->getRequest()->getQuery('ruian_address_id');
        if (Validation::numeric($ruianAddressId)) {
            $filterQuery = $this->CustomerMessages->Customers->Contracts->find()
                ->select([
                    'customer_id',
                ])
                ->contain([
                    'InstallationAddresses',
                ])
                ->distinct()
                ->where([
                    'InstallationAddresses.ruian_gid IS' => $ruianAddressId,
                ]);

            $customersFilter[] = [
                'Customers.id IN' => $filterQuery,
            ];
            unset($filterQuery);
        }

        if (!empty($customersFilter)) {
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
                            '_auditQueue' => new SplObjectStorage(),
                            '_auditTransaction' => Text::uuid(),
                        ]
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
            'ruianAddresses',
            'customers',
        ));

        // load access points from NMS if possible
        $accessPoints = ApiClient::getAccessPoints();
        if ($accessPoints) {
            $this->set('accessPoints', $accessPoints->sortBy('name', SORT_ASC, SORT_NATURAL)->combine('id', 'name'));
        } else {
            $this->Flash->warning(__('The access points list could not be loaded. Please, try again.'));
            $this->set('accessPoints', []);
        }
    }

    /**
     * Edit method
     *
     * @param string|null $id Customer Message id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
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
    }

    /**
     * Delete method
     *
     * @param string|null $id Customer Message id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
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
