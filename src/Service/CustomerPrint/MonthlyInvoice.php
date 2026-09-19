<?php
declare(strict_types=1);

namespace App\Service\CustomerPrint;

use App\Model\Entity\Billing;
use App\Model\Entity\Contract;
use PhpCollective\DecimalObject\Decimal;

/**
 * One invoice a customer is sent each month, and what it comes to.
 *
 * It names the billing or the contract it is for when it is for one alone. The common invoice names
 * neither.
 */
final class MonthlyInvoice
{
    /**
     * @param \PhpCollective\DecimalObject\Decimal $total What it comes to.
     * @param \App\Model\Entity\Contract|null $contract The contract it is for alone.
     * @param \App\Model\Entity\Billing|null $billing The billing it is for alone.
     */
    public function __construct(
        public readonly Decimal $total,
        public readonly ?Contract $contract = null,
        public readonly ?Billing $billing = null,
    ) {
    }
}
