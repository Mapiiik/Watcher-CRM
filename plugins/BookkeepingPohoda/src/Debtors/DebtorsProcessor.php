<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Debtors;

use Cake\Collection\CollectionInterface;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;

class DebtorsProcessor
{
    use LocatorAwareTrait;

    private static CollectionInterface $debtors;

    private int $allowed_payment_delay;
    private float $allowed_total_overdue_debt;

    /**
     * Constructor
     */
    public function __construct(
        int $allowed_payment_delay = 0,
        float $allowed_total_overdue_debt = 0,
    ) {
        $this->allowed_payment_delay = $allowed_payment_delay;
        $this->allowed_total_overdue_debt = $allowed_total_overdue_debt;
    }

    /**
     * Load Deptors from Database
     *
     * @return void
     */
    private function loadDeptorsFromDatabase(): void
    {
        self::$debtors = $this->fetchTable('BookkeepingPohoda.Invoices')
            ->find()
            ->contain([
                'Customers' => [
                    'strategy' => 'select',
                    'Emails',
                    'Phones',
                ],
            ])
            ->where([
                'Invoices.debt >' => 0,
            ])
            ->orderBy([
                'Invoices.customer_id' => 'ASC',
                'Invoices.creation_date' => 'DESC',
                'Invoices.number' => 'DESC',
            ])
            ->all()
            ->groupBy('customer.id')
            ->map(
                function ($invoices, $customer_id) {
                    return new Debtor($invoices);
                }
            )
            ->sortBy(
                function (Debtor $debtor) {
                    return $debtor->getTotalDebt();
                }
            );
    }

    /**
     * Get Deptors
     *
     * @return \Cake\Collection\CollectionInterface|iterable<\BookkeepingPohoda\Debtors\Debtor>
     */
    public function getDeptors(): CollectionInterface|iterable
    {
        // Load deptors if not already loaded
        if (!isset(self::$debtors)) {
            $this->loadDeptorsFromDatabase();
        }

        // Return filtered deptors
        return self::$debtors
            ->filter(
                function (Debtor $debtor) {
                    return $debtor->getDueDate() < Date::now()->subDays($this->allowed_payment_delay)
                        && $debtor->getTotalOverdueDebt() > $this->allowed_total_overdue_debt;
                }
            );
    }
}
