<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Check\CheckScope;
use App\Contracts\Check\ImplausibleConclusionDateCheck;
use App\Model\Table\ContractVersionsTable;
use App\View\AppView;
use Authorization\AuthorizationServiceInterface;
use Cake\Cache\Cache;
use Cake\Http\ServerRequest;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Settings\Utility\Settings;

/**
 * App\Contracts\Check\ImplausibleConclusionDateCheck Test Case
 *
 * A version is concluded at most three months before it takes effect and at most three after.
 * What lands outside that is usually a mistyped year.
 */
#[UsesClass(ImplausibleConclusionDateCheck::class)]
class ImplausibleConclusionDateCheckTest extends TestCase
{
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

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
        'plugin.Settings.Settings',
    ];

    private ContractVersionsTable $ContractVersions;

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

        // said rather than inherited: a stored setting outlives the test that stored it
        Cache::clear('default');
        Settings::set('core.contracts.checks.concluded_in_advance_months', 3);
        Settings::set('core.contracts.checks.concluded_retroactively_months', 3);
    }

    /**
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Cache::clear('default');

        parent::tearDown();
    }

    /**
     * The year the version began, written as the current one: the slip this is here for.
     *
     * @return void
     * @link \App\Contracts\Check\ImplausibleConclusionDateCheck::find()
     */
    public function testAConclusionYearsAfterTheStartIsReported(): void
    {
        $this->agreed(valid_from: '2020-09-01', valid_until: null, conclusion_date: '2026-09-01');

        $this->assertCount(1, $this->found());
    }

    /**
     * Signed some months ahead, or some weeks after the service started, is the ordinary paper.
     *
     * @return void
     * @link \App\Contracts\Check\ImplausibleConclusionDateCheck::find()
     */
    public function testAConclusionAroundTheStartIsNotReported(): void
    {
        $this->agreed(valid_from: '2020-09-01', valid_until: null, conclusion_date: '2020-11-15');
        $this->agreed(valid_from: '2021-09-01', valid_until: null, conclusion_date: '2021-08-20');
        $this->agreed(valid_from: '2022-09-01', valid_until: null, conclusion_date: '2022-07-15');

        $this->assertSame([], $this->found());
    }

    /**
     * Signed more than three months after the service started is no longer a late signature.
     *
     * @return void
     * @link \App\Contracts\Check\ImplausibleConclusionDateCheck::find()
     */
    public function testAConclusionMonthsAfterTheStartIsReported(): void
    {
        $this->agreed(valid_from: '2020-09-01', valid_until: null, conclusion_date: '2021-01-15');

        $this->assertCount(1, $this->found());
    }

    /**
     * Nor is signing more than three months ahead, which is also what the previous version's date
     * carried onto a new one looks like.
     *
     * @return void
     * @link \App\Contracts\Check\ImplausibleConclusionDateCheck::find()
     */
    public function testAConclusionLongBeforeTheStartIsReported(): void
    {
        $this->agreed(valid_from: '2022-09-01', valid_until: null, conclusion_date: '2022-04-15');

        $this->assertCount(1, $this->found());
    }

    /**
     * How far from the start a version may be concluded is how the company works, so it is asked
     * of the settings.
     *
     * @return void
     * @link \App\Contracts\Check\ImplausibleConclusionDateCheck::find()
     */
    public function testHowFarFromTheStartComesFromTheSettings(): void
    {
        $this->agreed(valid_from: '2022-09-01', valid_until: null, conclusion_date: '2022-04-15');
        $this->agreed(valid_from: '2020-09-01', valid_until: null, conclusion_date: '2021-01-15');
        $this->assertCount(2, $this->found());

        Settings::set('core.contracts.checks.concluded_in_advance_months', 12);
        Settings::set('core.contracts.checks.concluded_retroactively_months', 12);

        $this->assertSame([], $this->found(), 'The check did not ask the settings again.');
    }

    /**
     * A version cannot be agreed to once it is over.
     *
     * @return void
     * @link \App\Contracts\Check\ImplausibleConclusionDateCheck::find()
     */
    public function testAConclusionAfterTheEndIsReported(): void
    {
        $this->agreed(valid_from: '2020-09-01', valid_until: '2020-10-31', conclusion_date: '2020-12-01');

        $this->assertCount(1, $this->found());
    }

    /**
     * Nor signed on a day that has not come.
     *
     * @return void
     * @link \App\Contracts\Check\ImplausibleConclusionDateCheck::find()
     */
    public function testAConclusionStillAheadIsReported(): void
    {
        $tomorrow = Date::today()->addDays(1)->toDateString();
        $this->agreed(valid_from: $tomorrow, valid_until: null, conclusion_date: $tomorrow);

        $this->assertCount(1, $this->found());
    }

    /**
     * A start nobody knows says nothing about when the version was signed.
     *
     * @return void
     * @link \App\Contracts\Check\ImplausibleConclusionDateCheck::find()
     */
    public function testAStartStandingForNotKnownIsPassedOver(): void
    {
        $this->agreed(valid_from: '1800-01-01', valid_until: null, conclusion_date: '2020-09-01');

        $this->assertSame([], $this->found());
    }

    /**
     * A finding is drawn with the days it was found by and the contract it belongs to.
     *
     * @return void
     */
    public function testAFindingIsDrawnWithItsDays(): void
    {
        $this->agreed(valid_from: '2020-09-01', valid_until: null, conclusion_date: '2026-09-01');
        $this->loadRoutes();

        $allowed = $this->createStub(AuthorizationServiceInterface::class);
        $allowed->method('can')->willReturn(true);
        $view = new AppView((new ServerRequest())->withAttribute('authorization', $allowed));

        $drawn = $view->element('ContractChecks/implausible_conclusion_date', ['records' => $this->found()]);

        $this->assertStringContainsString((string)new Date('2020-09-01'), $drawn);
        $this->assertStringContainsString((string)new Date('2026-09-01'), $drawn);
        $this->assertStringContainsString('/contracts/' . self::CONTRACT_ID, $drawn);
    }

    /**
     * Run the check and return what it found.
     *
     * @return list<\App\Model\Entity\ContractVersion>
     */
    private function found(): array
    {
        /** @var list<\App\Model\Entity\ContractVersion> $records */
        $records = (new ImplausibleConclusionDateCheck($this->ContractVersions, new CheckScope(false)))
            ->find()
            ->all()
            ->toList();

        return $records;
    }

    /**
     * Agree a version of the contract.
     *
     * @param string $valid_from When the version begins.
     * @param string|null $valid_until When it ends, or null for an open end.
     * @param string $conclusion_date When it was concluded.
     * @return void
     */
    private function agreed(string $valid_from, ?string $valid_until, string $conclusion_date): void
    {
        $this->ContractVersions->saveOrFail(
            $this->ContractVersions->newEntity([
                'contract_id' => self::CONTRACT_ID,
                'valid_from' => $valid_from,
                'valid_until' => $valid_until,
                'conclusion_date' => $conclusion_date,
                'number_of_amendments' => 0,
            ]),
            // the versions here say only what the check reads, whatever else a version is held to
            ['checkRules' => false],
        );
    }
}
