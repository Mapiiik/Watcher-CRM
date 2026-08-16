<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use Bookkeeping\Debtors\DebtorsProcessor;
use Override;
use Settings\Utility\Settings;

/**
 * Shared ground for the cards about people who owe money.
 *
 * Both read the accounting records, so both are fetched on their own request, and both ask
 * by the thresholds the debtor listing and the nightly run already work by.
 */
abstract class AbstractDebtorCard extends AbstractDashboardCard
{
    /**
     * @return bool
     */
    #[Override]
    public function deferred(): bool
    {
        return true;
    }

    /**
     * A processor set to the tolerances currently configured.
     *
     * @return \Bookkeeping\Debtors\DebtorsProcessor
     */
    protected function processor(): DebtorsProcessor
    {
        $delay = Settings::get('bookkeeping.debtors.thresholds.allowed_payment_delay', 0);
        $amount = Settings::get('bookkeeping.debtors.thresholds.allowed_total_overdue_debt', 0);

        return new DebtorsProcessor(
            allowed_payment_delay: is_numeric($delay) ? (int)$delay : 0,
            allowed_total_overdue_debt: is_numeric($amount) ? (float)$amount : 0.0,
        );
    }
}
