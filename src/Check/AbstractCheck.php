<?php
declare(strict_types=1);

namespace App\Check;

use App\Model\Table\ContractsTable;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;

/**
 * Shared ground for checks - the defaults a check only overrides where it differs.
 */
abstract class AbstractCheck implements CheckInterface
{
    use LocatorAwareTrait;

    /**
     * The customers of contracts that are providing services.
     *
     * For the checks whose subject is the customer rather than a service: what is on file is
     * only worth anything about somebody we still serve, and a customer left on record with
     * nothing running is not somebody an invoice goes to. What counts as live is
     * {@see \App\Model\Table\ContractsTable::findWithActiveServices()} and nothing else, so
     * that every check gives the same answer about the same customer.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    protected function activeCustomerIds(): SelectQuery
    {
        /** @var \App\Model\Table\ContractsTable $contracts */
        $contracts = $this->fetchTable(ContractsTable::class);

        return $contracts
            ->find('withActiveServices')
            ->select(['Contracts.customer_id'], true);
    }

    /**
     * The template is named after the check unless it says otherwise.
     *
     * @return string
     */
    public function template(): string
    {
        return $this->id();
    }

    /**
     * Checks are counted on the dashboard unless they say otherwise.
     *
     * @return bool
     */
    public function onDashboard(): bool
    {
        return true;
    }

    /**
     * Checks are listed without being asked for unless they say otherwise.
     *
     * @return bool
     */
    public function optional(): bool
    {
        return false;
    }

    /**
     * Counting is asking the same query how many rows it has.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->find()->count();
    }
}
