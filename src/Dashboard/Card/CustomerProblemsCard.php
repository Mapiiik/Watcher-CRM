<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use Override;

/**
 * How much each of the customer checks currently has to say.
 */
class CustomerProblemsCard extends AbstractProblemsCard
{
    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'customer_problems';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Customer Problems');
    }

    /**
     * @return string
     */
    #[Override]
    protected function overviewAction(): string
    {
        return 'overviewOfCustomerProblems';
    }
}
