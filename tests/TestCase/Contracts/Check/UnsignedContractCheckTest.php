<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Contracts\Check\UnsignedContractCheck;
use App\Contracts\Unsigned\UnsignedPaperwork;
use App\Model\Enum\UnsignedDeadlineAnchor;
use App\Model\Table\ContractVersionsTable;
use Cake\Cache\Cache;
use Cake\Chronos\Chronos;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Settings\Utility\Settings;

/**
 * App\Contracts\Check\UnsignedContractCheck Test Case
 *
 * What is on file has to be able to show what the customer agreed to, which needs paper from
 * about the time the version took effect.
 */
#[UsesClass(UnsignedContractCheck::class)]
class UnsignedContractCheckTest extends TestCase
{
    use LocatorAwareTrait;

    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * The day an import wrote where nobody knew when a version began.
     */
    private const START_NOT_KNOWN = '1800-01-01';

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

        Cache::clear('default');

        // Said rather than inherited. A stored setting outlives the test that stored it, so
        // where the line falls has to be this test's own answer and not whatever ran before.
        Settings::set('core.contracts.unsigned.consider_from', '2026-01-01');
        Settings::set(UnsignedDeadlineAnchor::SETTINGS_PATH, UnsignedDeadlineAnchor::Installation->value);
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
     * @return void
     * @link \App\Contracts\Check\UnsignedContractCheck::find()
     */
    public function testAVersionWithNoConclusionDateIsReported(): void
    {
        $this->agreed(valid_from: '2024-01-01', conclusion_date: null);

        $this->assertCount(1, $this->found());
    }

    /**
     * Paper from long before the version it is meant to be behind is what carrying the
     * previous version's date onto a new one looks like.
     *
     * @return void
     * @link \App\Contracts\Check\UnsignedContractCheck::find()
     */
    public function testAVersionConcludedLongBeforeItTookEffectIsReported(): void
    {
        $this->agreed('2024-01-01', '2022-06-01');

        $this->assertCount(1, $this->found());
    }

    /**
     * Signed shortly before it starts is how a contract is signed.
     *
     * @return void
     * @link \App\Contracts\Check\UnsignedContractCheck::find()
     */
    public function testAVersionConcludedShortlyBeforeItTookEffectIsNotReported(): void
    {
        $this->agreed('2024-01-01', '2023-12-15');

        $this->assertSame([], $this->found());
    }

    /**
     * A thousand versions came out of an import with a start nobody knows and no paper at all.
     * Comparing their dates says nothing, but having no paper still does - so they are counted
     * for that and left alone for the rest.
     *
     * @return void
     * @link \App\Contracts\Check\UnsignedContractCheck::find()
     */
    public function testAVersionWhoseStartNobodyKnowsIsStillCountedForHavingNoPaper(): void
    {
        $this->agreed(self::START_NOT_KNOWN, null);

        $this->assertCount(1, $this->found());
    }

    /**
     * The same version with paper on it cannot be judged by how old the paper is, because
     * there is no day to judge it against.
     *
     * @return void
     * @link \App\Contracts\Check\AbstractContractCheck::knownDate()
     */
    public function testAVersionWhoseStartNobodyKnowsIsNotJudgedByHowOldItsPaperIs(): void
    {
        $this->agreed(self::START_NOT_KNOWN, '2024-01-01');

        $this->assertSame([], $this->found());
    }

    /**
     * How old the paper may be is a matter of how the company works, so it is asked of the
     * settings rather than settled here.
     *
     * @return void
     * @link \App\Contracts\Check\UnsignedContractCheck::find()
     */
    public function testHowOldThePaperMayBeComesFromTheSettings(): void
    {
        $this->agreed('2024-01-01', '2023-09-01');

        // four months before it took effect is beyond the three the settings ship with
        $this->assertCount(1, $this->found());

        Settings::set('core.contracts.checks.signature_expected_within_months', 12);

        $this->assertSame([], $this->found(), 'The check did not ask the settings again.');
    }

