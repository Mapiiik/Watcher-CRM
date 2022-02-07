<?php
declare(strict_types=1);

namespace App\View\Cell;

use Cake\View\Cell;

/**
 * Customer cell
 */
class CustomerCell extends Cell
{
    /**
     * List of valid options that can be passed into this
     * cell's constructor.
     *
     * @var array<string>
     */
    protected $_validCellOptions = ['compact'];

    /**
     * Compact view
     *
     * @var bool
     */
    protected $compact = false;

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
     * @param string|null $customer_id Customer Id.
     * @return void
     */
    public function display($customer_id)
    {
        if ($customer_id) {
            $customer = $this->getTableLocator()->get('Customers')->get($customer_id, [
                'contain' => ['Contracts' => ['ServiceTypes', 'InstallationAddresses']],
            ]);

            $this->set(compact('customer'));

            $this->set('compact', $this->compact);
        }
    }
}
