<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Enum\ContractPeriodSource;
use App\Model\Table\ContractsTable;
use App\Model\Table\ContractVersionsTable;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * The two finders behind the overview of new and ending contracts.
 *
 * Kept apart from the rest of the table's tests because every case here is built out of
 * versions rather than read off the fixture, and the fixture is shared with a couple of
 * dozen other files that count its rows.
 */
#[UsesClass(ContractsTable::class)]
#[UsesClass(ContractPeriodSource::class)]
class ContractsTablePeriodFindersTest extends TestCase
{
    use LocatorAwareTrait;

    /**
     * The contract every version below hangs off.
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * The other contract of the same customer, for the cases that count customers.
     */
    private const OTHER_CONTRACT_ID = '9c0d5e5c-2a6b-4f8e-9a3d-1b7c4e2f6a90';

    /**
     * The customer both contracts belong to.
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

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
    ];

    private ContractsTable $Contracts;

    private ContractVersionsTable $ContractVersions;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->Contracts = $this->getTableLocator()
            ->get('Contracts', ['className' => ContractsTable::class]);
        $this->ContractVersions = $this->getTableLocator()
            ->get('ContractVersions', ['className' => ContractVersionsTable::class]);

        // Cleared rather than added to: the fixture is shared with a couple of dozen files
        // that count its rows, and every case here wants versions of its own anyway.
        $this->ContractVersions->deleteAll(['1 = 1']);
    }

    /**
     * A contract begins when its earliest version begins, not when the one in force does.
     *
     * @return void
     * @link \App\Model\Table\ContractsTable::findStartingBetween()
     */
    public function testStartingBetweenTakesTheEarliestVersion(): void
    {
        $this->inForce('2026-03-01', '2026-06-30');
        $this->inForce('2026-07-01', null);

        $this->assertSame(
            [self::CONTRACT_ID],
            $this->starting('2026-03-01', '2026-03-31'),
            'The contract began in March.',
        );
        $this->assertSame(
            [],
            $this->starting('2026-07-01', '2026-07-31'),
            'July is an amendment, not a new contract.',
        );
    }

    /**
     * The trap the whole of {@see \App\Model\Enum\ContractPeriodSource} is shaped around.
     *
     * Versions are contiguous, each closed on the day before the next begins, so the latest
     * `valid_until` on a running contract is the day an amendment took over. Reading the end
     * as `MAX(valid_until)` turns every amendment into a termination: counted over July 2026
     * on the live file that is 34 endings where there are 29.
     *
     * @return void
     * @link \App\Model\Table\ContractsTable::findEndingBetween()
     */
    public function testEndingBetweenReadsTheLastVersionRatherThanTheLatestEnd(): void
    {
        $this->inForce('2026-01-01', '2026-07-15');
        $this->inForce('2026-07-16', null);

        $this->assertSame(
            [],
            $this->ending('2026-07-01', '2026-07-31'),
            'The contract is still running; only an amendment happened in July.',
        );
    }

    /**
     * The other half of the pair above: a contract that really did end is still found.
     *
     * Without this the test before it would pass just as well on a finder that answers
     * nothing at all.
     *
     * @return void
     * @link \App\Model\Table\ContractsTable::findEndingBetween()
     */
    public function testAClosedLastVersionDoesEnd(): void
    {
        $this->inForce('2026-01-01', '2026-07-15');
        $this->inForce('2026-07-16', '2026-07-31');

        $this->assertSame(
            [self::CONTRACT_ID],
            $this->ending('2026-07-01', '2026-07-31'),
            'The last version was closed inside the period.',
        );
    }

    /**
     * Two versions beginning on the same day are not supposed to happen and today none do,
     * but {@see \App\Contracts\Check\OverlappingContractVersionsCheck} exists because they
     * sometimes will. When one of the pair has no end, the contract is running.
     *
     * @return void
     * @link \App\Model\Enum\ContractPeriodSource::endsOnSql()
     */
    public function testVersionsBeginningOnTheSameDayPreferTheOpenEnd(): void
    {
        $this->inForce('2026-07-16', '2026-07-31');
        $this->inForce('2026-07-16', null);

        $this->assertSame(
            [],
            $this->ending('2026-07-01', '2026-07-31'),
            'One of the two says the contract runs on, and that is the safer reading.',
        );
    }

