<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Unsigned;

use App\Contracts\Unsigned\UnsignedPaperwork;
use App\Model\Enum\ContractDeliveryMethod;
use App\Model\Enum\ProposalPurpose;
use App\Model\Enum\UnsignedDeadlineAnchor;
use App\Model\Table\ContractVersionsTable;
use Cake\Cache\Cache;
use Cake\Chronos\Chronos;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Settings\Utility\Settings;

/**
 * App\Contracts\Unsigned\UnsignedPaperwork Test Case
 *
 * A service is running, the customer is being billed for it, and nothing on file says the
 * customer ever agreed to any of it. How long that may go on, and what is counted from what,
 * is what these cases pin down - because the same query decides who gets written to and who
 * gets disconnected, and being wrong in either direction costs somebody something.
 */
#[UsesClass(UnsignedPaperwork::class)]
class UnsignedPaperworkTest extends TestCase
{
    use LocatorAwareTrait;

    /**
     * The fixture contract. It went in on 2022-11-28 and its state serves customers.
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * The day every case below is asked on.
     */
    private const TODAY = '2026-06-01';

    /**
     * The waits these cases work by: ten days since the service went in, twenty since the
     * version took effect.
     */
    private const AFTER_ANCHOR = 10;

    private const AFTER_VALID_FROM = 20;

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

    private UnsignedPaperwork $paperwork;

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

        $this->paperwork = new UnsignedPaperwork($this->ContractVersions);

        Cache::clear('default');
        Chronos::setTestNow(new Chronos(self::TODAY . ' 09:00:00'));

