<?php
declare(strict_types=1);

namespace WorkReports\Controller;

use Cake\Http\Response;
use WorkReports\Model\Entity\WorkReportWorkerRecipient;

/**
 * WorkReportWorkerRecipients Controller
 *
 * Who gets the reports of a worker, managed from the worker's page.
 *
 * @property \WorkReports\Model\Table\WorkReportWorkerRecipientsTable $WorkReportWorkerRecipients
 */
class WorkReportWorkerRecipientsController extends AppController
{
    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add(): ?Response
    {
        $recipient = $this->WorkReportWorkerRecipients->newEmptyEntity();
        $recipient->work_report_worker_id = (string)$this->getRequest()->getQuery('work_report_worker_id');
        $recipient->may_edit = false;
        if ($this->getRequest()->is('post')) {
            $recipient = $this->WorkReportWorkerRecipients->patchEntity($recipient, $this->getRequest()->getData());
            if ($this->WorkReportWorkerRecipients->save($recipient)) {
                $this->Flash->success(__d('work_reports', 'The record has been saved.'));

                return $this->afterAddRedirect($this->workerUrl($recipient));
            }
            $this->Flash->error(__d('work_reports', 'The record could not be saved. Please, try again.'));
        }
        $this->setFormLists($recipient);

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Recipient id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     */
    public function edit(?string $id = null): ?Response
    {
        $recipient = $this->WorkReportWorkerRecipients->get($id);
        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $recipient = $this->WorkReportWorkerRecipients->patchEntity($recipient, $this->getRequest()->getData());
            if ($this->WorkReportWorkerRecipients->save($recipient)) {
                $this->Flash->success(__d('work_reports', 'The record has been saved.'));

                return $this->afterEditRedirect($this->workerUrl($recipient));
            }
            $this->Flash->error(__d('work_reports', 'The record could not be saved. Please, try again.'));
        }
        $this->setFormLists($recipient);

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Recipient id.
     * @return \Cake\Http\Response|null Redirects to the worker.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $recipient = $this->WorkReportWorkerRecipients->get($id);
        if ($this->WorkReportWorkerRecipients->delete($recipient)) {
            $this->Flash->success(__d('work_reports', 'The record has been deleted.'));
        } else {
            $this->Flash->error(__d('work_reports', 'The record could not be deleted. Please, try again.'));
        }

        return $this->afterDeleteRedirect($this->workerUrl($recipient));
    }

    /**
     * The page of the worker the recipient belongs to.
     *
     * @param \WorkReports\Model\Entity\WorkReportWorkerRecipient $recipient Recipient.
     * @return array<string|int, mixed>
     */
    protected function workerUrl(WorkReportWorkerRecipient $recipient): array
    {
        return ['controller' => 'WorkReportWorkers', 'action' => 'view', $recipient->work_report_worker_id];
    }

    /**
     * What the form chooses from.
     *
     * @param \WorkReports\Model\Entity\WorkReportWorkerRecipient $recipient Recipient being edited.
     * @return void
     */
    protected function setFormLists(WorkReportWorkerRecipient $recipient): void
    {
        /** @var \App\Model\Table\AppUsersTable $users */
        $users = $this->fetchTable('AppUsers');

        $this->set('recipient', $recipient);
        $this->set('people', $this->usersForSelect($users->find()->where([
            'OR' => ['AppUsers.active' => true, 'AppUsers.id IS' => $recipient->user_id],
        ])));
    }
}