    /**
     * The day's work is the running services whose wait has actually run out.
     *
     * @return void
     * @link \App\Contracts\Check\UnsignedContractCheck::find()
     */
    public function testAVersionPastItsDeadlineIsTheDaysWork(): void
    {
        $this->onDay('2026-06-01', function (): void {
            // the fixture contract went in in 2022, so only the version's own wait is in
            // question, and twenty days of it are up
            $this->agreed(valid_from: '2026-05-01', conclusion_date: null);

            $this->assertCount(1, $this->foundToday());
        });
    }

    /**
     * A version that has only just taken effect is late for nothing yet, but it is still the
     * day's work: it is where a phone call settles the matter before any deadline makes it
     * somebody's problem. Its standing is what the listing reads off the deadlines.
     *
     * @return void
     * @link \App\Contracts\Check\UnsignedContractCheck::find()
     */
    public function testAVersionInsideItsDeadlineIsStillTheDaysWork(): void
    {
        $this->onDay('2026-06-01', function (): void {
            $this->agreed(valid_from: '2026-05-25', conclusion_date: null);

            $found = $this->foundToday();

            $this->assertCount(1, $found);
            $this->assertNotNull($found[0]->notify_due);
            $this->assertNotNull($found[0]->block_due);
            $this->assertTrue($found[0]->notify_due->isFuture(), 'It is not due a reminder yet.');
            $this->assertTrue($found[0]->block_due->isFuture(), 'Nor a disconnection.');
        });
    }

    /**
     * And a version well past both waits says so on the same two fields.
     *
     * @return void
     * @link \App\Contracts\Unsigned\UnsignedPaperwork::withDeadlines()
     */
    public function testTheDaysWorkCarriesBothDeadlines(): void
    {
        $this->onDay('2026-06-01', function (): void {
            $this->agreed(valid_from: '2026-01-10', conclusion_date: null);

            $found = $this->foundToday();

            $this->assertCount(1, $found);
            $this->assertNotNull($found[0]->notify_due);
            $this->assertNotNull($found[0]->block_due);
            // ten days on from the version taking effect, and twenty
            $this->assertSame('2026-01-20', $found[0]->notify_due->format('Y-m-d'));
            $this->assertSame('2026-01-30', $found[0]->block_due->format('Y-m-d'));
        });
    }

    /**
     * The whole file carries the deadlines too, because a contract's own card asks the wider
     * question and a finding there still has to say whether anything is about to happen.
     *
     * @return void
     * @link \App\Contracts\Check\UnsignedContractCheck::find()
     */
    public function testTheHistoryCarriesTheDeadlinesToo(): void
    {
        $this->onDay('2026-06-01', function (): void {
            $this->agreed(valid_from: '2026-01-10', conclusion_date: null);

            $found = $this->found();

            $this->assertCount(1, $found);
            $this->assertNotNull($found[0]->notify_due);
            $this->assertSame('2026-01-20', $found[0]->notify_due->format('Y-m-d'));
        });
    }

    /**
     * But not against a version the automation is never going to reach. A deadline printed
     * on one of those promises a disconnection that is not coming - and the whole file is
     * full of them, which is what the wider reading is for.
     *
     * @return void
     * @link \App\Contracts\Unsigned\UnsignedPaperwork::withDeadlines()
     */
    public function testTheHistoryLeavesTheDeadlinesEmptyWhereNothingWillHappen(): void
    {
        $this->onDay('2026-06-01', function (): void {
            // replaced by a later version, so nobody is running a service on this one
            $this->agreed(valid_from: '2026-01-10', valid_until: '2026-02-01', conclusion_date: null);

            $found = $this->found();

            $this->assertCount(1, $found, 'Its paperwork is still worth putting straight.');
            $this->assertNull($found[0]->notify_due);
            $this->assertNull($found[0]->block_due);
        });
    }

