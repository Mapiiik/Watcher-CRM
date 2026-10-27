<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Command;

use Bookkeeping\Command\SendPartnersCommand;
use Bookkeeping\Provider\Pohoda\PohodaProvider;
use Cake\Cache\Cache;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Settings\Utility\Settings;

/**
 * Bookkeeping\Command\SendPartnersCommand Test Case
 *
 * A run hands the customers to the accounting system, which a test is no place to reach. What can
 * be answered without it is the name and the options a cron entry names the command by, and the
 * branches that return before any of it: a run with nobody to send, and a run where nobody is to be
 * synchronized.
 */
#[UsesClass(SendPartnersCommand::class)]
class SendPartnersCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * Fixtures
     *
     * The customers are here only because the one test that lets the command see them turns their
     * synchronization off first: with a customer left to send, the run reaches the accounting
     * system, which a test is no place to do.
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Emails',
        'app.Phones',
        'plugin.Settings.Settings',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear('default');
        Settings::set('bookkeeping.accounting.default_provider', 'pohoda');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Cache::clear('default');

        parent::tearDown();
    }

    /**
     * The name a cron entry calls the command by.
     *
     * @return void
     * @link \Bookkeeping\Command\SendPartnersCommand::defaultName()
     */
    public function testDefaultName(): void
    {
        $this->assertSame('send_partners', SendPartnersCommand::defaultName());
    }

    /**
     * The command says what it does in the list of commands, which is where an operator looks.
     *
     * @return void
     * @link \Bookkeeping\Command\SendPartnersCommand::getDescription()
     */
    public function testGetDescription(): void
    {
        $this->assertNotEmpty(SendPartnersCommand::getDescription());

        $this->exec('send_partners --help');

        $this->assertExitSuccess();
        $this->assertOutputContains(SendPartnersCommand::getDescription());
    }

    /**
     * Every option is still there. They are what narrows a run down to one customer or one band of
     * customer numbers, so a renamed one widens a scheduled run into sending more than it was meant
     * to.
     *
     * @return void
     * @link \Bookkeeping\Command\SendPartnersCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->exec('send_partners --help');

        $this->assertExitSuccess();
        $this->assertOutputContains('--customer-id');
        $this->assertOutputContains('--min-customer-number');
        $this->assertOutputContains('--max-customer-number');
    }

    /**
     * Nobody to send - here because nobody falls in the band of customer numbers the run asks for -
     * is a quiet run rather than a failed one, and one that reaches for nothing.
     *
     * @return void
     * @link \Bookkeeping\Command\SendPartnersCommand::execute()
     */
    public function testExecuteWithNobodyToSend(): void
    {
        $this->exec('send_partners --min-customer-number 999999999');

        $this->assertExitSuccess();
        $this->assertErrorContains('No customers to send.');
    }

    /**
     * A customer who is not to be synchronized is walked past rather than sent, which is the whole
     * point of the flag: one partner the accounting system will not take used to end the run for
     * everybody behind them.
     *
     * The customers can be loaded here precisely because none of them is to be sent, so the run
     * still reaches for nothing.
     *
     * @return void
     * @link \Bookkeeping\Command\SendPartnersCommand::execute()
     */
    public function testExecuteSkipsCustomersNotToBeSynchronized(): void
    {
        $customers = $this->getTableLocator()->get('Customers');
        $customers->updateAll(['sync_to_accounting' => false], []);

        $this->exec('send_partners');

        $this->assertExitSuccess();
        $this->assertOutputContains('Skipping customer');
        $this->assertErrorContains('No customers to send.');
    }

    /**
     * The option may raise the number the partner cards are ours from, never take a run below it:
     * there the run would set a second card beside every one made by hand.
     *
     * @return void
     * @link \Bookkeeping\Command\SendPartnersCommand::execute()
     */
    public function testTheOptionDoesNotGoBelowTheSetting(): void
    {
        Settings::set(PohodaProvider::SETTINGS_ROOT . '.customers.min_customer_number', 999999999);

        $this->exec('send_partners --min-customer-number 1');

        $this->assertExitSuccess();
        $this->assertOutputContains('greater than or equal to: 999999999');
        $this->assertErrorContains('No customers to send.');
    }

    /**
     * Nor does naming one customer.
     *
     * @return void
     * @link \Bookkeeping\Command\SendPartnersCommand::execute()
     */
    public function testOneCustomerBelowTheSettingIsNotSent(): void
    {
        Settings::set(PohodaProvider::SETTINGS_ROOT . '.customers.min_customer_number', 999999999);

        $this->exec('send_partners -c 403bab0e-52cd-4a8e-83f8-43c2457d0481');

        $this->assertExitSuccess();
        $this->assertErrorContains('No customers to send.');
    }
}
