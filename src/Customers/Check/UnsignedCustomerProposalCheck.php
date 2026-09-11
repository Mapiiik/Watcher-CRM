<?php
declare(strict_types=1);

namespace App\Customers\Check;

use App\Proposals\LateProposals;
use Cake\ORM\Query\SelectQuery;
use Override;
use Settings\Utility\Settings;

/**
 * Papers that went out to the customer themselves and have not come back signed.
 *
 * Nothing is held up by it - a consent nobody has signed simply is not one - but the asking has
 * been done and only the answer makes it worth anything.
 */
class UnsignedCustomerProposalCheck extends AbstractCustomerProposalCheck
{
    /**
     * How long the papers may be out before it is worth raising, if nothing says otherwise.
     */
    private const AFTER_DAYS = 14;

    /**
     * Where the settings say how long that is.
     */
    private const AFTER_DAYS_PATH = 'core.customers.proposals.unanswered_after_days';

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'unsigned_customer_proposal';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Papers for the Customer Waiting for a Signature');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every round of papers that went out has come back signed.');
    }

    /**
     * Rounds that went out and have not come back.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $after = (int)Settings::get(self::AFTER_DAYS_PATH, self::AFTER_DAYS);

        $query = $this->candidates()->find('open');

        LateProposals::unanswered($query, 'CustomerProposals', $after);

        return $this->scoped($query);
    }
}
