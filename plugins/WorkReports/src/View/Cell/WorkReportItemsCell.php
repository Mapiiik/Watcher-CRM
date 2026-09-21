<?php
declare(strict_types=1);

namespace WorkReports\View\Cell;

use Cake\View\Cell;

/**
 * The work done at a customer or on a contract, for their pages.
 */
class WorkReportItemsCell extends Cell
{
    /**
     * List of valid options that can be passed into this cell's constructor.
     *
     * @var list<string>
     */
    protected array $_validCellOptions = ['show_contracts'];

    /**
     * Whether the contract of each item is shown, which a contract's own page does not need.
     */
    protected bool $show_contracts = true;

    /**
     * Default display method.
     *
     * @param array<mixed> $conditions Query conditions.
     * @return void
     */
    public function display(array $conditions = []): void
    {
        $items = $this->fetchTable('WorkReports.WorkReportItems')
            ->find(
                'all',
                conditions: $conditions,
                contain: [
                    'WorkReports' => ['Users'],
                    'WorkReportItemTypes',
                    'Contracts',
                    'WorkRates',
                    'WorkLabels',
                ],
                order: [
                    'WorkReportItems.date' => 'DESC',
                    'WorkReportItems.work_from' => 'DESC',
                ],
            )
            ->all();

        $this->set('items', $items);
        $this->set('show_contracts', $this->show_contracts);
    }
}
