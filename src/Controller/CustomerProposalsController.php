<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Enum\CustomerProposalPurpose;
use App\Model\Enum\DocumentsDeliveryType;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\I18n\DateTime;

/**
 * CustomerProposals Controller
 *
 * A round of papers put to the customer themselves rather than to any one contract. It travels the
 * road a contract's proposal does - drawn up, sent, signed - and stops there, because nothing
 * stands behind it waiting to be written into the live records.
 *
 * @property \App\Model\Table\CustomerProposalsTable $CustomerProposals
 */
class CustomerProposalsController extends AppController
{
    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        $conditions = [];
        if ($this->customer_id !== null) {
            $conditions += ['CustomerProposals.customer_id' => $this->customer_id];
        }

        $request = $this->getRequest();
        $show_settled = toBool($request->getQuery('show_settled')) ?? false;

        $search = $request->getQuery('search');
        if (!empty($search)) {
            $conditions[] = ['CustomerProposals.note ILIKE' => '%' . trim((string)$search) . '%'];
        }

        $query = $this->CustomerProposals
            ->find($show_settled ? 'all' : 'open')
            ->contain(['Customers'])
            ->where($conditions)
            ->orderBy(['CustomerProposals.effective_from' => 'DESC']);

        $customerProposals = $this->paginate($query);

        $this->set(compact('customerProposals', 'show_settled'));
    }

    /**
     * View method
     *
     * @param string|null $id Customer proposal id.
     * @return void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $customerProposal = $this->CustomerProposals->get($id, contain: [
            'Customers',
            'Creators',
            'Modifiers',
        ]);

        $this->set(compact('customerProposal'));
        $this->set('mayBeEdited', $this->CustomerProposals->mayBeEdited($customerProposal));
        $this->set('mayBeDeleted', $this->CustomerProposals->mayBeDeleted($customerProposal));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $proposal = $this->CustomerProposals->newEmptyEntity();

        if ($this->customer_id !== null) {
            $proposal->set('customer_id', $this->customer_id);
        }

        // A paper asked for out of the blue speaks about today. Where it goes out with something
        // else - a contract starting, say - whoever draws it up says which day that is.
        $proposal->set('effective_from', Date::now());

        if ($this->request->is('post')) {
            $proposal = $this->CustomerProposals->patchEntity($proposal, $this->request->getData());

            if ($this->CustomerProposals->save($proposal)) {
                $this->Flash->success(__('The proposal has been saved.'));

                return $this->afterAddRedirect(['action' => 'view', $proposal->id]);
            }

            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The proposal could not be saved. Please, try again.'));
        }

        $this->set('customerProposal', $proposal);
        $this->setFormViewVars();

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Customer proposal id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $proposal = $this->CustomerProposals->get($id);

        if (!$this->CustomerProposals->mayBeEdited($proposal)) {
            $this->Flash->warning(__('This proposal may no longer be changed.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $proposal = $this->CustomerProposals->patchEntity($proposal, $this->request->getData());

            if ($this->CustomerProposals->save($proposal)) {
                $this->Flash->success(__('The proposal has been saved.'));

                return $this->redirect(['action' => 'view', $proposal->id]);
            }

            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The proposal could not be saved. Please, try again.'));
        }

        $this->set('customerProposal', $proposal);
        $this->setFormViewVars();

        return null;
    }

    /**
     * Records that the papers went out, and how.
     *
     * @param string|null $id Customer proposal id.
     * @return \Cake\Http\Response|null Redirects when recorded, renders the form otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function send(?string $id = null): ?Response
    {
        $proposal = $this->CustomerProposals->get($id, contain: ['Customers']);

        if (!$proposal->isOpen()) {
            $this->Flash->warning(__('This proposal has already been settled.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $proposal = $this->CustomerProposals->patchEntity($proposal, [
                'sent_date' => $this->request->getData('sent_date'),
                'delivery_type' => $this->request->getData('delivery_type'),
            ]);

            if ($this->CustomerProposals->save($proposal)) {
                $this->Flash->success(__('The proposal has been recorded as sent.'));

                return $this->redirect(['action' => 'view', $proposal->id]);
            }

            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The sending could not be recorded. Please, try again.'));
        }

        $this->set('customerProposal', $proposal);
        $this->set('deliveryTypes', DocumentsDeliveryType::options());

        return null;
    }

    /**
     * Records the day the customer signed.
     *
     * This is where a round ends: nothing stands behind it waiting to be carried over.
     *
     * @param string|null $id Customer proposal id.
     * @return \Cake\Http\Response|null Redirects when recorded, renders the form otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function conclude(?string $id = null): ?Response
    {
        $proposal = $this->CustomerProposals->get($id, contain: ['Customers']);

        if (!$proposal->isOpen()) {
            $this->Flash->warning(__('This proposal has already been settled.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $proposal = $this->CustomerProposals->patchEntity($proposal, [
                'conclusion_date' => $this->request->getData('conclusion_date'),
            ]);

            if ($this->CustomerProposals->save($proposal)) {
                $this->Flash->success(__('The signature has been recorded.'));

                return $this->redirect(['action' => 'view', $proposal->id]);
            }

            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The signature could not be recorded. Please, try again.'));
        }

        $this->set('customerProposal', $proposal);

        return null;
    }

    /**
     * Gives up on the papers.
     *
     * @param string|null $id Customer proposal id.
     * @return \Cake\Http\Response|null Redirects back to the proposal.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function revoke(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $proposal = $this->CustomerProposals->get($id);

        if (!$proposal->isOpen()) {
            $this->Flash->warning(__('This proposal has already been settled.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        $proposal->revoked = DateTime::now();
        $proposal->revoked_by = $this->getRequest()->getAttribute('identity')['id'] ?? null;

        if ($this->CustomerProposals->save($proposal, ['checkRules' => false])) {
            $this->Flash->success(__('The proposal has been revoked.'));
        } else {
            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The proposal could not be revoked. Please, try again.'));
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Delete method
     *
     * @param string|null $id Customer proposal id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);

        $proposal = $this->CustomerProposals->get($id);

        if (!$this->CustomerProposals->mayBeDeleted($proposal)) {
            $this->Flash->warning(__('This proposal may no longer be removed.'));

            return $this->redirect(['action' => 'view', $id]);
        }

        if ($this->CustomerProposals->delete($proposal)) {
            $this->Flash->success(__('The proposal has been deleted.'));
        } else {
            $this->flashValidationErrors($proposal->getErrors());
            $this->Flash->error(__('The proposal could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }

    /**
     * What the form needs to draw itself.
     *
     * @return void
     */
    private function setFormViewVars(): void
    {
        $this->set('purposes', CustomerProposalPurpose::options());
        $this->set('customers', $this->CustomerProposals->Customers->find('list'));
    }
}
