<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Controller;

use BookkeepingPohoda\Debtors\DebtorsProcessor;

/**
 * Invoices Controller
 *
 * @property \BookkeepingPohoda\Model\Table\InvoicesTable $Invoices
 * @method \BookkeepingPohoda\Model\Entity\Invoice[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class DebtorsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $debtorsProcessor = new DebtorsProcessor(
            allowed_payment_delay: (int)$this->getRequest()->getQuery('allowed_payment_delay', 0),
            allowed_total_overdue_debt: (float)$this->getRequest()->getQuery('allowed_total_overdue_debt', 0),
        );

        $debtors = $debtorsProcessor->getDeptors();

        $this->set(compact('debtors'));
    }

    /**
     * Unblock method
     *
     * @param string|null $id Customer id.
     * @return \Cake\Http\Response|null|void Redirects always to customer view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function block(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post']);

        $debtorsProcessor = new DebtorsProcessor();
        $result = $debtorsProcessor->block($id);

        $this->Flash->success(
            '<strong>' . __d('bookkeeping_pohoda', 'Routers updated.') . '</strong><br>'
                . ($result ? nl2br($result) : __d('bookkeeping_pohoda', 'Nothing has changed.')),
            ['escape' => false]
        );

        return $this->redirect($this->referer([
            'plugin' => null,
            'controller' => 'Customers',
            'action' => 'view',
            $id,
        ]));
    }

    /**
     * Unblock method
     *
     * @param string|null $id Customer id.
     * @return \Cake\Http\Response|null|void Redirects always to customer view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function unblock(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post']);

        $debtorsProcessor = new DebtorsProcessor();
        $result = $debtorsProcessor->unblock($id);

        $this->Flash->success(
            '<strong>' . __d('bookkeeping_pohoda', 'Routers updated.') . '</strong><br>'
                . ($result ? nl2br($result) : __d('bookkeeping_pohoda', 'Nothing has changed.')),
            ['escape' => false]
        );

        return $this->redirect($this->referer([
            'plugin' => null,
            'controller' => 'Customers',
            'action' => 'view',
            $id,
        ]));
    }
}
