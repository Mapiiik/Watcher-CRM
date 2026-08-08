<?php
declare(strict_types=1);

namespace App\Controller\Traits;

use Cake\Core\App;
use Cake\Http\Response;
use Cake\ORM\Table;

/**
 * @psalm-require-extends \Cake\Controller\Controller
 * @method \Cake\Http\ServerRequest getRequest()
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
trait AdditionalParametersTrait
{
    /**
     * The nesting the routes carry, outermost first, and the table each id names.
     *
     * The order is what says that a contract cannot be reached without its customer, so an id that
     * turns out to name nothing takes the ones nested under it with it.
     *
     * @var array<string, string>
     */
    private const NESTING = [
        'customer_id' => 'Customers',
        'contract_id' => 'Contracts',
    ];

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
     * Send a request whose route does not hold up on to the URL that does.
     *
     * Two things can be wrong with a nested URL, and neither of them deserves an error page - what
     * the caller asked for is there, only somewhere else:
     *
     * - The route names a customer or a contract the record does not belong to. The nested routes
     *   match any id against any record, so `/customers/{stranger}/billings/view/{id}` answers with
     *   the billing all the same, under a heading naming a customer it has nothing to do with. The
     *   record says who it belongs to and is answered there.
     * - The route names a customer or a contract that is not there at all, which is what a bookmark
     *   turns into once the contract behind it is deleted. Nothing can be nested under it, so the
     *   nesting is dropped and the caller lands on the same action without it. That matters most for
     *   `add`, where the form would otherwise fill a dead id in and the save fail on `existsIn` -
     *   with the complaint on a field the form does not render, which reads as nothing at all.
     *
     * Only reading is redirected. A `delete` arrives as a POST and would come back as a GET, which
     * would leave the record standing and say it was removed; and a submitted `edit` or `add`
     * carries the form, which a redirect would drop.
     *
     * @return \Cake\Http\Response|null
     */
    protected function redirectIfTheRouteNamesAnother(): ?Response
    {
        $request = $this->getRequest();

        if (!$request->is('get')) {
            return null;
        }

        $id = $request->getParam('pass.0');
        $id = is_string($id) ? $id : null;
        $owner = $this->ownerOfTheRecordAsked($id);
        $corrections = [];
        $gone = false;

        foreach (self::NESTING as $field => $alias) {
            $named = $this->{$field};

            // the action is about that very record, and whether it is there is its own to answer
            if ($named === null || $named === $id) {
                continue;
            }

            if ($gone) {
                // an outer nesting that went leaves this one nowhere to hang
                $corrections[$field] = null;
                continue;
            }

            if (array_key_exists($field, $owner) && $owner[$field] !== $named) {
                $corrections[$field] = $owner[$field];
                continue;
            }

            $table = $this->fetchTable($alias);
            $primaryKey = $table->getPrimaryKey();

            if (is_string($primaryKey) && !$table->exists([$primaryKey => $named])) {
                $corrections[$field] = null;
                $gone = true;
            }
        }

        if ($corrections === []) {
            return null;
        }

        if ($gone) {
            $this->Flash->info(__('The record the address was filed under is no longer there.'));
        }

        $url = ['action' => $request->getParam('action')];
        foreach ((array)$request->getParam('pass') as $passed) {
            $url[] = $passed;
        }

        return $this->redirect($url + $corrections);
    }

    /**
     * Who the record the action was asked for belongs to, as far as the route's nesting goes.
     *
     * Only `view` and `edit` are asked: they are the actions whose first passed argument is this
     * table's own id. Elsewhere it can be anything at all, and looking it up as a key would be a
     * type error rather than a miss.
     *
     * @param string|null $id Id the action was handed.
     * @return array<string, string|null> Empty when there is no record to ask.
     */
    private function ownerOfTheRecordAsked(?string $id): array
    {
        if ($id === null || !in_array($this->getRequest()->getParam('action'), ['view', 'edit'], true)) {
            return [];
        }

        // A controller need not be named after a table - the settings and the pages are named
        // after what they show - and in the web an alias no class answers to is an error rather
        // than an empty answer, so it is asked for only once there is one to ask for.
        $alias = (string)$this->defaultTable;
        if ($alias === '' || App::className($alias, 'Model/Table', 'Table') === null) {
            return [];
        }

        $table = $this->fetchTable();
        $fields = array_intersect(array_keys(self::NESTING), $table->getSchema()->columns());
        $primaryKey = $table->getPrimaryKey();

        // a key of several columns is not something one passed id names
        if ($fields === [] || !is_string($primaryKey)) {
            return [];
        }

        $record = $table->find()
            ->select($fields)
            ->where([$primaryKey => $id])
            ->disableHydration()
            ->first();

        // a record the route cannot name is a record the action will not find either, and saying so
        // is its business rather than this one's
        return is_array($record) ? $record : [];
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
