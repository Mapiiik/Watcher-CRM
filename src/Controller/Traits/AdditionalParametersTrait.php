<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use Cake\Http\Response;
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
     * Send a request whose route names a customer or a contract the record does not belong to on to
     * the URL the record does answer to.
     *
     * The nested routes match any id against any record: `/customers/{stranger}/billings/view/{id}`
     * answers with the billing all the same, under a heading naming a customer it has nothing to do
     * with. A hand-written or gone-stale URL should not be an error - the record exists and the
     * caller is welcome to it - so it is answered where it belongs instead.
     *
     * Only reading is redirected. A `delete` arrives as a POST and would come back as a GET, which
     * would leave the record standing and say it was removed; and a submitted `edit` carries the
     * form, which a redirect would drop. Neither reads the route's ids anyway - what belongs to what
     * is the record's own to say - so both are left to go on with the record they were given.
     *
     * @return \Cake\Http\Response|null
     */
    protected function redirectIfTheRouteNamesAnother(): ?Response
    {
        $request = $this->getRequest();

        if (!$request->is('get') || !in_array($request->getParam('action'), ['view', 'edit'], true)) {
            return null;
        }

        $id = $request->getParam('pass.0');
        $parameters = array_filter([
            'customer_id' => $this->customer_id,
            'contract_id' => $this->contract_id,
        ], fn(?string $value): bool => $value !== null);

        if (!is_string($id) || $parameters === []) {
            return null;
        }

        $table = $this->fetchTable();
        $fields = array_intersect(array_keys($parameters), $table->getSchema()->columns());
        $primaryKey = $table->getPrimaryKey();

        // a key of several columns is not something one passed id names
        if ($fields === [] || !is_string($primaryKey)) {
            return null;
        }

        $record = $table->find()
            ->select($fields)
            ->where([$primaryKey => $id])
            ->disableHydration()
            ->first();

        // a record the route cannot name is a record the action will not find either, and saying so
        // is its business rather than this one's
        if (!is_array($record) || $record == array_intersect_key($parameters, $record)) {
            return null;
        }

        return $this->redirect([
            'action' => $request->getParam('action'),
            $id,
        ] + $record);
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
