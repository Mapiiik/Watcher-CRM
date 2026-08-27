<?php
declare(strict_types=1);

namespace App\Addresses\Check;

use App\Check\AbstractCheck;
use Override;

/**
 * Shared ground for address checks.
 *
 * The defaults a check only overrides where it differs are in {@see \App\Check\AbstractCheck},
 * which the contract and customer checks stand on too. Nothing here is peculiar to addresses
 * yet; the class is kept so that one family's ground can be laid without the others'.
 */
abstract class AbstractAddressCheck extends AbstractCheck implements AddressCheckInterface
{
    /**
     * @return string
     */
    #[Override]
    public function element(): string
    {
        return 'AddressChecks/' . $this->template();
    }
}
