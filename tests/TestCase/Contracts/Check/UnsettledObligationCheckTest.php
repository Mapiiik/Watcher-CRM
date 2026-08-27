<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Contracts\Check\UnsettledObligationCheck;
use App\Model\Table\ContractsTable;
use App\Model\Table\ContractVersionsTable;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Contracts\Check\UnsettledObligationCheck Test Case
 *
 * A term left running after the contract stopped means either something is owed and nobody
 * has asked for it, or it was waived and nobody has written that down.
 */
#[UsesClass(UnsettledObligationCheck::class)]
class UnsettledObligationCheckTest extends TestCase
{
    use LocatorAwareTrait;

    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

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
    ];

    private ContractVersionsTable $ContractVersions;

    private ContractsTable $Contracts;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->ContractVersions = $this->getTableLocator()
            ->get('ContractVersions', ['className' => ContractVersionsTable::class]);
        $this->Contracts = $this->getTableLocator()->get('Contracts', ['className' => ContractsTable::class]);

        $this->ContractVersions->deleteAll(['1 = 1']);
    }

    /**
     * A version in force for a few weeks carrying a term of two years is what a customer
     * leaving early looks like. The term stands, and nothing else on file says so.
     *
     * @return void
     * @link \App\Contracts\Check\UnsettledObligationCheck::find()
     */
    public function testATermOutlivingItsVersionIsReported(): void
    {
        $this->agreed(valid_from: '-1 month', valid_until: '-1 day', obligation_until: '+2 years');

        $this->assertCount(1, $this->found());
    }

    /**
     * Waived, and written down as waived. That tick is the whole point of the check.
     *
     * @return void
     * @link \App\Contracts\Check\UnsettledObligationCheck::find()
     */
    public function testATermAlreadySettledIsNotReported(): void
    {
        $this->agreed('-1 month', '-1 day', '+2 years', settled: true);

        $this->assertSame([], $this->found());
    }

    /**
     * A term that ends with its version has outlived nothing.
     *
     * @return void
     * @link \App\Contracts\Check\UnsettledObligationCheck::find()
     */
    public function testATermEndingWithItsVersionIsNotReported(): void
    {
        $this->agreed('-2 years', '-1 day', '-1 day');

        $this->assertSame([], $this->found());
    }

    /**
     * The version runs on, but the contract behind it has stopped - which is the same thing
     * said one record higher up.
     *
     * @return void
     * @link \App\Contracts\Check\UnsettledObligationCheck::find()
     */
    public function testATermOutlivingTheTerminatedContractIsReported(): void
    {
        $this->agreed('-1 month', null, '+2 years');
        $this->terminatedOn('-1 day');

        $this->assertCount(1, $this->found());
    }

    /**
     * A term already run out has nothing left to ask for and nothing left to waive, so daily
     * work is not shown a list of things that cannot be done.
     *
     * @return void
     * @link \App\Contracts\Check\UnsettledObligationCheck::find()
     */
    public function testATermAlreadyRunOutIsOnlyReportedWhenTheFilterIsLifted(): void
    {
        $this->agreed('-3 years', '-2 years', '-1 year');

        $this->assertSame([], $this->found());
        $this->assertCount(1, $this->found(ignore_inactive: false));
    }

    /**
     * Run the check and return what it found.
     *
     * @param bool $ignore_inactive Whether to keep to terms that are still running.
     * @return list<\App\Model\Entity\ContractVersion>
     */
    private function found(bool $ignore_inactive = true): array
    {
        /** @var list<\App\Model\Entity\ContractVersion> $records */
        $records = (new UnsettledObligationCheck($this->ContractVersions, $ignore_inactive))
            ->find()
            ->all()
            ->toList();

        return $records;
    }

    /**
     * Agree a version of the contract, with days given relative to today, so that a term
     * still running means what it says whenever the test is run.
     *
     * @param string $valid_from When the version begins.
     * @param string|null $valid_until When it ends, or null for an open end.
     * @param string $obligation_until When the minimum term runs out.
     * @param bool $settled Whether the term has been settled or waived.
     * @return void
     */
    private function agreed(
        string $valid_from,
        ?string $valid_until,
        string $obligation_until,
        bool $settled = false,
    ): void {
        $this->ContractVersions->saveOrFail($this->ContractVersions->newEntity([
            'contract_id' => self::CONTRACT_ID,
            'valid_from' => Date::now()->modify($valid_from)->format('Y-m-d'),
            'valid_until' => $valid_until === null ? null : Date::now()->modify($valid_until)->format('Y-m-d'),
            'obligation_until' => Date::now()->modify($obligation_until)->format('Y-m-d'),
            'number_of_amendments' => 0,
            'obligations_settled' => $settled,
        ]));
    }

    /**
     * Stop the contract itself on a day.
     *
     * @param string $on The day the services stop, as a modifier of today.
     * @return void
     */
    private function terminatedOn(string $on): void
    {
        $this->Contracts->updateAll(
            ['termination_date' => Date::now()->modify($on)->format('Y-m-d')],
            ['id' => self::CONTRACT_ID],
        );
    }
}
