<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Command\RebuildFulltextSearchCustomersCommand;
use App\Test\Traits\FulltextSearchCustomersTestTrait;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Command\RebuildFulltextSearchCustomersCommand Test Case
 *
 * The run is the safety net under everything the application writes as it saves, so what is asked
 * of it is that a customer nothing has touched can be searched for afterwards.
 */
#[UsesClass(RebuildFulltextSearchCustomersCommand::class)]
class RebuildFulltextSearchCustomersCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;
    use FulltextSearchCustomersTestTrait;

    /**
     * The customer the fixtures carry the related records for.
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
        'app.IpAddresses',
        'app.FulltextSearchCustomers',
    ];

    /**
     * The command says what it is for, which is what somebody writing a cron entry reads.
     *
     * @return void
     * @link \App\Command\RebuildFulltextSearchCustomersCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->exec('fulltext_search_customers rebuild --help');

        $this->assertExitSuccess();
        $this->assertOutputContains('advanced search');
    }

    /**
     * A run leaves every customer findable by what their records say, having been given nothing to
     * go on but the database.
     *
     * @return void
     * @link \App\Command\RebuildFulltextSearchCustomersCommand::execute()
     */
    public function testExecuteBuildsTheDocumentsFromTheDatabaseAlone(): void
    {
        // the address of the fixture IP address, which is on no column of the customer
        $this->assertSame([], $this->customersFoundBy('192.168.11.11'));

        $this->exec('fulltext_search_customers rebuild');

        $this->assertExitSuccess();
        $this->assertOutputContains('Documents');
        $this->assertContains(self::CUSTOMER_ID, $this->customersFoundBy('192.168.11.11'));
    }

    /**
     * The name a cron entry would call it by.
     *
     * @return void
     * @link \App\Command\RebuildFulltextSearchCustomersCommand::defaultName()
     */
    public function testDefaultName(): void
    {
        $this->assertSame('fulltext_search_customers rebuild', RebuildFulltextSearchCustomersCommand::defaultName());
    }
}
