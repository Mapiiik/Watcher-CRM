<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Utility\Text;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use SplObjectStorage;

/**
 * Phones Controller
 *
 * @property \App\Model\Table\PhonesTable $Phones
 */
class PhonesController extends AppController
{
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
            $conditions = ['Phones.customer_id' => $this->customer_id];
        }

        // search
        $search = $this->getRequest()->getQuery('search');
        if (!empty($search)) {
            $conditions[] = [
                'OR' => [
                    'Phones.phone ILIKE' => '%' . trim((string)$search) . '%',
                    "REPLACE(Phones.phone, ' ', '') ILIKE" => '%' . trim((string)$search) . '%',
                ],
            ];
        }

        $this->paginate = [
            'order' => [
                'id' => 'DESC',
            ],
        ];
        $phones = $this->paginate($this->Phones->find(
            'all',
            contain: [
                'Customers',
            ],
            conditions: $conditions,
        ));

        $this->set(compact('phones'));
    }

    /**
     * View method
     *
     * @param string|null $id Phone id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $phone = $this->Phones->get($id, contain: [
            'Customers',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('phone'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $phone = $this->Phones->newEmptyEntity();

        if ($this->customer_id !== null) {
            $phone->customer_id = $this->customer_id;
        }

        if ($this->getRequest()->is('post')) {
            $phone = $this->Phones->patchEntity(
                $phone,
                $this->dataWithAdditionalParameters($this->Phones, $this->getRequest()->getData()),
            );
            if ($this->Phones->save($phone)) {
                $this->Flash->success(__('The phone has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $phone->id]);
            }
            $this->Flash->error(__('The phone could not be saved. Please, try again.'));
        }
        $customers = $this->Phones->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);

        if ($this->customer_id !== null) {
            $customers->where(['id' => $this->customer_id]);
        }

        $this->set(compact('phone', 'customers'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Phone id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $phone = $this->Phones->get($id, contain: []);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $phone = $this->Phones->patchEntity($phone, $this->getRequest()->getData());
            if ($this->Phones->save($phone)) {
                $this->Flash->success(__('The phone has been saved.'));

                return $this->afterEditRedirect(['action' => 'view', $phone->id]);
            }
            $this->Flash->error(__('The phone could not be saved. Please, try again.'));
        }
        $customers = $this->Phones->Customers->find('list', order: [
            'company',
            'last_name',
            'first_name',
        ]);

        if ($this->customer_id !== null) {
            $customers->where(['Customers.id' => $this->customer_id]);
        }

        $this->set(compact('phone', 'customers'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Phone id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $phone = $this->Phones->get($id);
        if ($this->Phones->delete($phone)) {
            $this->Flash->success(__('The phone has been deleted.'));
        } else {
            $this->flashValidationErrors($phone->getErrors());
            $this->Flash->error(__('The phone could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }

    /**
     * Format all method
     *
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Http\Exception\MethodNotAllowedException When badly called.
     */
    public function formatAll(): ?Response
    {
        $this->getRequest()->allowMethod(['post']);

        /** @var \Cake\Datasource\ResultSetInterface<array-key, \App\Model\Entity\Phone> $phones */
        $phones = $this->Phones->find()->all();

        $phoneUtil = PhoneNumberUtil::getInstance();

        $phoneRegion = Configure::read('Phones.defaultRegion');

        // check the formatting of the phone number and update it if necessary
        foreach ($phones as $phone) {
            try {
                $phoneNumber = $phoneUtil->parse($phone->phone, $phoneRegion);

                if ($phoneUtil->isValidNumber($phoneNumber)) {
                    // The phone number is fine, formatting...
                    $phoneString = $phoneUtil->format($phoneNumber, PhoneNumberFormat::INTERNATIONAL);

                    // If the phone number format is different, update the record
                    if ($phone->phone !== $phoneString) {
                        $phone->phone = $phoneString;
                    }
                } else {
                    $this->Flash->error(
                        __('The phone number is invalid: {0}', $phone->phone),
                    );
                }
            } catch (NumberParseException) {
                $this->Flash->error(
                    __('The phone number is invalid: {0}', $phone->phone),
                );
            }
        }

        // save all changes
        if (
            $this->Phones->saveMany(
                $phones,
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
                __('The phones could not be updated. Please, try again.'),
            );
        } else {
            $this->Flash->success(
                __('The phones have been updated.'),
            );
        }

        return $this->redirect(['action' => 'index']);
    }
}
