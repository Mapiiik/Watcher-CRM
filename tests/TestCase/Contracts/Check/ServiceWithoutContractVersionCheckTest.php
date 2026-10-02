<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Check\CheckScope;
use App\Contracts\Check\ServiceWithoutContractVersionCheck;
use App\Contracts\Unsigned\UnsignedPaperwork;
use App\Model\Table\ContractVersionsTable;
use Cake\Cache\Cache;
use Cake\Chronos\Chronos;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use Settings\Utility\Settings;

/**
 * App\Contracts\Check\ServiceWithoutContractVersionCheck Test Case
 *
 * The service is running and charged for and no contract version covers it. The narrow question is
 * the day's work - what the office watches from the day it draws its line - and the wide one is the
 * whole file, which on a real installation is the backlog an import left behind.
 */
#[CoversClass(ServiceWithoutContractVersionCheck::class)]
class ServiceWithoutContractVersionCheckTest extends TestCase
{
    use LocatorAwareTrait;

    /**
     * The fixture contract, charged for since 2021-11-05 and with no version on file.
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * The day the cases are asked on.
     */
    private const TODAY = '2026-06-01';

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
        'app.ConnectionProfiles',
        'app.Services',
        'app.Billings',
        'plugin.Settings.Settings',
    ];

    private ContractVersionsTable $ContractVersions;

    private ?Chronos $clock = null;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->ContractVersions = $this->getTableLocator()
            ->get('ContractVersions', ['className' => ContractVersionsTable::class]);
        $this->ContractVersions->deleteAll(['1 = 1']);

        Cache::clear('default');

        $this->clock = Chronos::getTestNow();
        Chronos::setTestNow(new Chronos(self::TODAY . ' 09:00:00'));

        Settings::set('core.contracts.paperwork.consider_from', '2020-01-01');
    }

    /**
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Chronos::setTestNow($this->clock);
        Cache::clear('default');

        parent::tearDown();
    }

    /**
     * A service nobody has drawn a version for is the day's work.
     *
     * @return void
     */
    public function testAServiceWithNoVersionIsTheDaysWork(): void
    {
        $this->assertContains(self::CONTRACT_ID, $this->found());
    }

    /**
     * Held to the day the office watches from, it is not - which is how a thousand contracts an
     * import left behind stay out of the daily work.
     *
     * @return void
     */
    public function testOneChargedForSinceBeforeTheWatchedDayIsNotTheDaysWork(): void
    {
        Settings::set('core.contracts.paperwork.consider_from', '2026-01-01');

        $this->assertNotContains(self::CONTRACT_ID, $this->found());
    }

    /**
     * The wider question is the whole file, line or no line.
     *
     * @return void
     */
    public function testTheWiderQuestionIgnoresTheLine(): void
    {
        Settings::set('core.contracts.paperwork.consider_from', '2026-01-01');

        $this->assertContains(self::CONTRACT_ID, $this->found(ignore_inactive: false));
    }

    /**
     * A version in force answers for the service, so this check says nothing about it - the one
     * about unsigned versions does.
     *
     * @return void
     */
    public function testAContractWithAVersionInForceIsNotAFinding(): void
    {
        $this->ContractVersions->saveOrFail($this->ContractVersions->newEntity([
            'contract_id' => self::CONTRACT_ID,
            'valid_from' => '2021-11-05',
            'conclusion_date' => '2021-11-01',
            'number_of_amendments' => 0,
            'obligations_settled' => false,
        ]));

        $this->assertNotContains(self::CONTRACT_ID, $this->found());
        $this->assertNotContains(self::CONTRACT_ID, $this->found(ignore_inactive: false));
    }

    /**
     * What the check finds, as contract ids.
     *
     * @param bool $ignore_inactive Whether to keep to the day's work.
     * @return list<string>
     */
    private function found(bool $ignore_inactive = true): array
    {
        /** @var list<string> $ids */
        $ids = (new ServiceWithoutContractVersionCheck(
            new UnsignedPaperwork($this->ContractVersions),
            new CheckScope($ignore_inactive),
        ))
            ->find()
            ->all()
            ->extract('id')
            ->toList();

        return $ids;
    }
}
