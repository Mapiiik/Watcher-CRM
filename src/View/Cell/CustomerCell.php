<?php
declare(strict_types=1);

namespace App\View\Cell;

use App\Model\Table\CustomersTable;
use Cake\View\Cell;
use Override;

/**
 * Customer cell
 */
class CustomerCell extends Cell
{
    /**
     * List of valid options that can be passed into this
     * cell's constructor.
     *
     * @var list<string>
     */
    protected array $_validCellOptions = ['compact'];

    /**
     * Compact view
     *
     * @var bool
     */
    protected bool $compact = false;

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
     * @param string|null $customer_id Customer Id.
     * @return void
     */
    public function display(?string $customer_id): void
    {
        if ($customer_id) {
            $customer = $this->fetchTable(CustomersTable::class)->get($customer_id, contain: [
                'Contracts' => [
                    'ContractStates',
                    'InstallationAddresses',
                    'ServiceTypes',
                ],
            ]);

            $this->set(compact('customer'));

            $this->set('compact', $this->compact);
        }
    }
}
