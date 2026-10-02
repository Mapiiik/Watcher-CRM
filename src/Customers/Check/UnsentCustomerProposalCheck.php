<?php
declare(strict_types=1);

namespace App\Customers\Check;

use App\Proposals\LateProposals;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A round of papers drawn up for the customer and never sent.
 *
 * The day the papers speak about arrives whether or not anybody printed them, and nothing else
 * would ever mention that nobody did.
 */
class UnsentCustomerProposalCheck extends AbstractCustomerProposalCheck
{
    /**
     * How far ahead a round nobody has sent is worth raising, if nothing says otherwise.
     */
    private const WITHIN_DAYS = 14;

    /**
     * Where the settings say how far ahead to look.
     */
    private const WITHIN_DAYS_PATH = 'core.customers.paperwork.unsent.before_effective_days';

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'unsent_customer_proposal';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Customer Proposal That Was Never Sent');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every customer proposal created has been sent.');
    }

    /**
     * Rounds nobody has sent to the customer.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $within = $this->days(self::WITHIN_DAYS_PATH, self::WITHIN_DAYS);

        $query = $this->candidates()->find('open');

        LateProposals::neverSent($query, 'CustomerProposals', $within);

        return $this->scoped($query);
    }
}
