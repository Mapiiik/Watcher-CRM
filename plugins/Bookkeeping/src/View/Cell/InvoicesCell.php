<?php
declare(strict_types=1);

namespace Bookkeeping\View\Cell;

use Bookkeeping\Model\Table\InvoicesTable;
use Cake\View\Cell;
use Override;

/**
 * Invoices cell
 *
 * @extends \Cake\View\Cell<\App\View\AppView>
 */
class InvoicesCell extends Cell
{
    /**
     * List of valid options that can be passed into this
     * cell's constructor.
     *
     * @var list<string>
     */
    protected array $_validCellOptions = ['show_customers'];

    /**
     * Show customers
     *
     * @var bool
     */
    protected bool $show_customers = true;

    /**
     * Initialization logic run at the end of object construction.
     *
     * @return void
     */
    #[Override]
    public function initialize(): void
    {
    }

    /**
     * Default display method.
     *
     * @param array<mixed> $conditions Query conditions.
     * @return void
     */
    public function display(array $conditions = []): void
    {
        $invoices = $this->fetchTable(InvoicesTable::class)
            ->find(
                'all',
                conditions: $conditions + (
                    $this->request->getQuery('show_also_paid_invoices') === '1' ? [] : ['Invoices.debt !=' => 0]
                ),
                contain: [
                    'Customers',
                ],
                order: [
                    'Invoices.creation_date' => 'DESC',
                ],
            )
            ->all();

        $this->set(compact('invoices'));
        $this->set('show_customers', $this->show_customers);
    }
}
