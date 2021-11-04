<?php
declare(strict_types=1);

namespace App\View\Cell;

use Cake\View\Cell;

/**
 * Customer cell
 * 
 * @property \App\Model\Table\CustomersTable $Customers
 */
class CustomerCell extends Cell
{
    /**
     * List of valid options that can be passed into this
     * cell's constructor.
     *
     * @var array<string>
     */
    protected $_validCellOptions = [];

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
     * @return void
     */
    public function display()
    {
        $customer_id = $this->request->getParam('customer_id');
        
        if ($customer_id) {
            $this->loadModel('Customers');
            $customer = $this->Customers->get($customer_id, [
                'contain' => ['Contracts' => ['ServiceTypes', 'InstallationAddresses']],
            ]);

            $this->set(compact('customer'));
        }
    }
}
