<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use Cake\Event\EventInterface;

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
     * Global beforeFilter
     *
     * @param \Cake\Event\EventInterface<\Cake\Controller\Controller> $event An Event instance
     * @return \Cake\Http\Response|null|void
     * @link https://book.cakephp.org/4/en/controllers.html#request-life-cycle-callbacks
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    public function beforeFilter(EventInterface $event)
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

        parent::beforeFilter($event);
    }
}
