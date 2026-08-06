<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Command\UpdateHistoricalConnectionsCommand;
use App\Model\Enum\HistoricalConnectionSource;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use stdClass;

/**
 * App\Command\UpdateHistoricalConnectionsCommand Test Case
 *
 * What the run does with the sessions it reads is the updater's business and has tests of its own.
 * What is asked here is the shell around it: which sources a run gathers, and whether a source that
 * cannot be reached is allowed to pass for a quiet night.
 */
#[UsesClass(UpdateHistoricalConnectionsCommand::class)]
class UpdateHistoricalConnectionsCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

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
        'app.HistoricalConnections',
    ];

    /**
     * What the application ships as its sources, to be put back.
     *
     * @var mixed
     */
    private mixed $sourcesBefore = null;

    /**
     * The language the suite runs in, to be put back.
     *
     * @var string
     */
    private string $localeBefore = '';

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->sourcesBefore = Configure::read('HistoricalConnections.sources');

        // the suite runs in Czech, and what these tests read the run's output for is what the
        // source says rather than what a translator made of it
        $this->localeBefore = I18n::getLocale();
        I18n::setLocale('en_US');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Configure::write('HistoricalConnections.sources', $this->sourcesBefore);
        I18n::setLocale($this->localeBefore);

        parent::tearDown();
    }

    /**
     * The options a cron entry would name are there.
     *
     * @return void
     * @link \App\Command\UpdateHistoricalConnectionsCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->exec('historical_connections update --help');

        $this->assertExitSuccess();
        $this->assertOutputContains('--source');
    }

    /**
     * The application ships a source, and a run named on its own reads it.
     *
     * The accounts are deliberately left out of the fixtures: with one, the run reaches the RADIUS
     * database for its sessions, and what it makes of them is the updater's test to ask about.
     *
     * @return void
     * @link \App\Command\UpdateHistoricalConnectionsCommand::execute()
     */
    public function testExecuteReadsTheSourcesTheApplicationShips(): void
    {
        $this->exec('historical_connections update');

        $this->assertExitSuccess();
        // a run with a source to read prints the summary; one without returns before it
        $this->assertOutputContains('Accounts');
    }

    /**
     * Naming a source reads that one, and naming one nothing answers to reads none.
     *
     * @return void
     * @link \App\Command\UpdateHistoricalConnectionsCommand::buildSources()
     */
    public function testExecuteReadsOnlyTheSourceNamed(): void
    {
        $this->exec('historical_connections update --source ' . HistoricalConnectionSource::Radius->value);
        $this->assertExitSuccess();
        // a run with a source to read prints the summary; one without returns before it
        $this->assertOutputContains('Accounts');

        $this->exec('historical_connections update --source nonesuch');
        $this->assertExitSuccess();
        $this->assertErrorContains('No historical connections source is configured');
    }

    /**
     * With nothing configured to read, the run says so rather than reporting a quiet night.
     *
     * @return void
     * @link \App\Command\UpdateHistoricalConnectionsCommand::execute()
     */
    public function testExecuteSaysSoWhenThereIsNothingToReadFrom(): void
    {
        Configure::write('HistoricalConnections.sources', []);

        $this->exec('historical_connections update');

        $this->assertExitSuccess();
        $this->assertErrorContains('No historical connections source is configured');
    }

    /**
     * Something configured that is not a source is passed over rather than taken for one.
     *
     * @return void
     * @link \App\Command\UpdateHistoricalConnectionsCommand::buildSources()
     */
    public function testExecutePassesOverWhatIsNotASource(): void
    {
        Configure::write('HistoricalConnections.sources', ['App\\NoSuchSource', stdClass::class]);

        $this->exec('historical_connections update');

        $this->assertExitSuccess();
        $this->assertErrorContains('No historical connections source is configured');
    }
}
