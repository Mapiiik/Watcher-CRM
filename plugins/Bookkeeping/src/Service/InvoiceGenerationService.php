<?php
declare(strict_types=1);

namespace Bookkeeping\Service;

use App\Model\Entity\AccountingProfile;
use App\Model\Entity\Billing;
use App\Model\Entity\Contract;
use App\Model\Entity\Customer;
use App\Model\Table\CustomersTable;
use Bookkeeping\Model\ValueObject\InvoiceDraft;
use Cake\I18n\Date;
use PhpCollective\DecimalObject\Decimal;
use Settings\Utility\Settings;

/**
 * InvoiceGenerationService
 *
 * Generates invoice drafts for a given invoiced month and accounting profile
 * based on CRM billing data.
 *
 * This service:
 * - aggregates billing data per customer and contract
 * - applies business rules for separate invoices
 * - calculates invoice numbers, totals and due dates
 * - produces InvoiceDraft value objects
 *
 * It does NOT:
 * - persist invoices
 * - export invoices
 * - perform UI logic
 */
final class InvoiceGenerationService
{
    /**
     * Constructor
     */
    public function __construct(
        private CustomersTable $customers,
    ) {
    }

    /**
     * Generate invoice drafts.
     *
     * @return list<\Bookkeeping\Model\ValueObject\InvoiceDraft>
     */
    public function generate(
        Date $invoicedMonth,
        AccountingProfile $accountingProfile,
    ): array {
        $prefix = $this->calculateInvoicePrefix($invoicedMonth, $accountingProfile);
        $index = 1;
        $drafts = [];

        /** @var iterable<\App\Model\Entity\Customer> $customers */
        $customers = $this->customers->find(
            'billingDataForMonth',
            invoicedMonth: $invoicedMonth,
            accountingProfileId: $accountingProfile->id,
        );

        foreach ($customers as $customer) {
            // Customer-level aggregation
            $billingCustomerTotal = Decimal::create(0, 2);
            $billingCustomerItems = [];

            foreach ($customer->contracts as $contract) {
                // Contract-level aggregation
                $billingContractTotal = Decimal::create(0, 2);
                $billingContractItems = [];

                foreach ($contract->billings as $billing) {
                    if ($billing->isSeparateInvoice() && !$billing->period_total->isZero()) {
                        // Separate invoice per billing item
                        $drafts[] = $this->createDraft(
                            number: (string)($prefix + $index++),
                            customer: $customer,
                            total: $billing->period_total,
                            items: $contract->isInvoiceWithItems() || $customer->isInvoiceWithItems() ? [$billing] : [],
                            text: $this->buildSeparateBillingText($billing, $invoicedMonth),
                            invoicedMonth: $invoicedMonth,
                            internalNote: 'separate',
                        );
                    } else {
                        $billingContractTotal = $billingContractTotal->add($billing->period_total);
                        $billingContractItems[] = $billing;
                    }
                }

                if ($contract->isSeparateInvoice() && !$billingContractTotal->isZero()) {
                    // Separate invoice per contract
                    $drafts[] = $this->createDraft(
                        number: (string)($prefix + $index++),
                        customer: $customer,
                        total: $billingContractTotal,
                        items: $contract->isInvoiceWithItems() ? $billingContractItems : [],
                        text: $this->buildContractInvoiceText($contract, $invoicedMonth),
                        invoicedMonth: $invoicedMonth,
                        internalNote: 'separate',
                    );
                } else {
                    // Merge into customer invoice
                    $billingCustomerTotal = $billingCustomerTotal->add($billingContractTotal);
                    $billingCustomerItems = array_merge(
                        $billingCustomerItems,
                        $billingContractItems,
                    );
                }
            }

            if (!$billingCustomerTotal->isZero()) {
                // Customer-level invoice
                $drafts[] = $this->createDraft(
                    number: (string)($prefix + $index++),
                    customer: $customer,
                    total: $billingCustomerTotal,
                    items: $customer->isInvoiceWithItems() ? $billingCustomerItems : [],
                    text: $this->buildCustomerInvoiceText($customer, $invoicedMonth),
                    invoicedMonth: $invoicedMonth,
                );
            }
        }

        return $drafts;
    }

    /**
     * Create and populate InvoiceDraft.
     *
     * @param list<\App\Model\Entity\Billing> $items
     */
    private function createDraft(
        string $number,
        Customer $customer,
        Decimal $total,
        array $items,
        string $text,
        Date $invoicedMonth,
        ?string $internalNote = null,
    ): InvoiceDraft {
        $draft = new InvoiceDraft();

        $draft->number = $number;
        $draft->customer = $customer;
        $draft->variableSymbol = (int)$customer->number;
        $draft->creationDate = $invoicedMonth->lastOfMonth();
        $draft->dueDate = $this->calculateDueDate($customer, $invoicedMonth);
        $draft->text = $text;
        $draft->internalNote = $internalNote;
        $draft->total = $total;
        $draft->debt = $total;
        $draft->items = $items;

        return $draft;
    }

    /**
     * Calculate invoice number prefix.
     */
    private function calculateInvoicePrefix(Date $month, AccountingProfile $accountingProfile): int
    {
        return 10000000 * ($month->year - 1980)
            + 1000000 * ($accountingProfile->reverse_charge ? 8 : 9)
            + 10000 * $month->month;
    }

    /**
     * Calculate invoice due date.
     */
    private function calculateDueDate(Customer $customer, Date $month): Date
    {
        return $month
            ->lastOfMonth()
            ->addDays($customer->individual_maturity_period ?? 10);
    }

    /**
     * Build invoice text for separate billing item.
     */
    private function buildSeparateBillingText(Billing $billing, Date $month): string
    {
        return strtr(
            Settings::getString('bookkeeping.invoices.texts.separate'),
            [
                '{service_name}' => $billing->name,
                '{invoiced_month}' => $month->i18nFormat('MM/yyyy'),
            ],
        );
    }

    /**
     * Build invoice text for contract invoice.
     */
    private function buildContractInvoiceText(Contract $contract, Date $month): string
    {
        if ($contract->getInvoiceText()) {
            return strtr($contract->getInvoiceText(), [
                '{number}' => $contract->number, // legacy
                '{month}' => $month->i18nFormat('MM/yyyy'), // legacy
                '{contract_number}' => $contract->number,
                '{invoiced_month}' => $month->i18nFormat('MM/yyyy'),
            ]);
        }

        return strtr(
            Settings::getString('bookkeeping.invoices.texts.default'),
            [
                '{contract_number}' => $contract->number,
                '{invoiced_month}' => $month->i18nFormat('MM/yyyy'),
            ],
        );
    }

    /**
     * Build invoice text for customer-level invoice.
     */
    private function buildCustomerInvoiceText(Customer $customer, Date $month): string
    {
        return strtr(
            Settings::getString('bookkeeping.invoices.texts.default'),
            [
                '{contract_number}' => $customer->number,
                '{invoiced_month}' => $month->i18nFormat('MM/yyyy'),
            ],
        );
    }
}
