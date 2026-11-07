<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use Override;

/**
 * How much each of the contract checks currently has to say.
 */
class ContractProblemsCard extends AbstractProblemsCard
{
    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'contract_problems';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Contract Problems');
    }

    /**
     * @return string
     */
    #[Override]
    protected function overviewAction(): string
    {
        return 'overviewOfContractProblems';
    }
}
