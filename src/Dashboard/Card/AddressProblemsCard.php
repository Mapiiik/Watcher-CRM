<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use Override;

/**
 * How much each of the address checks currently has to say.
 */
class AddressProblemsCard extends AbstractProblemsCard
{
    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'address_problems';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Address Problems');
    }

    /**
     * @return string
     */
    #[Override]
    protected function overviewAction(): string
    {
        return 'overviewOfAddressProblems';
    }
}
