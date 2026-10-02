<?php
declare(strict_types=1);

namespace App\Customers\Check;

use App\Check\AbstractCheck;
use App\Check\CheckScope;
use App\Model\Table\CustomerProposalsTable;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * Shared ground for the checks that read the papers put to a customer themselves.
 *
 * Unlike the customer's other checks, no label excuses these: a job that was started and not
 * finished is not answered by saying so. A consent belongs to nobody's contract, so they say
 * nothing about one either.
 */
abstract class AbstractCustomerProposalCheck extends AbstractCheck implements CustomerCheckInterface
{
    /**
     * @param \App\Model\Table\CustomerProposalsTable $proposals Customer proposals table.
     * @param \App\Check\CheckScope $scope What is being asked about, and how widely.
     */
    public function __construct(
        protected CustomerProposalsTable $proposals,
        CheckScope $scope = new CheckScope(),
    ) {
        parent::__construct($scope);
    }

    /**
     * @return string
     */
    #[Override]
    public function element(): string
    {
        return 'CustomerChecks/' . $this->template();
    }

    /**
     * All three share the one template: which step is missing reads off the days it shows.
     *
     * @return string
     */
    #[Override]
    public function template(): string
    {
        return 'customer_proposal';
    }

    /**
     * @return string|null
     */
    #[Override]
    protected function customerField(): ?string
    {
        return 'CustomerProposals.customer_id';
    }

    /**
     * The rounds this check may report, before it says what it is looking for. Papers put to
     * somebody we no longer serve are nobody's work.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    protected function candidates(): SelectQuery
    {
        $query = $this->proposals->find()->contain(['Customers', 'ContractProposals']);

        if ($this->scope->ignore_inactive) {
            $query->where(['CustomerProposals.customer_id IN' => $this->activeCustomerIds()]);
        }

        return $query;
    }
}
