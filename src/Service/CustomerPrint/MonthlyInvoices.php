<?php
declare(strict_types=1);

namespace App\Service\CustomerPrint;

use App\Model\Entity\Billing;
use App\Model\Entity\Contract;
use App\Model\Entity\Customer;
use Cake\I18n\Date;
use PhpCollective\DecimalObject\Decimal;

/**
 * The invoices a customer is sent each month, and what each of them comes to.
 *
 * {@see self::split()} is the rule, and the bookkeeping writes the invoices by the same one
 * ({@see \Bookkeeping\Service\InvoiceGenerationService::generate()}), so what a list of the
 * services tells the customer to pay is what the invoices will ask for.
 *
 * Only what is billed on the day counts: a contract in a state that is not billed, or a billing
 * that has not started yet, is not paid for yet.
 */
final class MonthlyInvoices
{
    /**
     * @param \App\Model\Entity\Customer $customer The customer, with their contracts, the contracts'
     *   states, service types and billings loaded.
     * @param \Cake\I18n\Date $day The day the invoices are worked out for.
     * @return list<\App\Service\CustomerPrint\MonthlyInvoice> The invoices, the common one last.
     */
    public function of(Customer $customer, Date $day): array
    {
        return $this->split(
            array_filter($customer->contracts ?? [], fn(Contract $contract): bool => (bool)$contract->billed),
            fn(Contract $contract): array => $this->billedOn($contract, $day),
            fn(Billing $billing): Decimal => $billing->total_price,
        );
    }

    /**
     * Which invoices the billings of a customer go on.
     *
     * A service billed on its own gets an invoice of its own, so does a contract whose service type
     * is invoiced separately, and the rest goes on one invoice together. What comes to nothing
     * stays with the rest, so it is still listed there.
     *
     * @param iterable<\App\Model\Entity\Contract> $contracts The contracts.
     * @param callable(\App\Model\Entity\Contract): iterable<\App\Model\Entity\Billing> $billingsOf
     *   Which billings of a contract count.
     * @param callable(\App\Model\Entity\Billing): \PhpCollective\DecimalObject\Decimal $amountOf
     *   What a billing comes to.
     * @return list<\App\Service\CustomerPrint\MonthlyInvoice> The invoices, the common one last.
     */
    public function split(iterable $contracts, callable $billingsOf, callable $amountOf): array
    {
        $invoices = [];
        $common = Decimal::create(0, 2);
        $commonItems = [];

        foreach ($contracts as $contract) {
            $together = Decimal::create(0, 2);
            $items = [];

            foreach ($billingsOf($contract) as $billing) {
                $amount = $amountOf($billing);

                if ($billing->isSeparateInvoice() && !$amount->isZero()) {
                    $invoices[] = new MonthlyInvoice($amount, $contract, $billing, [$billing]);
                } else {
                    $together = $together->add($amount);
                    $items[] = $billing;
                }
            }

            if ($contract->isSeparateInvoice() && !$together->isZero()) {
                $invoices[] = new MonthlyInvoice($together, $contract, items: $items);
            } else {
                $common = $common->add($together);
                $commonItems = array_merge($commonItems, $items);
            }
        }

        if (!$common->isZero()) {
            $invoices[] = new MonthlyInvoice($common, items: $commonItems);
        }

        return $invoices;
    }

    /**
     * The days after the given one on which what is billed changes: a billing starts, or the day
     * after one ends.
     *
     * @param \App\Model\Entity\Customer $customer The customer, loaded as for {@see of()}.
     * @param \Cake\I18n\Date $day The day to look ahead from.
     * @return list<\Cake\I18n\Date> In order, each once.
     */
    public function changesAfter(Customer $customer, Date $day): array
    {
        $days = [];

        foreach ($customer->contracts ?? [] as $contract) {
            if (!$contract->billed) {
                continue;
            }

            foreach ($contract->billings ?? [] as $billing) {
                $days[] = $billing->billing_from;
                if ($billing->billing_until !== null) {
                    $days[] = $billing->billing_until->addDays(1);
                }
            }
        }

        $after = [];
        foreach ($days as $one) {
            if ($one > $day) {
                $after[$one->toDateString()] = $one;
            }
        }
        ksort($after);

        return array_values($after);
    }

    /**
     * The billings of a contract that are running on the day.
     *
     * @param \App\Model\Entity\Contract $contract The contract.
     * @param \Cake\I18n\Date $day The day.
     * @return list<\App\Model\Entity\Billing>
     */
    private function billedOn(Contract $contract, Date $day): array
    {
        return array_values(array_filter(
            $contract->billings ?? [],
            static fn(Billing $billing): bool => $billing->billing_from <= $day
                && ($billing->billing_until === null || $billing->billing_until >= $day),
        ));
    }
}
