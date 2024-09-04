<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Debtors;

use App\Model\Entity\Customer;
use BookkeepingPohoda\Model\Entity\Invoice;
use Cake\Collection\Collection;
use Cake\I18n\Date;

class Debtor
{
    /**
     * @var list<\BookkeepingPohoda\Model\Entity\Invoice>
     */
    private array $invoices;

    private Customer $customer;
    private Date $due_date;
    private float $total_debt;
    private float $total_overdue_debt;

    /**
     * Constructor
     *
     * @param list<\BookkeepingPohoda\Model\Entity\Invoice> $invoices Invoices for a single debtor
     */
    public function __construct(array $invoices)
    {
        $this->invoices = $invoices;

        $invoicesCollection = new Collection($this->invoices);

        $this->customer = $invoicesCollection->first()->customer;
        $this->due_date = $invoicesCollection->min('due_date')->due_date;
        $this->total_debt = $invoicesCollection->sumOf(
            function (Invoice $invoice) {
                return $invoice->debt->toFloat();
            }
        );
        $this->total_overdue_debt = $invoicesCollection
            ->filter(
                function (Invoice $invoice) {
                    return $invoice->due_date < Date::now();
                }
            )
            ->sumOf(
                function (Invoice $invoice) {
                    return $invoice->debt->toFloat();
                }
            );
    }

    /**
     * Get invoices
     *
     * @return list<\BookkeepingPohoda\Model\Entity\Invoice>
     */
    public function getInvoices(): array
    {
        return $this->invoices;
    }

    /**
     * Get customer
     *
     * @return \App\Model\Entity\Customer
     */
    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    /**
     * Get due date
     *
     * @return \Cake\I18n\Date
     */
    public function getDueDate(): Date
    {
        return $this->due_date;
    }

    /**
     * Get total debt
     *
     * @return float
     */
    public function getTotalDebt(): float
    {
        return $this->total_debt;
    }

    /**
     * Get total overdue debt
     *
     * @return float
     */
    public function getTotalOverdueDebt(): float
    {
        return $this->total_overdue_debt;
    }

    /**
     * Get total overdue debt for specific date
     *
     * @return float
     */
    public function getTotalOverdueDebtForDate(Date $date): float
    {
        $invoicesCollection = new Collection($this->invoices);

        return $invoicesCollection
        ->filter(
            function (Invoice $invoice) use ($date) {
                return $invoice->due_date < $date;
            }
        )
        ->sumOf(
            function (Invoice $invoice) {
                return $invoice->debt->toFloat();
            }
        );
    }
}