    /**
     * An expression carries no type of its own, so the day has to be declared a date. Left
     * undeclared, Postgres hands back a string and the column sorts by its spelling.
     *
     * @return void
     * @link \App\Model\Table\ContractsTable::findStartingBetween()
     */
    public function testTheWorkedOutDaysComeBackAsDates(): void
    {
        $this->inForce('2026-07-10', '2026-07-20');

        $starting = $this->Contracts
            ->find(
                'startingBetween',
                source: ContractPeriodSource::Versions,
                from: new Date('2026-07-01'),
                to: new Date('2026-07-31'),
            )
            ->firstOrFail();
        $ending = $this->Contracts
            ->find(
                'endingBetween',
                source: ContractPeriodSource::Versions,
                from: new Date('2026-07-01'),
                to: new Date('2026-07-31'),
            )
            ->firstOrFail();

        $this->assertInstanceOf(Date::class, $starting->get('starts_on'));
        $this->assertInstanceOf(Date::class, $ending->get('ends_on'));
    }

    /**
     * About a sixth of the file carries no version at all. Read by the versions, such a
     * contract begins and ends nowhere - which is the cost the switch in the filter exists
     * to let somebody weigh.
     *
     * @return void
     * @link \App\Model\Table\ContractsTable::findStartingBetween()
     */
    public function testAContractWithNoVersionsBeginsAndEndsNowhere(): void
    {
        $this->assertSame([], $this->starting('1900-01-01', '2100-01-01'));
        $this->assertSame([], $this->ending('1900-01-01', '2100-01-01'));
    }

    /**
     * The other readings: the columns on the contract itself, which the versionless
     * contracts are reachable by. Both begin at the installation, there being only one day
     * on record for a contract beginning.
     *
     * @return void
     * @link \App\Model\Enum\ContractPeriodSource::startsOnSql()
     */
    public function testTheContractsOwnColumnsBothBeginAtTheInstallation(): void
    {
        foreach ([ContractPeriodSource::ServiceDates, ContractPeriodSource::EquipmentDates] as $source) {
            $this->assertSame(
                [self::CONTRACT_ID],
                $this->starting('2022-11-01', '2022-11-30', $source),
                'The fixture contract was installed on 2022-11-28.',
            );
        }
    }

    /**
     * A service is routinely stopped weeks before anybody drives out to collect the kit, so
     * the two endings fall in different months and a report has to say which it counted.
     *
     * @return void
     * @link \App\Model\Enum\ContractPeriodSource::endsOnSql()
     */
    public function testTheServiceAndTheEquipmentEndOnDifferentDays(): void
    {
        // Written straight rather than saved: the fixture contract is in a state whose rules
        // reach half the schema, and none of that is what this is about.
        $this->Contracts->updateAll(
            ['uninstallation_date' => '2023-02-20'],
            ['id' => self::CONTRACT_ID],
        );

        $this->assertSame(
            [self::CONTRACT_ID],
            $this->ending('2022-12-01', '2022-12-31', ContractPeriodSource::ServiceDates),
            'The service was stopped on 2022-12-11.',
        );
        $this->assertSame(
            [],
            $this->ending('2022-12-01', '2022-12-31', ContractPeriodSource::EquipmentDates),
            'The kit was still on the wall in December.',
        );
        $this->assertSame(
            [self::CONTRACT_ID],
            $this->ending('2023-02-01', '2023-02-28', ContractPeriodSource::EquipmentDates),
            'It was fetched back in February.',
        );
    }

    /**
     * Both days of the period are inside it. A month asked for as the first to the last of
     * it has to hold what happened on either.
     *
     * @return void
     * @link \App\Model\Table\ContractsTable::findStartingBetween()
     */
    public function testBothEndsOfThePeriodAreCountedIn(): void
    {
        $this->inForce('2026-07-01', null);
        $this->assertSame([self::CONTRACT_ID], $this->starting('2026-07-01', '2026-07-31'));

        $this->ContractVersions->deleteAll(['1 = 1']);
        $this->inForce('2026-07-31', null);
        $this->assertSame([self::CONTRACT_ID], $this->starting('2026-07-01', '2026-07-31'));
    }

    /**
     * The two listings are not halves of the file. A contract signed and given up inside the
     * same period is in both, and the net change between them is still the arithmetic it
     * looks like.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfNewAndEndingContracts()
     */
    public function testAContractThatBeginsAndEndsInThePeriodIsInBoth(): void
    {
        $this->inForce('2026-07-05', '2026-07-20');

        $this->assertSame([self::CONTRACT_ID], $this->starting('2026-07-01', '2026-07-31'));
        $this->assertSame([self::CONTRACT_ID], $this->ending('2026-07-01', '2026-07-31'));
    }

    /**
     * A customer is new when their first contract begins, not when a later one does.
     *
     * @return void
     * @link \App\Model\Table\ContractsTable::findCustomersStartingBetween()
     */
    public function testACustomerIsNewWithTheirFirstContract(): void
    {
        $this->inForce('2026-03-01', null);
        $this->inForce('2026-07-01', null, self::OTHER_CONTRACT_ID);

        $this->assertSame([self::CUSTOMER_ID], $this->customersStarting('2026-03-01', '2026-03-31'));
        $this->assertSame([], $this->customersStarting('2026-07-01', '2026-07-31'), 'A second contract.');
    }

