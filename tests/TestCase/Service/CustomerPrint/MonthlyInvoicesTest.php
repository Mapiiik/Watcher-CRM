<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\CustomerPrint;

use App\Model\Entity\Billing;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractState;
use App\Model\Entity\Customer;
use App\Model\Entity\ServiceType;
use App\Service\CustomerPrint\MonthlyInvoices;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use PhpCollective\DecimalObject\Decimal;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * What the list of services tells the customer to pay has to be what the invoices ask for, so it
 * is split the way the bookkeeping splits them.
 */
#[UsesClass(MonthlyInvoices::class)]
class MonthlyInvoicesTest extends TestCase
{
    /**
     * The day the invoices are worked out for.
     */
    private Date $day;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->day = new Date('2026-09-19');
    }

    /**
     * What is not invoiced on its own goes on one invoice together, whichever contract it is on.
     *
     * @return void
     */
    public function testWhatIsNotSeparateGoesOnOneInvoice(): void
    {
        $invoices = (new MonthlyInvoices())->of($this->customer([
            $this->contract('A', [$this->billing('350')]),
            $this->contract('B', [$this->billing('250'), $this->billing('18')]),
        ]), $this->day);

        $this->assertCount(1, $invoices);
        $this->assertNull($invoices[0]->contract);
        $this->assertSame('618', $invoices[0]->total->trim()->toString());
    }

    /**
     * A contract whose service type is invoiced separately has an invoice of its own, and the
     * common one comes last.
     *
     * @return void
     */
    public function testASeparatelyInvoicedContractHasItsOwnInvoice(): void
    {
        $invoices = (new MonthlyInvoices())->of($this->customer([
            $this->contract('A', [$this->billing('350')]),
            $this->contract('TV', [$this->billing('259')], separate: true),
        ]), $this->day);

        $this->assertCount(2, $invoices);
        $this->assertSame('TV', $invoices[0]->contract?->number);
        $this->assertSame('259', $invoices[0]->total->trim()->toString());
        $this->assertNull($invoices[1]->contract);
        $this->assertSame('350', $invoices[1]->total->trim()->toString());
    }

    /**
     * A billing invoiced on its own leaves the rest of its contract where it was.
     *
     * @return void
     */
    public function testASeparatelyInvoicedBillingHasItsOwnInvoice(): void
    {
        $separate = $this->billing('100', separate: true);

        $invoices = (new MonthlyInvoices())->of($this->customer([
            $this->contract('A', [$this->billing('350'), $separate]),
        ]), $this->day);

        $this->assertCount(2, $invoices);
        $this->assertSame($separate, $invoices[0]->billing);
        $this->assertSame('100', $invoices[0]->total->trim()->toString());
        $this->assertSame('350', $invoices[1]->total->trim()->toString());
    }

    /**
     * Only what is billed on the day is paid for: not a billing that starts later, not one that
     * has ended, and nothing on a contract whose state is not billed.
     *
     * @return void
     */
    public function testOnlyWhatIsBilledOnTheDayCounts(): void
    {
        $invoices = (new MonthlyInvoices())->of($this->customer([
            $this->contract('A', [
                $this->billing('350'),
                $this->billing('500', from: '2026-10-01'),
                $this->billing('70', until: '2026-08-31'),
            ]),
            $this->contract('Waiting', [$this->billing('999')], billed: false),
        ]), $this->day);

        $this->assertCount(1, $invoices);
        $this->assertSame('350', $invoices[0]->total->trim()->toString());
    }

    /**
     * Nothing billed is no invoice at all, rather than one for nothing.
     *
     * @return void
     */
    public function testNothingBilledIsNoInvoice(): void
    {
        $invoices = (new MonthlyInvoices())->of($this->customer([
            $this->contract('A', []),
        ]), $this->day);

        $this->assertSame([], $invoices);
    }

    /**
     * What is paid changes on the day a service starts and on the day after one ends, and on no
     * day before the list is drawn up or on a contract that is not billed.
     *
     * @return void
     */
    public function testWhatIsPaidChangesWhereAServiceStartsOrEnds(): void
    {
        $days = (new MonthlyInvoices())->changesAfter($this->customer([
            $this->contract('A', [
                $this->billing('350', until: '2026-10-31'),
                $this->billing('400', from: '2026-11-01'),
                $this->billing('70', from: '2026-12-01', until: '2027-05-31'),
            ]),
            $this->contract('Waiting', [$this->billing('999', from: '2026-10-15')], billed: false),
        ]), $this->day);

        $this->assertSame(
            ['2026-11-01', '2026-12-01', '2027-06-01'],
            array_map(fn(Date $day): string => $day->toDateString(), $days),
        );
    }

    /**
     * @param list<\App\Model\Entity\Contract> $contracts Their contracts.
     * @return \App\Model\Entity\Customer
     */
    private function customer(array $contracts): Customer
    {
        return new Customer(['contracts' => $contracts]);
    }

    /**
     * @param string $number Its number.
     * @param list<\App\Model\Entity\Billing> $billings What it bills.
     * @param bool $separate Whether its service type is invoiced separately.
     * @param bool $billed Whether its state is billed.
     * @return \App\Model\Entity\Contract
     */
    private function contract(string $number, array $billings, bool $separate = false, bool $billed = true): Contract
    {
        return new Contract([
            'number' => $number,
            'billings' => $billings,
            'service_type' => new ServiceType(['separate_invoice' => $separate]),
            'contract_state' => new ContractState(['billed' => $billed, 'active_services' => true]),
        ]);
    }

    /**
     * @param string $price Its monthly price.
     * @param bool $separate Whether it is invoiced on its own.
     * @param string $from Its first day.
     * @param string|null $until Its last day.
     * @return \App\Model\Entity\Billing
     */
    private function billing(
        string $price,
        bool $separate = false,
        string $from = '2026-01-01',
        ?string $until = null,
    ): Billing {
        return new Billing([
            'price' => Decimal::create($price),
            'quantity' => 1,
            'separate_invoice' => $separate,
            'billing_from' => new Date($from),
            'billing_until' => $until === null ? null : new Date($until),
        ]);
    }
}
