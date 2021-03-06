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
    protected $_validCellOptions = ['show_customers'];

    /**
     * Show customers
     *
     * @var bool
     */
    protected $show_customers = true;

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
    public function display(array $conditions = [])
    {
        $invoices = $this->fetchTable('BookkeepingPohoda.Invoices')
            ->find('all', [
                'conditions' => $conditions,
                'contain' => [
                    'Customers',
                ],
                'order' => [
                    'Invoices.id' => 'DESC',
                ],
            ])
            ->all();

        $this->set(compact('invoices'));
        $this->set('show_customers', $this->show_customers);
    }
}