    /**
     * A customer leaves with the last of their contracts to end.
     *
     * @return void
     * @link \App\Model\Table\ContractsTable::findCustomersEndingBetween()
     */
    public function testACustomerLeavesWithTheirLastContract(): void
    {
        $this->inForce('2026-01-01', '2026-07-15');
        $this->inForce('2026-02-01', '2026-08-20', self::OTHER_CONTRACT_ID);

        $this->assertSame([], $this->customersEnding('2026-07-01', '2026-07-31'), 'One is still running.');
        $this->assertSame([self::CUSTOMER_ID], $this->customersEnding('2026-08-01', '2026-08-31'));
    }

    /**
     * A contract that has not ended keeps its customer, however long ago the other one ended.
     *
     * @return void
     * @link \App\Model\Table\ContractsTable::findCustomersEndingBetween()
     */
    public function testAContractStillRunningKeepsTheCustomer(): void
    {
        $this->inForce('2026-01-01', '2026-07-15');
        $this->inForce('2026-02-01', null, self::OTHER_CONTRACT_ID);

        $this->assertSame([], $this->customersEnding('2026-07-01', '2026-07-31'));
    }

    /**
     * A contract with no day it begins on is not counted at all: under the versions the other
     * contract here has none, so it neither keeps the customer nor makes them new.
     *
     * @return void
     * @link \App\Model\Table\ContractsTable::findCustomersEndingBetween()
     */
    public function testAContractWithoutABeginningIsLeftOut(): void
    {
        $this->inForce('2026-07-01', '2026-07-15');

        $this->assertSame([self::CUSTOMER_ID], $this->customersStarting('2026-07-01', '2026-07-31'));
        $this->assertSame([self::CUSTOMER_ID], $this->customersEnding('2026-07-01', '2026-07-31'));
    }

    /**
     * Put a version in force over the contract every case here is built on, or another.
     *
     * @param string $from The day it begins.
     * @param string|null $until The day it ends, or null where it has not.
     * @param string $contractId The contract.
     * @return void
     */
    private function inForce(string $from, ?string $until, string $contractId = self::CONTRACT_ID): void
    {
        $this->ContractVersions->saveOrFail($this->ContractVersions->newEntity([
            'contract_id' => $contractId,
            'valid_from' => $from,
            'valid_until' => $until,
            'number_of_amendments' => 0,
            'obligations_settled' => false,
        ]));
    }

    /**
     * The ids of the contracts beginning within the period.
     *
     * @param string $from First day of the period.
     * @param string $to Last day of the period.
     * @param \App\Model\Enum\ContractPeriodSource|null $source Which dates to read.
     * @return list<string>
     */
    private function starting(string $from, string $to, ?ContractPeriodSource $source = null): array
    {
        return $this->idsFrom('startingBetween', $from, $to, $source);
    }

    /**
     * The ids of the contracts ending within the period.
     *
     * @param string $from First day of the period.
     * @param string $to Last day of the period.
     * @param \App\Model\Enum\ContractPeriodSource|null $source Which dates to read.
     * @return list<string>
     */
    private function ending(string $from, string $to, ?ContractPeriodSource $source = null): array
    {
        return $this->idsFrom('endingBetween', $from, $to, $source);
    }

    /**
     * The ids of the customers whose first contract begins within the period.
     *
     * @param string $from First day of the period.
     * @param string $to Last day of the period.
     * @return list<string>
     */
    private function customersStarting(string $from, string $to): array
    {
        return $this->idsFrom('customersStartingBetween', $from, $to, null, 'customer_id');
    }

    /**
     * The ids of the customers whose last contract ends within the period.
     *
     * @param string $from First day of the period.
     * @param string $to Last day of the period.
     * @return list<string>
     */
    private function customersEnding(string $from, string $to): array
    {
        return $this->idsFrom('customersEndingBetween', $from, $to, null, 'customer_id');
    }

    /**
     * @param string $finder Which end of the contract's life to ask about.
     * @param string $from First day of the period.
     * @param string $to Last day of the period.
     * @param \App\Model\Enum\ContractPeriodSource|null $source Which dates to read.
     * @param string $field Which id to read off the rows.
     * @return list<string>
     */
    private function idsFrom(
        string $finder,
        string $from,
        string $to,
        ?ContractPeriodSource $source,
        string $field = 'id',
    ): array {
        /** @var list<string> $ids */
        $ids = $this->Contracts
            ->find(
                $finder,
                source: $source ?? ContractPeriodSource::Versions,
                from: new Date($from),
                to: new Date($to),
            )
            ->all()
            ->extract($field)
            ->toList();

        return $ids;
    }
}
