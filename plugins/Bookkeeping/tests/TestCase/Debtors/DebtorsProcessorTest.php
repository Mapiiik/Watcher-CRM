<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Debtors;

use App\Model\Enum\IpAddressTypeOfUse;
use App\Model\Enum\IpNetworkTypeOfUse;
use Bookkeeping\Debtors\Debtor;
use Bookkeeping\Debtors\DebtorsProcessor;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionMethod;

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
     * Contract the addresses from the fixtures hang on.
     *
     * @var string
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

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
        'app.IpAddresses',
        'app.IpNetworks',
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

    /**
     * Blocking writes addresses into a firewall list, and a technology address is our own
     * equipment held under the customer. Writing one would cut off the device rather than
     * the service, so only what is the customer's may come out.
     *
     * VIP is switched off here so that the fixture's contracts, which are VIP, do not hide
     * what is being asked about.
     *
     * @return void
     * @link \Bookkeeping\Debtors\DebtorsProcessor::getCustomerIps()
     */
    public function testOnlyTheCustomersOwnAddressesAreBlocked(): void
    {
        $addresses = $this->fetchTable('IpAddresses');
        $addresses->saveOrFail($addresses->newEntity([
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => self::CONTRACT_ID,
            'ip_address' => '192.168.99.99',
            'type_of_use' => IpAddressTypeOfUse::TechnologyManually,
        ]));

        $networks = $this->fetchTable('IpNetworks');
        $networks->saveOrFail($networks->newEntity([
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => self::CONTRACT_ID,
            'ip_network' => '172.16.0.0/24',
            'type_of_use' => IpNetworkTypeOfUse::TechnologyManually,
        ]));

        $method = new ReflectionMethod(DebtorsProcessor::class, 'getCustomerIps');
        /** @var array{ipv4: array<string, string>, ipv6: array<string, string>} $ips */
        $ips = $method->invoke(new DebtorsProcessor(), self::CUSTOMER_ID, '', false);

        $this->assertSame(['192.168.11.11', '10.0.0.0/8'], array_keys($ips['ipv4']));
        $this->assertSame([], $ips['ipv6']);
    }
}
