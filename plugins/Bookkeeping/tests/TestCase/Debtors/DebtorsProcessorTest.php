<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Debtors;

use App\Messages\Message;
use App\Model\Enum\IpAddressTypeOfUse;
use App\Model\Enum\IpNetworkTypeOfUse;
use ArrayObject;
use Bookkeeping\Debtors\Debtor;
use Bookkeeping\Debtors\DebtorsProcessor;
use Cake\Core\Configure;
use Cake\Http\Client\Response;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Http\Message\RequestInterface;
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
    use HttpClientTrait;
    use LocatorAwareTrait;

    /**
     * Where the agent listens.
     *
     * @var string
     */
    private const AGENT = 'https://agent.example.com';

    /**
     * A router the agent cannot reach.
     *
     * @var string
     */
    private const ROUTER_DOWN = '10.0.0.1';

    /**
     * A router the agent changes.
     *
     * @var string
     */
    private const ROUTER_UP = '10.0.0.2';

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
        'app.Users',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.ContractVersions',
        'app.ConnectionProfiles',
        'app.Services',
        'app.Billings',
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

        // The state in the fixtures says blocked, which would put every contract in them
        // into the blocked set for that reason alone. Cases about some other reason start
        // from a state that says nothing; the case about this one turns it back on.
        $this->fetchTable('ContractStates')->updateAll(['blocked' => false], ['1 = 1']);
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
     * address list on the router is made to hold exactly this set - a pass of its own would undo
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
     * A service running on no version at all is cut off beside them, which is the case the proposals
     * made ordinary: the billings are in so that the line runs, and the papers wait on a proposal.
     *
     * @return void
     * @link \Bookkeeping\Debtors\DebtorsProcessor::blockingUpdate()
     */
    public function testAServiceWithNoVersionIsBlockedBesideTheDebtors(): void
    {
        $this->onlyLabelling();
        $this->unsignedPaperwork();
        Settings::set('core.contracts.paperwork.unsigned.thresholds.without_version', true);
        $this->fetchTable('ContractVersions')->deleteAll(['1 = 1']);
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
        Settings::set('core.contracts.paperwork.unsigned.blocking.enabled', false);
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
     * A contract state has carried a flag saying blocked since it was written, and the office
     * has been able to set it all along, but nothing ever read it - a contract could sit in a
     * state called blocked and go on serving. Now it is what the operator says it is.
     *
     * @return void
     * @link \Bookkeeping\Debtors\DebtorsProcessor::statedAsBlocked()
     */
    public function testAStateThatSaysBlockedBlocks(): void
    {
        $this->onlyLabelling();
        $this->fetchTable('Bookkeeping.Invoices')->deleteAll([]);
        $this->fetchTable('ContractStates')->updateAll(['blocked' => true], ['1 = 1']);

        (new DebtorsProcessor())->blockingUpdate();

        /** @var list<\App\Model\Entity\CustomerLabel> $labels */
        $labels = $this->fetchTable('CustomerLabels')->find()->all()->toList();

        $this->assertCount(1, $labels);
        $this->assertSame('blocked state', $labels[0]->note);
    }

    /**
     * The routers are changed by the agent, which makes the list on each of them hold exactly
     * whoever is blocked. A router that cannot be reached does not hold up the next one, and the
     * operator is told which it was beside what was done on the others.
     *
     * @return void
     * @link \Bookkeeping\Debtors\DebtorsProcessor::updateRouters()
     */
    public function testTheBlockingUpdateSyncsEveryRouterThroughTheAgent(): void
    {
        $this->throughTheAgent(self::ROUTER_DOWN . ' ' . self::ROUTER_UP);
        $sent = $this->agentAnswers('sync', [
            self::ROUTER_DOWN => $this->agentAnswer(502, [
                'error' => 'routeros_unreachable',
                'message' => 'no connection to the router',
            ]),
            self::ROUTER_UP => $this->agentAnswer(200, [
                'host' => self::ROUTER_UP,
                'transport' => 'api-ssl',
                'added' => [['address' => '192.168.11.11', 'family' => 'ipv4', 'comment' => '1 - Customer']],
                'updated' => [],
                'removed' => [['address' => '2001:db8::1/128', 'family' => 'ipv6', 'comment' => 'paid']],
                'unchanged' => 1,
            ]),
        ]);

        $processor = new DebtorsProcessor();
        $processor->blockingUpdate();

        $this->assertSame([self::ROUTER_DOWN, self::ROUTER_UP], array_column($sent->getArrayCopy(), 'host'));
        foreach ($sent as $request) {
            $this->assertSame('debtors', $request['list']);
            $this->assertSame(['192.168.11.11', '10.0.0.0/8'], array_column($request['entries'], 'address'));
        }

        $error = $this->onlyMessage($processor, 'error');
        $this->assertStringContainsString('RouterOS device 10.0.0.1:', $error);
        $this->assertStringContainsString('no connection to the router', $error);
        $this->assertStringContainsString('Added IPv4 record 192.168.11.11 (1 - Customer) to router 10.0.0.2.', $error);
        $this->assertStringContainsString('Removed IPv6 record 2001:db8::1/128 from router 10.0.0.2.', $error);
    }

    /**
     * Blocking one customer by hand puts their addresses on the list and leaves the rest of it
     * alone, and letting them back in takes the same addresses off.
     *
     * @return void
     * @link \Bookkeeping\Debtors\DebtorsProcessor::updateRouters()
     */
    public function testOneCustomerIsAddedAndRemovedThroughTheAgent(): void
    {
        $this->throughTheAgent(self::ROUTER_UP);
        $nothing = ['added' => [], 'updated' => [], 'removed' => [], 'unchanged' => 2];
        $added = $this->agentAnswers('add', [self::ROUTER_UP => $this->agentAnswer(200, $nothing)]);
        $removed = $this->agentAnswers('remove', [self::ROUTER_UP => $this->agentAnswer(200, [
            'removed' => [['address' => '10.0.0.0/8', 'family' => 'ipv4', 'comment' => 'MANUAL ENTRY']],
        ] + $nothing)]);

        $processor = new DebtorsProcessor();
        $processor->block(self::CUSTOMER_ID);
        $processor->unblock(self::CUSTOMER_ID);

        $this->assertCount(1, $added);
        $this->assertSame(['192.168.11.11', '10.0.0.0/8'], array_column($added[0]['entries'], 'address'));
        foreach ($added[0]['entries'] as $entry) {
            $this->assertStringStartsWith('MANUAL ENTRY - ', $entry['comment']);
        }

        $this->assertCount(1, $removed);
        $this->assertSame(['192.168.11.11', '10.0.0.0/8'], $removed[0]['addresses']);

        $messages = array_filter(
            $processor->getMessages()->getMessages(),
            fn(Message $message): bool => $message->type === 'error',
        );
        $this->assertSame([], $messages);
    }

    /**
     * Routers switched on and changed through an agent that answers as it is told to.
     *
     * The fixture contracts are VIP, which the nightly pass leaves alone, so they are made
     * ordinary here.
     *
     * @param string $routers The routers, as the configuration lists them.
     * @return void
     */
    private function throughTheAgent(string $routers): void
    {
        $this->onlyLabelling();
        Settings::set('bookkeeping.debtors.blocking.services.routers.enabled', true);
        Settings::set('bookkeeping.debtors.blocking.services.routers.address_list', 'debtors');

        Configure::write('Bookkeeping.debtors.routersIpAddresses', $routers);
        Configure::write('Agent.url', self::AGENT);
        Configure::write('Agent.token', 'agent-token');

        $this->fetchTable('Contracts')->updateAll(['vip' => false], ['1 = 1']);
    }

    /**
     * Have the agent answer an address list operation per router, writing down what it was asked.
     *
     * @param string $operation `sync`, `add` or `remove`.
     * @param array<string, \Cake\Http\Client\Response> $answers What it answers, by router.
     * @return \ArrayObject<int, mixed>
     */
    private function agentAnswers(string $operation, array $answers): ArrayObject
    {
        /** @var \ArrayObject<int, mixed> $sent */
        $sent = new ArrayObject();

        foreach ($answers as $router => $answer) {
            $this->mockClientPost(
                self::AGENT . '/api/routeros/address-list/' . $operation,
                $answer,
                ['match' => function (RequestInterface $request) use ($router, $sent): bool {
                    $body = json_decode((string)$request->getBody(), true);
                    if (!is_array($body) || ($body['host'] ?? null) !== $router) {
                        return false;
                    }
                    $sent[] = $body;

                    return true;
                }],
            );
        }

        return $sent;
    }

    /**
     * @param int $status What the agent answers with.
     * @param array<string, mixed> $body What it says.
     * @return \Cake\Http\Client\Response
     */
    private function agentAnswer(int $status, array $body): Response
    {
        return $this->newClientResponse($status, ['Content-Type: application/json'], (string)json_encode($body));
    }

    /**
     * The one message of the type, which there has to be.
     *
     * @param \Bookkeeping\Debtors\DebtorsProcessor $processor What processed.
     * @param string $type The type of message.
     * @return string
     */
    private function onlyMessage(DebtorsProcessor $processor, string $type): string
    {
        $messages = array_values(array_filter(
            $processor->getMessages()->getMessages(),
            fn(Message $message): bool => $message->type === $type,
        ));
        $this->assertCount(1, $messages);

        return $messages[0]->text;
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
        Settings::set('core.contracts.paperwork.unsigned.blocking.enabled', true);
        Settings::set('core.contracts.paperwork.unsigned.blocking.after_anchor_days', 10);
        Settings::set('core.contracts.paperwork.unsigned.blocking.after_start_days', 20);
        Settings::set('core.contracts.paperwork.consider_from', '2020-01-01');
        // The other kind is its own case below, so it is left out of the ones about a version.
        Settings::set('core.contracts.paperwork.unsigned.thresholds.without_version', false);

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
