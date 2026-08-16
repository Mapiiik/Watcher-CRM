<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Debtors;

use Bookkeeping\Debtors\Debtor;
use Bookkeeping\Debtors\DebtorsProcessor;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Bookkeeping\Debtors\DebtorsProcessor Test Case
 *
 * The dashboard counts debtors with an aggregate rather than by loading every unpaid
 * invoice. That is a second expression of the same thresholds, so what matters is that the
 * two keep agreeing.
 */
#[UsesClass(DebtorsProcessor::class)]
class DebtorsProcessorTest extends TestCase
{
    use LocatorAwareTrait;

    /**
     * Customer the invoice from the fixture belongs to.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.Emails',
        'app.Phones',
        'plugin.Bookkeeping.Invoices',
    ];

    /**
     * The aggregate finds the same debtor the hydrating read does.
     *
     * @return void
     * @link \Bookkeeping\Debtors\DebtorsProcessor::findFilteredOverdueDebtorIds()
     */
    public function testTheAggregateAgreesWithTheHydratingRead(): void
    {
        $processor = new DebtorsProcessor(
            allowed_payment_delay: 0,
            allowed_total_overdue_debt: 0,
        );

        /** @var list<string> $expected */
        $expected = $processor->getFilteredOverdueDebtors()
            ->map(fn(Debtor $debtor): string => $debtor->getCustomer()->id)
            ->toList();

        $actual = array_column($processor->findFilteredOverdueDebtorIds()->toArray(), 'customer_id');

        sort($expected);
        sort($actual);

        $this->assertSame([self::CUSTOMER_ID], $expected);
        $this->assertSame($expected, $actual);
        $this->assertSame(count($expected), $processor->countFilteredOverdueDebtors());
    }

    /**
     * A debt below the tolerated amount is left out by both readings alike.
     *
     * @return void
     * @link \Bookkeeping\Debtors\DebtorsProcessor::findFilteredOverdueDebtorIds()
     */
    public function testAnAmountBelowTheToleranceIsLeftOut(): void
    {
        // the fixture invoice owes 1.5
        $processor = new DebtorsProcessor(
            allowed_payment_delay: 0,
            allowed_total_overdue_debt: 10.0,
        );

        $this->assertCount(0, $processor->getFilteredOverdueDebtors()->toList());
        $this->assertSame(0, $processor->countFilteredOverdueDebtors());
    }

    /**
     * An invoice not yet overdue by more than the tolerated delay is left out by both
     * readings alike. The delay is counted from today, so the invoice is written here
     * rather than in the fixture.
     *
     * @return void
     * @link \Bookkeeping\Debtors\DebtorsProcessor::findFilteredOverdueDebtorIds()
     */
    public function testADelayWithinTheToleranceIsLeftOut(): void
    {
        $invoices = $this->fetchTable('Bookkeeping.Invoices');
        $invoices->deleteAll([]);
        $invoices->saveOrFail($invoices->newEntity([
            'customer_id' => self::CUSTOMER_ID,
            'number' => '1/TEST/2026',
            'creation_date' => Date::today()->subDays(10),
            'due_date' => Date::today()->subDays(3),
            'total' => 100.0,
            'debt' => 100.0,
            'accounting_identifier' => 'test-within-tolerance',
        ]));

        $tolerant = new DebtorsProcessor(allowed_payment_delay: 30, allowed_total_overdue_debt: 0);
        $strict = new DebtorsProcessor(allowed_payment_delay: 0, allowed_total_overdue_debt: 0);

        $this->assertSame(0, $tolerant->countFilteredOverdueDebtors());
        $this->assertSame(1, $strict->countFilteredOverdueDebtors());
    }
}
