<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Check;

use App\Check\CheckScope;
use App\Model\Table\ContractsTable;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use Radius\Check\RadiusCompetingAddressesCheck;
use Radius\Check\TariffComparison;
use Radius\Model\Enum\AccountType;
use Radius\Model\Enum\AddressAttribute;
use Radius\Model\Table\AccountsTable;
use Radius\Model\Table\RadreplyTable;

/**
 * Radius\Check\RadiusCompetingAddressesCheck Test Case
 */
#[CoversClass(RadiusCompetingAddressesCheck::class)]
#[CoversClass(TariffComparison::class)]
#[CoversClass(AddressAttribute::class)]
class RadiusCompetingAddressesCheckTest extends TestCase
{
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

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
        'app.IpAddresses',
        'app.IpNetworks',
        'plugin.Radius.Accounts',
        'plugin.Radius.Radreply',
        'plugin.Radius.Radusergroup',
    ];

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // every case writes the accounts it is about
        $this->fetchTable(AccountsTable::class)->deleteAll(['1 = 1']);
        $this->fetchTable(RadreplyTable::class)->deleteAll(['1 = 1']);
    }

    /**
     * The case the check is there for: an old address left on the contract beside the new one,
     * and the account handed both.
     *
     * @return void
     * @link \Radius\Check\TariffComparison::withCompetingAddresses()
     */
    public function testTwoAddressesOfAFamilyAreReported(): void
    {
        $this->account('alice', [
            ['Framed-IP-Address', '192.0.2.18'],
            ['Framed-IP-Address', '192.0.2.245'],
        ]);

        $findings = $this->findings();

        $this->assertCount(1, $findings);
        $this->assertSame('alice', $findings[0]->account->username);
        $this->assertEqualsCanonicalizing(['192.0.2.18', '192.0.2.245'], $findings[0]->competing_addresses);
    }

    /**
     * Two IPv6 addresses compete the same way, and only those are listed, not the IPv4 one beside
     * them.
     *
     * @return void
     * @link \Radius\Check\TariffComparison::withCompetingAddresses()
     */
    public function testTwoIpv6AddressesAreReportedWithoutTheIpv4One(): void
    {
        $this->account('alice', [
            ['Framed-IP-Address', '192.0.2.10'],
            ['Framed-IPv6-Address', '2001:db8::1'],
            ['Framed-IPv6-Address', '2001:db8::2'],
        ]);

        $findings = $this->findings();

        $this->assertCount(1, $findings);
        $this->assertEqualsCanonicalizing(['2001:db8::1', '2001:db8::2'], $findings[0]->competing_addresses);
    }

    /**
     * One address of each family, any number of networks, and one address written twice are what
     * a connection is meant to have.
     *
     * @return void
     * @link \Radius\Check\TariffComparison::withCompetingAddresses()
     */
    public function testWhatAConnectionIsMeantToHaveIsNotReported(): void
    {
        $this->account('alice', [
            ['Framed-IP-Address', '192.0.2.10'],
            ['Framed-IPv6-Address', '2001:db8::1'],
            ['Framed-IPv6-Address', '2001:0DB8::1'],
            ['Framed-Route', '198.51.100.8/29'],
            ['Framed-Route', '203.0.113.0/29'],
            ['Framed-IPv6-Prefix', '2001:db8:1::/48'],
            ['Framed-IPv6-Prefix', '2001:db8:2::/48'],
            ['Delegated-IPv6-Prefix', '2001:db8:1::/48'],
            ['Delegated-IPv6-Prefix', '2001:db8:2::/48'],
        ]);

        $this->assertSame([], $this->findings());
    }

    /**
     * Replies that hand out no address are not addresses, an unreadable value neither.
     *
     * @return void
     * @link \Radius\Check\TariffComparison::withCompetingAddresses()
     */
    public function testWhatIsNoAddressIsNotCounted(): void
    {
        $this->account('alice', [
            ['Framed-IP-Address', '192.0.2.10'],
            ['Framed-IP-Address', 'pool'],
            ['Mikrotik-Rate-Limit', '192.0.2.11'],
        ]);

        $this->assertSame([], $this->findings());
    }

    /**
     * An account switched off is handed nothing.
     *
     * @return void
     * @link \Radius\Check\TariffComparison::withCompetingAddresses()
     */
    public function testAnInactiveAccountIsNotReported(): void
    {
        $this->account('alice', [
            ['Framed-IP-Address', '192.0.2.10'],
            ['Framed-IP-Address', '192.0.2.11'],
        ], active: false);

        $this->assertSame([], $this->findings());
    }

    /**
     * An active account on the contract of the customer under test.
     *
     * @param string $username Username.
     * @param list<array{0: string, 1: string}> $replies What FreeRADIUS answers with, as attribute and value.
     * @param bool $active Whether the account is active.
     * @return void
     */
    private function account(string $username, array $replies, bool $active = true): void
    {
        $accounts = $this->fetchTable(AccountsTable::class);
        $accounts->saveOrFail(
            $accounts->newEntity(
                [
                    'username' => $username,
                    'password' => 'secret',
                    'type' => AccountType::PPPoE,
                    'active' => $active,
                    'customer_id' => self::CUSTOMER_ID,
                    'contract_id' => self::CONTRACT_ID,
                    'radreply' => array_map(
                        fn(array $reply): array => ['attribute' => $reply[0], 'op' => '=', 'value' => $reply[1]],
                        $replies,
                    ),
                ],
                ['validate' => false, 'accessibleFields' => ['*' => true]],
            ),
            ['checkRules' => false],
        );
    }

    /**
     * What the check reports.
     *
     * @return list<\Radius\Check\AccountFinding>
     */
    private function findings(): array
    {
        $check = new RadiusCompetingAddressesCheck(
            $this->fetchTable(ContractsTable::class),
            new CheckScope(),
        );

        $findings = [];
        foreach ($check->find()->all() as $contract) {
            /** @var list<\Radius\Check\AccountFinding> $found */
            $found = $contract->get('radius_findings');
            array_push($findings, ...$found);
        }

        return $findings;
    }
}
