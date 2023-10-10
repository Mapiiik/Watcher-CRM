<?php
declare(strict_types=1);

namespace App\Controller\Traits;

/**
 * @psalm-require-extends \Cake\Controller\Controller
 * @method \Cake\Http\ServerRequest getRequest()
 */
trait AdditionalParametersTrait
{
    /*
     * Customer ID
     */
    protected ?int $customer_id = null;

    /*
     * Contract ID
     */
    protected ?int $contract_id = null;

    /**
     * Load and set additonal parameters
     *
     * @return void
     */
    protected function loadAdditionalParameters()
    {
        # Load selected customer ID from request
        $this->customer_id = $this->getRequest()->getParam('customer_id') ?
            (int)$this->getRequest()->getParam('customer_id') :
            null;
        $this->set('customer_id', $this->customer_id);

        # Load selected contract ID from request
        $this->contract_id = $this->getRequest()->getParam('contract_id') ?
            (int)$this->getRequest()->getParam('contract_id') :
            null;
        $this->set('contract_id', $this->contract_id);
    }
}
