<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Debtors;

use Cake\Collection\CollectionInterface;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;

class DebtorsProcessor
{
    use LocatorAwareTrait;

    private int $allowed_payment_delay;
    private float $allowed_total_debt;
    private float $allowed_debt_per_invoice;

    /**
     * Constructor
     */
    public function __construct(
        int $allowed_payment_delay = 0,
        float $allowed_total_debt = 0,
        float $allowed_debt_per_invoice = 0,
    ) {
        $this->allowed_payment_delay = $allowed_payment_delay;
        $this->allowed_total_debt = $allowed_total_debt;
        $this->allowed_debt_per_invoice = $allowed_debt_per_invoice;
    }

    /**
     * Get Deptors
     *
     * @return \Cake\Collection\CollectionInterface|iterable<\BookkeepingPohoda\Debtors\Debtor>
     */
    public function getDeptors(): CollectionInterface|iterable
    {
        return $this->fetchTable('BookkeepingPohoda.Invoices')
            ->find()
            ->contain([
                'Customers' => [
                    'strategy' => 'select',
                    'Emails',
                    'Phones',
                ],
            ])
            ->where([
                'Invoices.debt >' => $this->allowed_debt_per_invoice,
                'Invoices.due_date <' => Date::now()->subDays($this->allowed_payment_delay),
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
            ->filter(
                function (Debtor $debtor) {
                    return $debtor->getTotalDebt() > $this->allowed_total_debt;
                }
            )
            ->sortBy(
                function (Debtor $debtor) {
                    return $debtor->getTotalDebt();
                }
            );
    }
}
