<?php
declare(strict_types=1);

namespace App\Controller;

use App\Phones\Formatter as PhoneFormatter;
use Cake\Http\Response;
use Cake\Utility\Text;
use SplObjectStorage;

/**
 * Phones Controller
 *
 * @property \App\Model\Table\PhonesTable $Phones
 */
class PhonesController extends AppController
{
    /**
     * How many of the numbers that could not be read are named in the message about them.
     *
     * @var int
     */
    private const REFUSED_SHOWN = 20;

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

        $refused = [];

        foreach ($phones as $phone) {
            $formatted = PhoneFormatter::toInternational($phone->phone);

            if ($formatted === null) {
                $refused[] = $phone->phone;
                continue;
            }

            // an entity assigned what it already holds stays clean, so an unchanged record is
            // not saved again
            $phone->phone = $formatted;
        }

        if ($refused !== []) {
            // one message however many there are - a flash apiece buries the outcome under them
            // and carries the whole lot in the session
            $this->Flash->error(__(
                'Phone numbers that could not be read were left as they are ({0}): {1}',
                count($refused),
                implode(', ', array_slice($refused, 0, self::REFUSED_SHOWN)),
            ));
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
