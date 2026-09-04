<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Entity\ContractVersion;
use App\Model\Table\ContractVersionsTable;
use App\Test\Traits\TableTestTrait;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use Override;

/**
 * App\Model\Table\ContractVersionsTable Test Case
 */
class ContractVersionsTableTest extends TestCase
{
    use TableTestTrait;

    /**
     * Test subject
     *
     * @var \App\Model\Table\ContractVersionsTable
     */
    protected $ContractVersions;

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
        'app.ContractVersionProposals',
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
        $config = $this->getTableLocator()->exists('ContractVersions') ? [] : ['className' => ContractVersionsTable::class];
        $this->ContractVersions = $this->getTableLocator()->get('ContractVersions', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->ContractVersions);

        parent::tearDown();
    }

    /**
     * A new record with nothing filled in is refused - see the trait for why that is the question
     * worth asking here.
     *
     * @return void
     * @link \App\Model\Table\ContractVersionsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->assertEmptyRecordIsRefused($this->ContractVersions);
    }

    /**
     * The rules refuse a record whose references point nowhere - see the trait for why that is
     * the question worth asking here.
     *
     * @return void
     * @link \App\Model\Table\ContractVersionsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->assertDanglingReferencesAreRefused($this->ContractVersions);
    }

    /**
     * A version in force for a single day is a real one; one that ends the day before it begins
     * is what the previous version's end written onto a new one looks like.
     *
     * @return void
     * @link \App\Model\Table\ContractVersionsTable::buildRules()
     */
    public function testTheEndMayNotComeBeforeTheStart(): void
    {
        $version = $this->fixtureVersion();
        $day = $version->valid_from;

        $this->assertNotFalse(
            $this->ContractVersions->save(
                $this->ContractVersions->patchEntity($version, ['valid_until' => $day->toDateString()]),
            ),
            'A version in force for one day was refused.',
        );

        $refused = $this->ContractVersions->patchEntity(
            $this->fixtureVersion(),
            ['valid_until' => $day->subDays(1)->toDateString()],
        );

        $this->assertFalse($this->ContractVersions->save($refused));
        $this->assertArrayHasKey('contractVersionPeriodIsPossible', $refused->getError('valid_until'));
    }

    /**
     * The term belongs to the version, so it cannot have run out before the version existed.
     *
     * @return void
     * @link \App\Model\Table\ContractVersionsTable::buildRules()
     */
    public function testTheTermMayNotRunOutBeforeTheVersionBegins(): void
    {
        $version = $this->fixtureVersion();
        $day = $version->valid_from;

        $this->assertNotFalse(
            $this->ContractVersions->save(
                $this->ContractVersions->patchEntity($version, ['obligation_until' => $day->toDateString()]),
            ),
            'A term running out on the day the version begins was refused.',
        );

        $refused = $this->ContractVersions->patchEntity(
            $this->fixtureVersion(),
            ['obligation_until' => $day->subDays(1)->toDateString()],
        );

        $this->assertFalse($this->ContractVersions->save($refused));
        $this->assertArrayHasKey('obligationEndsAfterItsVersionBegins', $refused->getError('obligation_until'));
    }

    /**
     * Both rules are asked on every save, not only when a date has been touched.
     *
     * Eighty-odd versions on file break them already, and this is what is meant to happen to them:
     * whoever opens one is the one to look the dates up and put them right. Changing the note and
     * leaving the impossible period behind is not a way past it.
     *
     * @return void
     * @link \App\Model\Table\ContractVersionsTable::buildRules()
     */
    public function testAnImpossiblePeriodStandsInTheWayOfEveryOtherChange(): void
    {
        $broken = $this->fixtureVersion();
        $broken->valid_until = $broken->valid_from->subDays(1);
        // dated as it would have come to be before the rule was there to refuse it
        $this->ContractVersions->saveOrFail($broken, ['checkRules' => false]);

        $refused = $this->ContractVersions->patchEntity($this->fixtureVersion(), ['note' => 'Just a word.']);

        $this->assertFalse($this->ContractVersions->save($refused), 'The impossible period was saved over.');
        $this->assertArrayHasKey('contractVersionPeriodIsPossible', $refused->getError('valid_until'));

        $mended = $this->ContractVersions->patchEntity($this->fixtureVersion(), [
            'note' => 'Just a word.',
            'valid_until' => $broken->valid_from->toDateString(),
        ]);

        $this->assertNotFalse($this->ContractVersions->save($mended));
    }

    /**
     * What may be taken back and what may not.
     *
     * The days have to be made here rather than in the fixture: where the line falls depends on
     * the day the test is run on, which a fixture cannot know.
     *
     * @return void
     * @link \App\Model\Table\ContractVersionsTable::mayBeDeleted()
     */
    public function testOnlyAnUnsignedVersionOfThisMonthOrLaterMayBeTakenBack(): void
    {
        $thisMonth = Date::now()->firstOfMonth();

        $this->assertTrue(
            $this->ContractVersions->mayBeDeleted($this->versionFrom($thisMonth, null)),
            'A version with no paper behind it, beginning this month, could not be taken back.',
        );
        $this->assertTrue(
            $this->ContractVersions->mayBeDeleted($this->versionFrom($thisMonth->addMonths(1), null)),
            'A version with no paper behind it, beginning next month, could not be taken back.',
        );
        $this->assertFalse(
            $this->ContractVersions->mayBeDeleted($this->versionFrom($thisMonth->subDays(1), null)),
            'A version beginning last month was taken back.',
        );
        $this->assertFalse(
            $this->ContractVersions->mayBeDeleted($this->versionFrom($thisMonth, $thisMonth)),
            'A version with paper behind it was taken back.',
        );
        $this->assertFalse(
            $this->ContractVersions->mayBeDeleted($this->fixtureVersion()),
            'A version from years back was taken back.',
        );
    }

    /**
     * The one version the fixture holds, read afresh.
     *
     * @return \App\Model\Entity\ContractVersion
     */
    private function fixtureVersion(): ContractVersion
    {
        /** @var \App\Model\Entity\ContractVersion $version */
        $version = $this->ContractVersions->find()->firstOrFail();

        return $version;
    }

    /**
     * A version taking effect on a day, with paper behind it or without.
     *
     * @param \Cake\I18n\Date $valid_from The day it takes effect.
     * @param \Cake\I18n\Date|null $conclusion_date The day it was concluded, or null for no paper.
     * @return \App\Model\Entity\ContractVersion
     */
    private function versionFrom(Date $valid_from, ?Date $conclusion_date): ContractVersion
    {
        return $this->ContractVersions->newEntity([
            'contract_id' => $this->fixtureVersion()->contract_id,
            'valid_from' => $valid_from->toDateString(),
            'conclusion_date' => $conclusion_date?->toDateString(),
            'number_of_amendments' => 0,
            'obligations_settled' => false,
        ]);
    }

    /**
     * A version whose papers have been drawn up does not go on its own.
     *
     * An unsigned version from the current month may still be taken back, and its proposals are the
     * record of what went out for it, so either both go or neither does.
     *
     * @return void
     */
    public function testAVersionWithProposalsIsNotDeleted(): void
    {
        $version = $this->ContractVersions->get('74824fba-20b2-46fc-806c-df795aa9e429');

        $this->assertFalse($this->ContractVersions->delete($version));
    }
}
