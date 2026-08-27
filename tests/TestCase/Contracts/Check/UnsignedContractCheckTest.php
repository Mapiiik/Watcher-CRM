<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Contracts\Check\UnsignedContractCheck;
use App\Model\Table\ContractVersionsTable;
use Cake\Cache\Cache;
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
     * Run the check and return what it found.
     *
     * @return list<\App\Model\Entity\ContractVersion>
     */
    private function found(): array
    {
        /** @var list<\App\Model\Entity\ContractVersion> $records */
        $records = (new UnsignedContractCheck($this->ContractVersions))->find()->all()->toList();

        return $records;
    }

    /**
     * Agree a version of the contract on a day, with paper from another.
     *
     * @param string $valid_from The day the version takes effect.
     * @param string|null $conclusion_date The day it was concluded, or null for no paper.
     * @return void
     */
    private function agreed(string $valid_from, ?string $conclusion_date): void
    {
        $this->ContractVersions->saveOrFail($this->ContractVersions->newEntity([
            'contract_id' => self::CONTRACT_ID,
            'valid_from' => $valid_from,
            'conclusion_date' => $conclusion_date,
            'number_of_amendments' => 0,
            'obligations_settled' => false,
        ]));
    }
}
