<?php
declare(strict_types=1);

namespace App\Check;

use App\Model\Table\ContractsTable;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;

/**
 * Shared ground for checks - the defaults a check only overrides where it differs.
 *
 * Among them, how a check is narrowed to one record. A check is asked about the whole file
 * from the overview and the dashboard, and about one contract or one customer from that
 * record's own page; all it has to say for the second is which of its fields holds the
 * contract and which holds the customer. What has neither says so by leaving both alone, and
 * is then not offered where it could only answer about everything.
 */
abstract class AbstractCheck implements CheckInterface
{
    use LocatorAwareTrait;

    /**
     * @param string|null $contract_id The one contract being asked about, where there is one.
     * @param string|null $customer_id The one customer being asked about, where there is one.
     */
    public function __construct(
        protected ?string $contract_id = null,
        protected ?string $customer_id = null,
    ) {
    }

    /**
     * The field holding the contract, qualified by its alias, or null where the check has
     * nothing to say about one contract on its own.
     *
     * @return string|null
     */
    protected function contractField(): ?string
    {
        return null;
    }

    /**
     * The field holding the customer, qualified by its alias, or null where the check has
     * nothing to say about one customer on their own.
     *
     * @return string|null
     */
    protected function customerField(): ?string
    {
        return null;
    }

    /**
     * Narrow a query to whatever the check was asked about, where it was asked about anything.
     *
     * @param \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface> $query Query to narrow.
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    protected function scoped(SelectQuery $query): SelectQuery
    {
        $contract = $this->contractField();
        if ($this->contract_id !== null && $contract !== null) {
            $query->where([$contract => $this->contract_id]);
        }

        $customer = $this->customerField();
        if ($this->customer_id !== null && $customer !== null) {
            $query->where([$customer => $this->customer_id]);
        }

        return $query;
    }

    /**
     * Whether the check can answer the question it was given.
     *
     * Asked about one record, a check with no field holding it would answer about the whole
     * file instead - a page about one contract listing findings from every other is worse
     * than a page that leaves the check out, so the registry leaves it out.
     *
     * @return bool
     */
    public function answersWhatWasAsked(): bool
    {
        return ($this->contract_id === null || $this->contractField() !== null)
            && ($this->customer_id === null || $this->customerField() !== null);
    }

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