        // Said rather than inherited. The shipped defaults are the office's to move, and a
        // stored answer outlives the test that stored it, so every case starts from the same
        // two regardless of what ran before it or what the settings file says today.
        Settings::set(UnsignedDeadlineAnchor::SETTINGS_PATH, UnsignedDeadlineAnchor::Installation->value);
        Settings::set('core.contracts.unsigned.consider_from', '2020-01-01');
    }

    /**
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Chronos::setTestNow(Chronos::now());
        Cache::clear('default');

        parent::tearDown();
    }

    /**
     * @return void
     * @link \App\Contracts\Unsigned\UnsignedPaperwork::findDue()
     */
    public function testAVersionPastBothWaitsIsDue(): void
    {
        // twenty days on from the first of May is the twenty-first, which is behind us
        $this->agreed(valid_from: '2026-05-01');

        $this->assertCount(1, $this->due());
    }

    /**
     * A version signed for is not the automation's business however long it has been there.
     *
     * @return void
     * @link \App\Contracts\Unsigned\UnsignedPaperwork::findDue()
     */
    public function testAVersionWithPaperIsNeverDue(): void
    {
        $this->agreed(valid_from: '2026-05-01', conclusion_date: '2026-05-01');

        $this->assertSame([], $this->due());
    }

    /**
     * Both waits have to be up, not either of them. The service having been in for years
     * says nothing about a version that took effect last week.
     *
     * @return void
     * @link \App\Contracts\Unsigned\UnsignedPaperwork::findDue()
     */
    public function testTheLaterOfTheTwoWaitsIsWhatCounts(): void
    {
        // the contract went in in 2022, so its own wait was up long ago
        $this->agreed(valid_from: '2026-05-25');

        $this->assertSame([], $this->due(), 'The version has been in effect a week.');
    }

    /**
     * @return void
     * @link \App\Contracts\Unsigned\UnsignedPaperwork::findDue()
     */
    public function testTheDayTheWaitRunsOutIsAlreadyDue(): void
    {
        // twenty days on from the twelfth of May is exactly today
        $this->agreed(valid_from: '2026-05-12');

        $this->assertCount(1, $this->due());
    }

    /**
     * @return void
     * @link \App\Contracts\Unsigned\UnsignedPaperwork::findDue()
     */
    public function testTheDayBeforeTheWaitRunsOutIsNotDue(): void
    {
        $this->agreed(valid_from: '2026-05-13');

        $this->assertSame([], $this->due());
    }

    /**
     * A contract with no installation date has no day to count from, and Postgres reads
     * GREATEST past a NULL rather than through it - so without the guard the version would
     * be chased on the version's wait alone, which is half the rule.
     *
     * @return void
     * @link \App\Contracts\Unsigned\UnsignedPaperwork::findDue()
     */
    public function testAContractWithNoInstallationDateHasNoDeadline(): void
    {
        $this->contractInstalledOn(null);
        $this->agreed(valid_from: '2026-05-01');

        $this->assertSame([], $this->due());
    }

    /**
     * @return void
     * @link \App\Contracts\Unsigned\UnsignedPaperwork::findDue()
     */
    public function testAVersionFromBeforeTheLineIsLeftAlone(): void
    {
        Settings::set('core.contracts.unsigned.consider_from', '2026-01-01');
        $this->agreed(valid_from: '2025-12-31');

        $this->assertSame([], $this->due());

        Settings::set('core.contracts.unsigned.consider_from', '2025-01-01');

        $this->assertCount(1, $this->due(), 'The line did not move.');
    }

    /**
     * A version a later one has replaced is history. Its paperwork is worth putting straight
     * but nobody is running a service on it today, so nobody is cut off for it.
     *
     * @return void
     * @link \App\Contracts\Unsigned\UnsignedPaperwork::findDue()
     */
    public function testAVersionThatHasRunOutIsNotDue(): void
    {
        $this->agreed(valid_from: '2026-05-01', valid_until: '2026-05-20');

        $this->assertSame([], $this->due());
    }

    /**
     * A contract whose state serves nobody has nothing left to cut off, so its paperwork is
     * not the automation's business however long it has been outstanding.
     *
     * @return void
     * @link \App\Contracts\Unsigned\UnsignedPaperwork::findDue()
     */
    public function testAContractThatServesNobodyIsNotDue(): void
    {
        $this->agreed(valid_from: '2026-01-10');

        $this->assertCount(1, $this->due());

        $this->ContractVersions->Contracts->ContractStates->updateAll(
            ['active_services' => false],
            ['1 = 1'],
        );

        $this->assertSame([], $this->due());
    }

    /**
     * @return void
     * @link \App\Contracts\Unsigned\UnsignedPaperwork::findBecomingDueOn()
     */
    public function testAVersionBecomesDueOnOneDayAndNoOther(): void
    {
        $this->agreed(valid_from: '2026-05-12');

        $this->assertCount(1, $this->becomingDueOn(self::TODAY));
        $this->assertSame([], $this->becomingDueOn('2026-05-31'));
        $this->assertSame([], $this->becomingDueOn('2026-06-02'));
    }

    /**
     * The daily sweep and the named reminder days must not both pick the same version up in
     * one run, which is why its boundary is strict.
     *
     * @return void
     * @link \App\Contracts\Unsigned\UnsignedPaperwork::findDueBefore()
     */
    public function testWhatIsDueOnTheBoundaryIsNotDueBeforeIt(): void
    {
        $this->agreed(valid_from: '2026-05-12');

        $this->assertSame([], $this->dueBefore(self::TODAY), 'It came due today, not before.');
        $this->assertCount(1, $this->dueBefore('2026-06-02'));
    }

    /**
     * Under the sending anchor the wait starts when the customer got the papers, which is
     * later than the installation and so buys them time.
     *
     * @return void
     * @link \App\Model\Enum\UnsignedDeadlineAnchor::sql()
     */
    public function testTheSendingAnchorCountsFromTheDayThePapersWentOut(): void
    {
        // the version's own wait is long up, so only the anchor is in question
        $this->agreed(valid_from: '2026-01-10', sent_date: '2026-05-28');

        $this->assertCount(1, $this->due(), 'By the installation, this is years overdue.');

        Settings::set(UnsignedDeadlineAnchor::SETTINGS_PATH, UnsignedDeadlineAnchor::Sending->value);

        $this->assertSame([], $this->due(), 'The papers went out four days ago.');
    }

    /**
     * The strict sending anchor has nothing to count from where nobody recorded a sending,
     * so it lets that version off. This is the cost of the setting, and it is a case rather
     * than a footnote because it is the one way the automation can go quiet.
     *
     * @return void
     * @link \App\Model\Enum\UnsignedDeadlineAnchor::sql()
     */
    public function testTheStrictSendingAnchorLetsAnUnrecordedSendingOff(): void
    {
        $this->agreed(valid_from: '2026-01-10');

        Settings::set(UnsignedDeadlineAnchor::SETTINGS_PATH, UnsignedDeadlineAnchor::Sending->value);

        $this->assertSame([], $this->due());
    }

    /**
     * The anchor that falls back keeps that version, on the day the service went in.
     *
     * @return void
     * @link \App\Model\Enum\UnsignedDeadlineAnchor::sql()
     */
    public function testTheFallingBackAnchorKeepsAnUnrecordedSending(): void
    {
        $this->agreed(valid_from: '2026-01-10');

        Settings::set(
            UnsignedDeadlineAnchor::SETTINGS_PATH,
            UnsignedDeadlineAnchor::SendingOrInstallation->value,
        );

        $this->assertCount(1, $this->due());
    }

    /**
     * And where the sending is recorded, it is the sending that answers.
     *
     * @return void
     * @link \App\Model\Enum\UnsignedDeadlineAnchor::sql()
     */
    public function testTheFallingBackAnchorPrefersTheSendingWhereThereIsOne(): void
    {
        $this->agreed(valid_from: '2026-01-10', sent_date: '2026-05-28');

        Settings::set(
            UnsignedDeadlineAnchor::SETTINGS_PATH,
            UnsignedDeadlineAnchor::SendingOrInstallation->value,
        );

        $this->assertSame([], $this->due());
    }

    /**
     * Under the installation anchor the sending date is beside the point entirely.
     *
     * @return void
     * @link \App\Model\Enum\UnsignedDeadlineAnchor::sql()
     */
    public function testTheInstallationAnchorIgnoresTheSending(): void
    {
        $this->agreed(valid_from: '2026-01-10', sent_date: '2026-05-28');

        $this->assertCount(1, $this->due());
    }

    /**
     * With no wait at all, a version is due the day it takes effect.
     *
     * This is the widest of the three counts the dashboard card slices, and the card gets its
     * middle two by subtracting one count from another - so the sets have to nest, shorter
     * wait always holding everything a longer one holds.
     *
     * @return void
     * @link \App\Dashboard\Card\UnsignedContractsCard::data()
     */
    public function testWithNoWaitAVersionIsDueAsSoonAsItIsInEffect(): void
    {
        $this->agreed(valid_from: self::TODAY);

        $this->assertCount(1, $this->dueAfter(0, 0), 'It took effect today.');
        $this->assertSame([], $this->due(), 'And it is inside every real wait.');
    }

    /**
     * @return void
     * @link \App\Dashboard\Card\UnsignedContractsCard::data()
     */
    public function testAVersionNotYetInEffectIsNotDueEvenWithNoWait(): void
    {
        $this->agreed(valid_from: '2026-07-01');

        $this->assertSame([], $this->dueAfter(0, 0));
    }

    /**
     * @return list<\App\Model\Entity\ContractVersion>
     */
    private function due(): array
    {
        return $this->dueAfter(self::AFTER_ANCHOR, self::AFTER_VALID_FROM);
    }

    /**
     * @param int $after_anchor Days after the anchor date.
     * @param int $after_valid_from Days after the version took effect.
     * @return list<\App\Model\Entity\ContractVersion>
     */
    private function dueAfter(int $after_anchor, int $after_valid_from): array
    {
        /** @var list<\App\Model\Entity\ContractVersion> $records */
        $records = $this->paperwork
            ->findDue($after_anchor, $after_valid_from, new Date(self::TODAY))
            ->all()
            ->toList();

        return $records;
    }

    /**
     * @param string $day The day the wait is asked to have run out on.
     * @return list<\App\Model\Entity\ContractVersion>
     */
    private function becomingDueOn(string $day): array
    {
        /** @var list<\App\Model\Entity\ContractVersion> $records */
        $records = $this->paperwork
            ->findBecomingDueOn(self::AFTER_ANCHOR, self::AFTER_VALID_FROM, new Date($day))
            ->all()
            ->toList();

        return $records;
    }

    /**
     * @param string $day The day the wait is asked to have run out before.
     * @return list<\App\Model\Entity\ContractVersion>
     */
    private function dueBefore(string $day): array
    {
        /** @var list<\App\Model\Entity\ContractVersion> $records */
        $records = $this->paperwork
            ->findDueBefore(self::AFTER_ANCHOR, self::AFTER_VALID_FROM, new Date($day))
            ->all()
            ->toList();

        return $records;
    }

    /**
     * Put a version of the fixture contract on file.
     *
     * @param string $valid_from The day the version takes effect.
     * @param string|null $valid_until The day it stops, or null for still in force.
     * @param string|null $conclusion_date The day it was concluded, or null for no paper.
     * @param string|null $sent_date The day the papers went out, where they did.
     * @return void
     */
    private function agreed(
        string $valid_from,
        ?string $valid_until = null,
        ?string $conclusion_date = null,
        ?string $sent_date = null,
    ): void {
        $version = $this->ContractVersions->newEntity([
            'contract_id' => self::CONTRACT_ID,
            'valid_from' => $valid_from,
            'valid_until' => $valid_until,
            'conclusion_date' => $conclusion_date,
            'number_of_amendments' => 0,
            'obligations_settled' => false,
        ]);
        $this->ContractVersions->saveOrFail($version);

        if ($sent_date === null) {
            return;
        }

        // The sending is on the papers, and the papers belong to the proposal they were drawn
        // from - so that is where a test that is about the sending has to put it.
        $proposals = $this->getTableLocator()->get('ContractVersionProposals');
        $proposals->saveOrFail(
            $proposals->newEntity([
                'contract_id' => self::CONTRACT_ID,
                'contract_version_id' => $version->id,
                'purpose' => ProposalPurpose::NewContract->value,
                'effective_from' => $valid_from,
                'snapshot' => [
                    'contract' => [], 'customer' => [], 'version' => [], 'billings' => [],
                ],
                'snapshot_taken' => DateTime::now(),
                'changes' => [],
                'sent_date' => $sent_date,
                'sent_by' => ContractDeliveryMethod::Post->value,
            ]),
            ['checkRules' => false],
        );
    }

    /**
     * Move the day the fixture contract's service went in.
     *
     * @param string|null $day The day, or null for a contract nobody recorded one for.
     * @return void
     */
    private function contractInstalledOn(?string $day): void
    {
        $this->ContractVersions->Contracts->updateAll(
            ['installation_date' => $day],
            ['id' => self::CONTRACT_ID],
        );
    }
}