    /**
     * And the same for a version from before the day the settings draw the line on, which is
     * what the thousand an import left behind all look like.
     *
     * @return void
     * @link \App\Contracts\Unsigned\UnsignedPaperwork::withDeadlines()
     */
    public function testTheHistoryLeavesTheDeadlinesEmptyBeforeTheLine(): void
    {
        $this->onDay('2026-06-01', function (): void {
            $this->agreed(valid_from: '2020-01-01', conclusion_date: null);

            $found = $this->found();

            $this->assertCount(1, $found);
            $this->assertNull($found[0]->notify_due);
            $this->assertNull($found[0]->block_due);
        });
    }

    /**
     * The thousand an import left behind are not work anybody is going to do, so the day's
     * work does not reach back past the day the settings draw the line on.
     *
     * @return void
     * @link \App\Contracts\Unsigned\UnsignedPaperwork::findDue()
     */
    public function testAVersionFromBeforeTheLineIsLeftToTheHistory(): void
    {
        $this->onDay('2026-06-01', function (): void {
            $this->agreed(valid_from: '2020-01-01', conclusion_date: null);

            $this->assertSame([], $this->foundToday());
            $this->assertCount(1, $this->found(), 'The history still has it.');
        });
    }

    /**
     * Paper carried over from the previous version is worth putting straight, but it is not
     * what anybody gets written to or cut off for: for that, the version has to say nothing
     * at all about when it was concluded.
     *
     * @return void
     * @link \App\Contracts\Check\UnsignedContractCheck::find()
     */
    public function testPaperCarriedOverFromAnEarlierVersionIsHistoryRatherThanTheDaysWork(): void
    {
        $this->onDay('2026-06-01', function (): void {
            $this->agreed(valid_from: '2026-05-01', conclusion_date: '2022-06-01');

            $this->assertSame([], $this->foundToday());
            $this->assertCount(1, $this->found());
        });
    }

    /**
     * Run something with the clock held still.
     *
     * @param string $day The day to hold it at.
     * @param \Closure $test What to run.
     * @return void
     */
    private function onDay(string $day, callable $test): void
    {
        $was = Chronos::getTestNow();
        Chronos::setTestNow(new Chronos($day . ' 09:00:00'));

        try {
            $test();
        } finally {
            Chronos::setTestNow($was);
        }
    }

    /**
     * Run the check over the whole file and return what it found.
     *
     * Lifting the filter is what asks the check its wider question - every version with
     * nothing behind it, deadlines and running services beside the point. Every case above
     * is about that reading, which is the one somebody putting the history straight wants.
     *
     * @return list<\App\Model\Entity\ContractVersion>
     */
    private function found(): array
    {
        return $this->check(ignore_inactive: false);
    }

    /**
     * Run the check the way the dashboard does, and return what it found.
     *
     * @return list<\App\Model\Entity\ContractVersion>
     */
    private function foundToday(): array
    {
        return $this->check(ignore_inactive: true);
    }

    /**
     * @param bool $ignore_inactive Whether the check keeps to the day's work.
     * @return list<\App\Model\Entity\ContractVersion>
     */
    private function check(bool $ignore_inactive): array
    {
        $check = new UnsignedContractCheck(
            $this->ContractVersions,
            new UnsignedPaperwork($this->ContractVersions),
            $ignore_inactive,
        );

        /** @var list<\App\Model\Entity\ContractVersion> $records */
        $records = $check->find()->all()->toList();

        return $records;
    }

    /**
     * Agree a version of the contract on a day, with paper from another.
     *
     * @param string $valid_from The day the version takes effect.
     * @param string|null $conclusion_date The day it was concluded, or null for no paper.
     * @return void
     */
    private function agreed(string $valid_from, ?string $conclusion_date, ?string $valid_until = null): void
    {
        $this->ContractVersions->saveOrFail($this->ContractVersions->newEntity([
            'contract_id' => self::CONTRACT_ID,
            'valid_from' => $valid_from,
            'valid_until' => $valid_until,
            'conclusion_date' => $conclusion_date,
            'number_of_amendments' => 0,
            'obligations_settled' => false,
        ]));
    }
}
