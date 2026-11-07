<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Check;

use App\Check\CheckScope;
use App\Model\Enum\IpAddressTypeOfUse;
use App\Model\Enum\IpNetworkTypeOfUse;
use App\Model\Table\ContractsTable;
use App\Model\Table\IpAddressesTable;
use App\Model\Table\IpNetworksTable;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Radius\Check\AddressBlock;
use Radius\Check\RadiusAddressOutsideContractCheck;
use Radius\Check\TariffComparison;
use Radius\Model\Enum\AccountType;
use Radius\Model\Table\AccountsTable;
use Radius\Model\Table\RadreplyTable;

/**
 * Radius\Check\RadiusAddressOutsideContractCheck Test Case
 */
#[CoversClass(RadiusAddressOutsideContractCheck::class)]
#[CoversClass(TariffComparison::class)]
#[CoversClass(AddressBlock::class)]
class RadiusAddressOutsideContractCheckTest extends TestCase
{
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    private const OTHER_CONTRACT_ID = '9c0d5e5c-2a6b-4f8e-9a3d-1b7c4e2f6a90';

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

        // every case writes the accounts and the addresses it is about
        $this->fetchTable(AccountsTable::class)->deleteAll(['1 = 1']);
        $this->fetchTable(RadreplyTable::class)->deleteAll(['1 = 1']);
        $this->fetchTable(IpAddressesTable::class)->deleteAll(['1 = 1']);
        $this->fetchTable(IpNetworksTable::class)->deleteAll(['1 = 1']);
    }

    /**
     * The collision the check is there for: a contract left without an address keeps what its
     * account was given, and the address has gone to somebody else in the meantime.
     *
     * @return void
     * @link \Radius\Check\TariffComparison::outsideTheirContract()
     */
    public function testAnAddressOfAnotherContractIsReportedWithThatContract(): void
    {
        $this->address('192.0.2.10', self::OTHER_CONTRACT_ID);
        $this->account('alice', ['Framed-IP-Address' => '192.0.2.10']);

        $findings = $this->findings();

        $this->assertCount(1, $findings);
        $this->assertSame('alice', $findings[0]->account->username);
        $this->assertSame(['192.0.2.10'], array_keys($findings[0]->stray_addresses));
        $this->assertSame(self::OTHER_CONTRACT_ID, $findings[0]->stray_addresses['192.0.2.10']?->id);
    }

    /**
     * An address nobody has is outside the contract too, it has only not hurt anybody yet.
     *
     * @return void
     * @link \Radius\Check\TariffComparison::outsideTheirContract()
     */
    public function testAnAddressNobodyHasIsReported(): void
    {
        $this->account('alice', ['Framed-IP-Address' => '192.0.2.10']);

        $findings = $this->findings();

        $this->assertCount(1, $findings);
        $this->assertSame(['192.0.2.10' => null], $findings[0]->stray_addresses);
    }

    /**
     * What the contract has is nothing to report, whichever way it was written and whatever it is
     * used for on the contract.
     *
     * @param string $attribute What hands the address out.
     * @param string $value What it hands out.
     * @return void
     * @link \Radius\Check\TariffComparison::outsideTheirContract()
     */
    #[TestWith(['Framed-IP-Address', '192.0.2.10'])]
    #[TestWith(['Framed-IP-Address', '198.51.100.9'])]
    #[TestWith(['Framed-Route', '198.51.100.8/29'])]
    #[TestWith(['Framed-Route', '198.51.100.8/29 0.0.0.0 1'])]
    #[TestWith(['Framed-IPv6-Address', '2001:0DB8::1'])]
    #[TestWith(['Framed-IPv6-Prefix', '2001:db8:1::/48'])]
    #[TestWith(['Delegated-IPv6-Prefix', '2001:db8:1:2::/64'])]
    public function testWhatTheContractHasIsNotReported(string $attribute, string $value): void
    {
        $this->address('192.0.2.10', self::CONTRACT_ID);
        $this->address('2001:db8::1', self::CONTRACT_ID);
        $this->network('198.51.100.8/29', self::CONTRACT_ID);
        $this->network('2001:db8:1::/48', self::CONTRACT_ID);
        $this->account('alice', [$attribute => $value]);

        $this->assertSame([], $this->findings());
    }

    /**
     * A network reaching past what the contract has is not inside it, nor is its neighbour.
     *
     * @param string $attribute What hands the address out.
     * @param string $value What it hands out.
     * @return void
     * @link \Radius\Check\TariffComparison::outsideTheirContract()
     */
    #[TestWith(['Framed-Route', '198.51.100.0/24'])]
    #[TestWith(['Framed-Route', '198.51.100.8/28'])]
    #[TestWith(['Framed-Route', '203.0.113.9 0.0.0.0 1'])]
    #[TestWith(['Framed-IP-Address', '198.51.100.16'])]
    #[TestWith(['Framed-IPv6-Prefix', '2001:db8:2::/48'])]
    public function testWhatGoesPastTheContractIsReported(string $attribute, string $value): void
    {
        $this->network('198.51.100.8/29', self::CONTRACT_ID);
        $this->network('2001:db8:1::/48', self::CONTRACT_ID);
        $this->account('alice', [$attribute => $value]);

        $this->assertCount(1, $this->findings());
    }

    /**
     * Replies that hand out no address say nothing about one, an unreadable value neither.
     *
     * @return void
     * @link \Radius\Check\TariffComparison::outsideTheirContract()
     */
    public function testOtherRepliesAreNotAddresses(): void
    {
        $this->account('alice', ['Mikrotik-Rate-Limit' => '192.0.2.10', 'Framed-IP-Address' => 'pool']);

        $this->assertSame([], $this->findings());
    }

    /**
     * An account switched off is handed nothing.
     *
     * @return void
     * @link \Radius\Check\TariffComparison::outsideTheirContract()
     */
    public function testAnInactiveAccountIsNotReported(): void
    {
        $this->account('alice', ['Framed-IP-Address' => '192.0.2.10'], active: false);

        $this->assertSame([], $this->findings());
    }

    /**
     * Asked about one contract, the check speaks of that one, and still knows who else holds it.
     *
     * @return void
     * @link \Radius\Check\TariffComparison::outsideTheirContract()
     */
    public function testAskedAboutOneContract(): void
    {
        $this->address('192.0.2.10', self::OTHER_CONTRACT_ID);
        $this->account('alice', ['Framed-IP-Address' => '192.0.2.10']);

        $this->assertCount(1, $this->findings(self::CONTRACT_ID));
        $this->assertSame(
            self::OTHER_CONTRACT_ID,
            $this->findings(self::CONTRACT_ID)[0]->stray_addresses['192.0.2.10']?->id,
        );
        $this->assertSame([], $this->findings(self::OTHER_CONTRACT_ID));
    }

    /**
     * An active account on the contract of the customer under test.
     *
     * @param string $username Username.
     * @param array<string, string> $replies What FreeRADIUS answers with, by attribute.
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
                        fn(string $attribute, string $value): array => [
                            'attribute' => $attribute,
                            'op' => '=',
                            'value' => $value,
                        ],
                        array_keys($replies),
                        $replies,
                    ),
                ],
                ['validate' => false, 'accessibleFields' => ['*' => true]],
            ),
            ['checkRules' => false],
        );
    }

    /**
     * An address on a contract.
     *
     * @param string $address The address.
     * @param string $contract_id The contract.
     * @return void
     */
    private function address(string $address, string $contract_id): void
    {
        $addresses = $this->fetchTable(IpAddressesTable::class);
        $addresses->saveOrFail(
            $addresses->newEntity(
                [
                    'ip_address' => $address,
                    'type_of_use' => IpAddressTypeOfUse::CustomerManually,
                    'customer_id' => self::CUSTOMER_ID,
                    'contract_id' => $contract_id,
                ],
                ['validate' => false, 'accessibleFields' => ['*' => true]],
            ),
            ['checkRules' => false],
        );
    }

    /**
     * A network on a contract.
     *
     * @param string $network The network.
     * @param string $contract_id The contract.
     * @return void
     */
    private function network(string $network, string $contract_id): void
    {
        $networks = $this->fetchTable(IpNetworksTable::class);
        $networks->saveOrFail(
            $networks->newEntity(
                [
                    'ip_network' => $network,
                    'type_of_use' => IpNetworkTypeOfUse::CustomerManually,
                    'customer_id' => self::CUSTOMER_ID,
                    'contract_id' => $contract_id,
                ],
                ['validate' => false, 'accessibleFields' => ['*' => true]],
            ),
            ['checkRules' => false],
        );
    }

    /**
     * What the check reports.
     *
     * @param string|null $contract_id One contract to ask about.
     * @return list<\Radius\Check\AccountFinding>
     */
    private function findings(?string $contract_id = null): array
    {
        $check = new RadiusAddressOutsideContractCheck(
            $this->fetchTable(ContractsTable::class),
            new CheckScope(contract_id: $contract_id),
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
