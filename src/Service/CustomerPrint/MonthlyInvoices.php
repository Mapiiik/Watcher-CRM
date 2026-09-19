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
 * Split the way the bookkeeping splits them when it writes them
 * ({@see \Bookkeeping\Service\InvoiceGenerationService::generate()}), so that what a list of the
 * services tells the customer to pay is what the invoices will ask for. A service billed on its
 * own gets an invoice of its own, so does a contract whose service type is invoiced separately,
 * and the rest goes on one invoice together. Change one and the other has to follow.
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
        $invoices = [];
        $common = Decimal::create(0, 2);

        foreach ($customer->contracts ?? [] as $contract) {
            if (!$contract->billed) {
                continue;
            }

            $together = Decimal::create(0, 2);

            foreach ($this->billedOn($contract, $day) as $billing) {
                if ($billing->isSeparateInvoice() && !$billing->total_price->isZero()) {
                    $invoices[] = new MonthlyInvoice($billing->total_price, $contract, $billing);
                } else {
                    $together = $together->add($billing->total_price);
                }
            }

            if ($together->isZero()) {
                continue;
            }

            if ($contract->isSeparateInvoice()) {
                $invoices[] = new MonthlyInvoice($together, $contract);
            } else {
                $common = $common->add($together);
            }
        }

        if (!$common->isZero()) {
            $invoices[] = new MonthlyInvoice($common);
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
