<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
use Cake\Database\Log\LoggedQuery;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use Stringable;

/**
 * The order a listing opens in, and what its customer number is sorted by.
 *
 * @link \App\Controller\AppController::newestFirst()
 * @link \App\Controller\AppController::sortableWithCustomerNumber()
 */
class ListingOrderTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

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
        'app.ConnectionProfiles',
        'app.Services',
        'app.Contracts',
        'app.Billings',
        'app.EquipmentTypes',
        'app.BorrowedEquipments',
        'app.SoldEquipments',
        'app.Labels',
        'app.CustomerLabels',
        'app.DealerCommissions',
        'app.Emails',
        'app.Phones',
        'app.IpAddresses',
        'app.RemovedIpAddresses',
        'app.IpNetworks',
        'app.RemovedIpNetworks',
        'app.Logins',
        'app.AccessCredentials',
        'plugin.Radius.Accounts',
    ];

    /**
     * The listings that open by when a record was added.
     *
     * @return array<string, array{string, string}>
     */
    public static function newestFirstProvider(): array
    {
        return [
            'addresses' => ['/addresses', 'Addresses'],
            'billings' => ['/billings', 'Billings'],
            'borrowed equipments' => ['/borrowed-equipments', 'BorrowedEquipments'],
            'customer labels' => ['/customer-labels', 'CustomerLabels'],
            'dealer commissions' => ['/dealer-commissions', 'DealerCommissions'],
            'emails' => ['/emails', 'Emails'],
            'ip addresses' => ['/ip-addresses', 'IpAddresses'],
            'ip networks' => ['/ip-networks', 'IpNetworks'],
            'logins' => ['/logins', 'Logins'],
            'phones' => ['/phones', 'Phones'],
            'removed ip addresses' => ['/removed-ip-addresses', 'RemovedIpAddresses'],
            'removed ip networks' => ['/removed-ip-networks', 'RemovedIpNetworks'],
            'sold equipments' => ['/sold-equipments', 'SoldEquipments'],
            'radius accounts' => ['/radius/accounts', 'Radius.Accounts'],
        ];
    }

    /**
     * The listings with a column of the customer number.
     *
     * @return array<string, array{string, string}>
     */
    public static function customerNumberProvider(): array
    {
        return [
            'access credentials' => ['/access-credentials', 'AccessCredentials'],
            'addresses' => ['/addresses', 'Addresses'],
            'billings' => ['/billings', 'Billings'],
            'borrowed equipments' => ['/borrowed-equipments', 'BorrowedEquipments'],
            'contracts' => ['/contracts', 'Contracts'],
            'customer labels' => ['/customer-labels', 'CustomerLabels'],
            'emails' => ['/emails', 'Emails'],
            'ip addresses' => ['/ip-addresses', 'IpAddresses'],
            'ip networks' => ['/ip-networks', 'IpNetworks'],
            'logins' => ['/logins', 'Logins'],
            'phones' => ['/phones', 'Phones'],
            'removed ip addresses' => ['/removed-ip-addresses', 'RemovedIpAddresses'],
            'removed ip networks' => ['/removed-ip-networks', 'RemovedIpNetworks'],
            'sold equipments' => ['/sold-equipments', 'SoldEquipments'],
        ];
    }

    /**
     * An id is random, so it says nothing about what is new. What has no date goes last, or the
     * undated records would fill the first pages. The id keeps the pages apart.
     *
     * @param string $url The listing.
     * @param string $table Table alias.
     * @return void
     */
    #[DataProvider('newestFirstProvider')]
    public function testAListingOpensWithTheNewest(string $url, string $table): void
    {
        $alias = $this->getTableLocator()->get($table)->getAlias();

        $this->assertStringContainsString(
            'ORDER BY ' . $alias . '.created DESC NULLS LAST, ' . $alias . '.id DESC',
            $this->queriesOf($url, $table),
        );
    }

    /**
     * Contracts are numbered as they are added, so the number says what is new.
     *
     * @return void
     * @link \App\Controller\ContractsController::index()
     */
    public function testContractsOpenWithTheNewest(): void
    {
        $this->assertStringContainsStringIgnoringCase(
            'ORDER BY Contracts.nid DESC',
            $this->queriesOf('/contracts', 'Contracts'),
        );
    }

    /**
     * Records with a date come newest first, and those without any after them.
     *
     * @return void
     * @link \App\Controller\PhonesController::index()
     */
    public function testWhatHasNoDateComesLast(): void
    {
        $phones = $this->getTableLocator()->get('Phones');
        $fixture = $phones->find()->firstOrFail();
        $phones->updateAll(['created' => null], ['id' => $fixture->get('id')]);

        foreach (['+420 601 000 001' => '2026-01-01', '+420 601 000 002' => '2026-06-01'] as $phone => $added) {
            $record = $phones->newEntity(['customer_id' => $fixture->get('customer_id'), 'phone' => $phone]);
            $phones->saveOrFail($record);
            $phones->updateAll(['created' => new DateTime($added)], ['id' => $record->get('id')]);
        }

        $this->login();
        $this->get('/phones');
        $this->assertResponseOk();

        /** @var iterable<\App\Model\Entity\Phone> $listed */
        $listed = $this->viewVariable('phones');
        $ids = [];
        foreach ($listed as $phone) {
            $ids[] = $phone->phone;
        }

        $this->assertSame(['+420 601 000 002', '+420 601 000 001', $fixture->get('phone')], $ids);
    }

    /**
     * Sorting by a column is not held back by the order a listing opens in.
     *
     * @return void
     * @link \App\Controller\AppController::newestFirst()
     */
    public function testSortingByAColumnTakesOver(): void
    {
        $queries = $this->queriesOf('/phones?sort=phone&direction=asc', 'Phones');

        $this->assertStringContainsStringIgnoringCase('ORDER BY Phones.phone asc', $queries);
        $this->assertStringNotContainsString('NULLS LAST', $queries);
    }

    /**
     * The column of the customer number goes by that number, not by the id of the customer.
     *
     * @param string $url The listing.
     * @param string $table Table alias.
     * @return void
     */
    #[DataProvider('customerNumberProvider')]
    public function testACustomerNumberIsSortedByTheNumber(string $url, string $table): void
    {
        $queries = $this->queriesOf($url . '?sort=Customers.nid&direction=desc', $table);

        $this->assertStringContainsStringIgnoringCase('ORDER BY Customers.nid desc', $queries);

        // asked of the plain listing, as a sorted one carries the same words in its page links
        $this->get($url);
        $this->assertResponseContains('sort=Customers.nid');
    }

    /**
     * The columns of the table stay sortable beside the customer number.
     *
     * @return void
     * @link \App\Controller\AppController::sortableWithCustomerNumber()
     */
    public function testTheOtherColumnsStaySortable(): void
    {
        $queries = $this->queriesOf('/contracts?sort=number&direction=asc', 'Contracts');

        $this->assertStringContainsStringIgnoringCase('ORDER BY Contracts.number asc', $queries);
    }

    /**
     * What the listing asked the database, with the page itself having answered.
     *
     * @param string $url The listing.
     * @param string $table Alias of the table it lists.
     * @return string
     */
    private function queriesOf(string $url, string $table): string
    {
        $connection = $this->getTableLocator()->get($table)->getConnection();

        $queries = '';
        $connection->getDriver()->setLogger(new class ($queries) extends AbstractLogger {
            /**
             * @param string $queries What was asked.
             */
            public function __construct(private string &$queries)
            {
            }

            /**
             * @inheritDoc
             */
            public function log($level, string|Stringable $message, array $context = []): void
            {
                $query = $context['query'] ?? null;
                if ($query instanceof LoggedQuery) {
                    $this->queries .= (string)($query->jsonSerialize()['query'] ?? '') . "\n";
                }
            }
        });
        $connection->getDriver()->enableQueryLogging();

        $this->login();
        $this->get($url);

        $connection->getDriver()->disableQueryLogging();
        $connection->getDriver()->setLogger(new NullLogger());

        $this->assertResponseOk();

        return $queries;
    }
}
