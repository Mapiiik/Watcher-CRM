<?php
declare(strict_types=1);

namespace BookkeepingPohoda\View\Cell;

use Cake\View\Cell;

/**
 * Invoices cell
 */
class InvoicesCell extends Cell
{
    /**
     * List of valid options that can be passed into this
     * cell's constructor.
     *
     * @var array<string>
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
        $invoices = $this->fetchTable('BookkeepingPohoda.Invoices')
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
