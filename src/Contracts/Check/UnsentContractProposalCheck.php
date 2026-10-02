<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Proposals\LateProposals;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A proposal drawn up and never sent.
 *
 * Nobody is waiting on the customer here - the papers never left the building. It belongs beside
 * the proposals waiting for a signature all the same, because from the office's side the two are
 * the same job half done, and this is the half nothing else reports: the day the version takes
 * effect arrives whether or not anybody printed anything.
 *
 * A proposal whose day is still far off is not shown at all, whichever question is asked: until
 * then there is nothing to do about it, and it would only be a list of things to leave alone.
 * Lifting the filter widens this one to the contracts that serve nobody, and to nothing else.
 */
class UnsentContractProposalCheck extends AbstractContractProposalCheck
{
    /**
     * How far ahead a proposal nobody has sent is worth raising, if nothing says otherwise.
     */
    private const WITHIN_DAYS = 14;

    /**
     * Where the settings say how far ahead to look.
     */
    private const WITHIN_DAYS_PATH = 'core.contracts.paperwork.unsent.before_effective_days';

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'unsent_contract_proposal';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Contract Proposal That Was Never Sent');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every contract proposal created has been sent.');
    }

    /**
     * Proposals nobody has sent to the customer.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $within = $this->days(self::WITHIN_DAYS_PATH, self::WITHIN_DAYS);

        $query = $this->candidates('open');

        // The wait holds whichever question is being asked. What the wider reading adds is the
        // contracts that serve nobody, not the proposals whose day has not come yet.
        LateProposals::neverSent($query, 'ContractProposals', $within, 'CustomerProposals');

        $this->onlyWhatIsRunning($query);

        return $this->scoped($query);
    }
}
