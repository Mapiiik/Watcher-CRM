<?php
declare(strict_types=1);

namespace Bookkeeping\Controller;

use App\Controller\Traits\MessageHandlerTrait;
use Bookkeeping\Debtors\DebtorsProcessor;
use Cake\Core\Configure;
use Cake\Form\Form;
use Cake\Http\Response;

/**
 * Invoices Controller
 *
 * @property \Bookkeeping\Model\Table\InvoicesTable $Invoices
 */
class DebtorsController extends AppController
{
    use MessageHandlerTrait;

    /**
     * Index method
     *
     * @return void Renders view
     */
    public function index(): void
    {
        $allowed_payment_delay = is_numeric($this->getRequest()->getQuery('allowed_payment_delay')) ?
            (int)$this->getRequest()->getQuery('allowed_payment_delay') :
            (int)Configure::read('Bookkeeping.debtors.allowedPaymentDelay');

        $allowed_total_overdue_debt = is_numeric($this->getRequest()->getQuery('allowed_total_overdue_debt')) ?
            (float)$this->getRequest()->getQuery('allowed_total_overdue_debt') :
            (float)Configure::read('Bookkeeping.debtors.allowedTotalOverdueDebt');

        // filter form
        $filterForm = new Form();
        $filterForm->setData([
            'allowed_payment_delay' => $allowed_payment_delay,
            'allowed_total_overdue_debt' => $allowed_total_overdue_debt,
        ]);
        $this->set('filterForm', $filterForm);

        $debtorsProcessor = new DebtorsProcessor(
            allowed_payment_delay: $allowed_payment_delay,
            allowed_total_overdue_debt: $allowed_total_overdue_debt,
        );

        $debtors = $debtorsProcessor->getFilteredOverdueDebtors();

        $this->set(compact('debtors'));
    }

    /**
     * Blocking Update method
     *
     * @return \Cake\Http\Response|null Redirects to referer or debtors index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function blockingUpdate(): ?Response
    {
        $this->getRequest()->allowMethod(['post']);

        $debtorsProcessor = new DebtorsProcessor(
            allowed_payment_delay: (int)Configure::read('Bookkeeping.debtors.allowedPaymentDelay'),
            allowed_total_overdue_debt: (float)Configure::read('Bookkeeping.debtors.allowedTotalOverdueDebt'),
        );

        $debtorsProcessor->blockingUpdate();

        $this->handleMessages($debtorsProcessor->getMessages());

        return $this->redirect($this->referer([
            'plugin' => 'Bookkeeping',
            'controller' => 'Debtors',
            'action' => 'index',
        ]));
    }

    /**
     * Block method
     *
     * @param string|null $id Customer id.
     * @return \Cake\Http\Response|null Redirects to referer or customer view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function block(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post']);

        $debtorsProcessor = new DebtorsProcessor();
        $debtorsProcessor->block($id);

        $this->handleMessages($debtorsProcessor->getMessages());

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
     * @return \Cake\Http\Response|null Redirects to referer or customer view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function unblock(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post']);

        $debtorsProcessor = new DebtorsProcessor();
        $debtorsProcessor->unblock($id);

        $this->handleMessages($debtorsProcessor->getMessages());

        return $this->redirect($this->referer([
            'plugin' => null,
            'controller' => 'Customers',
            'action' => 'view',
            $id,
        ]));
    }
}
