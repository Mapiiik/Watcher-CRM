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
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionMethod;
use ReflectionProperty;
use Settings\Utility\Settings;

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
     * The label a blocked customer is marked with.
     *
     * @var string
     */
    private const LABEL_ID = 'e9cbb697-be8b-4e05-8226-1b0aeb53130d';

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
        'app.ContractVersions',
        'app.Emails',
        'app.Phones',
        'app.IpAddresses',
        'app.IpNetworks',
        'app.Labels',
        'app.CustomerLabels',
        'plugin.Settings.Settings',
        'plugin.Bookkeeping.Invoices',
    ];

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // The processor keeps the debtors it has loaded in a static, which outlives the test
        // that loaded them. A case that changes what is owed would otherwise be answered out
        // of whatever ran before it.
        (new ReflectionProperty(DebtorsProcessor::class, 'debtors'))->setValue(null, null);
    }

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

    /**
     * A running service with no signed contract is cut off by this same pass, because the
     * address list on the router is wiped and rebuilt here - a pass of its own would undo
     * this one rather than add to it. Which is also why the pass works the set out for
     * itself: a caller that handed it in would be one of two callers, and the other would
     * quietly reconnect everybody.
     *
     * @return void
     * @link \Bookkeeping\Debtors\DebtorsProcessor::blockingUpdate()
     */
    public function testUnsignedPaperworkIsBlockedBesideTheDebtors(): void
    {
        $this->onlyLabelling();
        $this->unsignedPaperwork();
        $this->fetchTable('Bookkeeping.Invoices')->deleteAll([]);

        (new DebtorsProcessor())->blockingUpdate();

        /** @var list<\App\Model\Entity\CustomerLabel> $labels */
        $labels = $this->fetchTable('CustomerLabels')->find()->all()->toList();

        $this->assertCount(1, $labels);
        $this->assertSame('unsigned contract', $labels[0]->note);
    }

    /**
     * Switched off, the pass is the debtor pass it always was.
     *
     * @return void
     * @link \Bookkeeping\Debtors\DebtorsProcessor::blockingUpdate()
     */
    public function testUnsignedPaperworkIsLeftAloneWhileItsBlockingIsOff(): void
    {
        $this->onlyLabelling();
        $this->unsignedPaperwork();
        Settings::set('core.contracts.unsigned.blocking.enabled', false);
        $this->fetchTable('Bookkeeping.Invoices')->deleteAll([]);

        (new DebtorsProcessor())->blockingUpdate();

        $this->assertSame(0, $this->fetchTable('CustomerLabels')->find()->count());
    }

    /**
     * And somebody who owes money and is also missing their paperwork is one blocked
     * customer, not two. Two labels would take two things to clear.
     *
     * @return void
     * @link \Bookkeeping\Debtors\DebtorsProcessor::addLabel()
     */
    public function testBeingBlockedTwiceOverStillLeavesOneLabel(): void
    {
        $this->onlyLabelling();
        $this->unsignedPaperwork();

        // the fixture invoice already makes this customer a debtor
        (new DebtorsProcessor())->blockingUpdate();

        /** @var list<\App\Model\Entity\CustomerLabel> $labels */
        $labels = $this->fetchTable('CustomerLabels')->find()->all()->toList();

        $this->assertCount(1, $labels);
        $this->assertSame('debtor', $labels[0]->note, 'The debt is what they pay off to be let back in.');
    }

    /**
     * Blocking switched on, but neither of the things it reaches out to. What is left is the
     * labelling, which is the part these cases are about - and nothing leaves the machine.
     *
     * @return void
     */
    private function onlyLabelling(): void
    {
        Settings::set('bookkeeping.debtors.blocking.enabled', true);
        Settings::set('bookkeeping.debtors.blocking.services.sledovani_tv.enabled', false);
        Settings::set('bookkeeping.debtors.blocking.services.routers.enabled', false);
        Settings::set('bookkeeping.debtors.blocking.blocked_label_id', self::LABEL_ID);
    }

    /**
     * Give the fixture contract a version nobody signed, long enough ago to be cut off for.
     *
     * @return void
     */
    private function unsignedPaperwork(): void
    {
        Settings::set('core.contracts.unsigned.blocking.enabled', true);
        Settings::set('core.contracts.unsigned.blocking.after_installation_days', 10);
        Settings::set('core.contracts.unsigned.blocking.after_valid_from_days', 20);
        Settings::set('core.contracts.unsigned.consider_from', '2020-01-01');

        $versions = $this->fetchTable('ContractVersions');
        $versions->saveOrFail($versions->newEntity([
            'contract_id' => self::CONTRACT_ID,
            'valid_from' => Date::today()->subDays(60),
            'conclusion_date' => null,
            'number_of_amendments' => 0,
            'obligations_settled' => false,
        ]));
    }
}
