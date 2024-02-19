<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Controller;

use BookkeepingPohoda\Debtors\DebtorsProcessor;
use Cake\Form\Form;

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
        $allowed_payment_delay = is_numeric($this->getRequest()->getQuery('allowed_payment_delay')) ?
            (int)$this->getRequest()->getQuery('allowed_payment_delay') :
            (int)env('DEBTORS_ALLOWED_PAYMENT_DELAY', '0');

        $allowed_total_overdue_debt = is_numeric($this->getRequest()->getQuery('allowed_total_overdue_debt')) ?
            (float)$this->getRequest()->getQuery('allowed_total_overdue_debt') :
            (float)env('DEBTORS_ALLOWED_TOTAL_OVERDUE_DEBT', '0');

        // filter form
        $filterForm = new Form();
        $filterForm->setData([
            'allowed_payment_delay' => $allowed_payment_delay,
            'allowed_total_overdue_debt' => $allowed_total_overdue_debt,
        ]);
        $this->set('filterForm', $filterForm);

        $debtorsProcessor = new DebtorsProcessor(
            allowed_payment_delay: $allowed_payment_delay,
            allowed_total_overdue_debt: $allowed_total_overdue_debt
        );

        $debtors = $debtorsProcessor->getFilteredOverdueDebtors();

        $this->set(compact('debtors'));
    }

    /**
     * Blocking Update method
     *
     * @return \Cake\Http\Response|null|void Redirects to referer or debtors index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function blockingUpdate(?string $id = null)
    {
        $this->getRequest()->allowMethod(['post']);

        $debtorsProcessor = new DebtorsProcessor(
            allowed_payment_delay: (int)env('DEBTORS_ALLOWED_PAYMENT_DELAY', '0'),
            allowed_total_overdue_debt: (float)env('DEBTORS_ALLOWED_TOTAL_OVERDUE_DEBT', '0'),
        );

        $result = $debtorsProcessor->blockingUpdate();

        $this->Flash->success(
            '<strong>' . __d('bookkeeping_pohoda', 'Routers updated.') . '</strong><br>'
                . ($result ? nl2br($result) : __d('bookkeeping_pohoda', 'Nothing has changed.')),
            ['escape' => false]
        );

        return $this->redirect($this->referer([
            'plugin' => 'BookkeepingPohoda',
            'controller' => 'Debtors',
            'action' => 'index',
        ]));
    }

    /**
     * Block method
     *
     * @param string|null $id Customer id.
     * @return \Cake\Http\Response|null|void Redirects to referer or customer view.
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
     * @return \Cake\Http\Response|null|void Redirects to referer or customer view.
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
