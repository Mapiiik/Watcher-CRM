<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Contracts\Check\OverlappingContractVersionsCheck;
use App\Model\Table\ContractVersionsTable;
use Cake\Cache\Cache;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Settings\Utility\Settings;

/**
 * App\Contracts\Check\OverlappingContractVersionsCheck Test Case
 *
 * A contract is meant to have exactly one version in force on any given day - that is what
 * makes it possible to say what was agreed.
 */
#[UsesClass(OverlappingContractVersionsCheck::class)]
class OverlappingContractVersionsCheckTest extends TestCase
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
     * The usual shape of it: the old version was never given an end, so from the day the new
     * one begins there are two sets of terms and nothing to say which applies.
     *
     * @return void
     * @link \App\Contracts\Check\OverlappingContractVersionsCheck::find()
     */
    public function testAVersionLeftOpenUnderANewOneIsReported(): void
    {
        $this->inForce('2022-01-01', null);
        $this->inForce('2023-01-01', null);

        $found = $this->found();

        $this->assertCount(1, $found, 'Only the earlier of the pair is the one to fix.');
        $this->assertSame('2022-01-01', $found[0]->valid_from?->format('Y-m-d'));
        $this->assertSame('2023-01-01', $found[0]->get('overlaps_from')?->format('Y-m-d'));
    }

    /**
     * One ending the day before the next begins is a contract carried on, not two at once.
     *
     * @return void
     * @link \App\Contracts\Check\OverlappingContractVersionsCheck::find()
     */
    public function testVersionsThatOnlyMeetAreNotAnOverlap(): void
    {
        $this->inForce('2022-01-01', '2022-12-31');
        $this->inForce('2023-01-01', null);

        $this->assertSame([], $this->found());
    }

    /**
     * An import marked the versions whose start nobody knew. Left in, an open-ended one of
     * those would overlap everything that came after it and say nothing by doing so - and
     * there are a thousand of them.
     *
     * @return void
     * @link \App\Contracts\Check\AbstractContractCheck::knownDate()
     */
    public function testAVersionWhoseStartNobodyKnowsIsPassedOver(): void
    {
        $this->inForce(self::START_NOT_KNOWN, null);
        $this->inForce('2023-01-01', null);

        $this->assertSame([], $this->found());
    }

    /**
     * Which days stand for "not known" is a matter of what the data was imported from, so it
     * is asked of the settings rather than settled here.
     *
     * @return void
     * @link \App\Contracts\Check\AbstractContractCheck::datesMeaningUnknown()
     */
    public function testWhichDaysStandForNotKnownComesFromTheSettings(): void
    {
        $this->inForce(self::START_NOT_KNOWN, null);
        $this->inForce('2023-01-01', null);

        Settings::set('core.contracts.checks.dates_meaning_unknown', []);

        $this->assertCount(1, $this->found(), 'The check did not ask the settings again.');
    }

    /**
     * Run the check and return what it found.
     *
     * @return list<\App\Model\Entity\ContractVersion>
     */
    private function found(): array
    {
        /** @var list<\App\Model\Entity\ContractVersion> $records */
        $records = (new OverlappingContractVersionsCheck($this->ContractVersions))->find()->all()->toList();

        return $records;
    }

    /**
     * Put a version of the contract in force over a stretch of time.
     *
     * @param string $from The day it begins.
     * @param string|null $until The day it ends, or null for an open end.
     * @return void
     */
    private function inForce(string $from, ?string $until): void
    {
        $this->ContractVersions->saveOrFail($this->ContractVersions->newEntity([
            'contract_id' => self::CONTRACT_ID,
            'valid_from' => $from,
            'valid_until' => $until,
            'number_of_amendments' => 0,
            'obligations_settled' => false,
        ]));
    }
}
