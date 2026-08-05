<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use Cake\ORM\Table;

/**
 * @psalm-require-extends \Cake\Controller\Controller
 * @method \Cake\Http\ServerRequest getRequest()
 */
trait AdditionalParametersTrait
{
    /*
     * Customer ID
     */
    protected ?string $customer_id = null;

    /*
     * Contract ID
     */
    protected ?string $contract_id = null;

    /**
     * Load and set additonal parameters
     *
     * @return void
     */
    protected function loadAdditionalParameters()
    {
        # Load selected customer ID from request
        $this->customer_id = $this->getRequest()->getParam('customer_id');
        $this->set('customer_id', $this->customer_id);

        # Load selected contract ID from request
        $this->contract_id = $this->getRequest()->getParam('contract_id');
        $this->set('contract_id', $this->contract_id);
    }

    /**
     * Add the ids the route carries to the data a form submitted.
     *
     * The forms under a customer or a contract do not render those fields: the route already says
     * which record it is, and the template leaves them out for exactly that reason. They have to
     * reach the entity as data rather than be set on it afterwards, or they miss the marshalling
     * that checks and casts them, and the validator asking for them - it reads what the request
     * carried, not what the entity ended up holding.
     *
     * Adding them after reading the request also settles which of the two wins: the route. Set on
     * the entity beforehand, as this used to be, a hand-written request could name a different
     * customer in the body and have it override the one in the URL.
     *
     * This is for creating a record only. Which record an existing one belongs to is its own to
     * say, and a route naming another one must not quietly move it there.
     *
     * @param \Cake\ORM\Table $table Table the data is being marshalled for.
     * @param array<mixed> $data Data the request carried.
     * @return array<mixed>
     */
    protected function dataWithAdditionalParameters(Table $table, array $data): array
    {
        $parameters = [
            'customer_id' => $this->customer_id,
            'contract_id' => $this->contract_id,
        ];

        foreach ($parameters as $field => $value) {
            if ($value !== null && $table->getSchema()->hasColumn($field)) {
                $data[$field] = $value;
            }
        }

        return $data;
    }
}
