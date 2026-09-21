<?php
declare(strict_types=1);

namespace WorkReports\Controller\Trait;

use Cake\Http\Response;
use Cake\ORM\Table;

/**
 * Listing, adding, editing and deleting the rows of a table the reports only look things up in.
 *
 * The controller names the table and what to call a row, and sets the lists its form needs in
 * `setFormLists()`.
 *
 * @psalm-require-extends \WorkReports\Controller\AppController
 */
trait LookupControllerTrait
{
    /**
     * The table looked things up in.
     *
     * @return \Cake\ORM\Table
     */
    abstract protected function lookupTable(): Table;

    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        $this->set('records', $this->paginate($this->lookupTable()->find(
            'all',
            contain: $this->indexContain(),
        )));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $record = $this->lookupTable()->newEmptyEntity();
        if ($this->getRequest()->is('post')) {
            $record = $this->lookupTable()->patchEntity($record, $this->getRequest()->getData());
            if ($this->lookupTable()->save($record)) {
                $this->Flash->success(__d('work_reports', 'The record has been saved.'));

                return $this->afterAddRedirect(['action' => 'index']);
            }
            $this->Flash->error(__d('work_reports', 'The record could not be saved. Please, try again.'));
        }
        $this->set('record', $record);
        $this->setFormLists();

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Record id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $record = $this->lookupTable()->get($id);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $record = $this->lookupTable()->patchEntity($record, $this->getRequest()->getData());
            if ($this->lookupTable()->save($record)) {
                $this->Flash->success(__d('work_reports', 'The record has been saved.'));

                return $this->afterEditRedirect(['action' => 'index']);
            }
            $this->Flash->error(__d('work_reports', 'The record could not be saved. Please, try again.'));
        }
        $this->set('record', $record);
        $this->setFormLists();

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Record id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $record = $this->lookupTable()->get($id);
        if ($this->lookupTable()->delete($record)) {
            $this->Flash->success(__d('work_reports', 'The record has been deleted.'));
        } else {
            $this->flashValidationErrors($record->getErrors());
            $this->Flash->error(__d('work_reports', 'The record could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect(['action' => 'index']);
    }

    /**
     * What the listing shows beside the rows themselves.
     *
     * @return array<string|int, mixed>
     */
    protected function indexContain(): array
    {
        return [];
    }

    /**
     * The lists the form chooses from.
     *
     * @return void
     */
    protected function setFormLists(): void
    {
    }
}
