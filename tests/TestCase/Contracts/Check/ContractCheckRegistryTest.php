<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Contracts\Check\ContractCheckInterface;
use App\Contracts\Check\ContractCheckRegistry;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Contracts\Check\ContractCheckRegistry Test Case
 */
#[UsesClass(ContractCheckRegistry::class)]
class ContractCheckRegistryTest extends TestCase
{
    /**
     * The contract the fixtures hang their billings on.
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * The customer holding it.
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

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
        'app.Queues',
        'app.Services',
        'app.Billings',
    ];

    /**
     * Every registered check has to be able to run and to say how many it found. A check
     * whose query does not hold together is not something to find out from the dashboard.
     *
     * @return void
     * @link \App\Contracts\Check\ContractCheckRegistry::all()
     */
    public function testEveryCheckRuns(): void
    {
        $checks = (new ContractCheckRegistry())->all();

        $this->assertNotEmpty($checks);

        foreach ($checks as $check) {
            $this->assertGreaterThanOrEqual(0, $check->count(), $check->id() . ' cannot be counted.');
            $this->assertNotSame('', $check->title());
            $this->assertNotSame('', $check->emptyMessage());
        }
    }

    /**
     * Ids are what the query string, the anchors and the template names are built from, so
     * two checks answering to the same one would quietly stand in for each other.
     *
     * @return void
     * @link \App\Contracts\Check\ContractCheckRegistry::all()
     */
    public function testIdsAreDistinct(): void
    {
        $ids = array_map(
            fn(ContractCheckInterface $check): string => $check->id(),
            (new ContractCheckRegistry())->all(),
        );

        $this->assertSame($ids, array_unique($ids));
    }

    /**
     * Every check has to answer to the filter. One that quietly ignored it would report the
     * whole history under a heading that says it is showing today's work.
     *
     * @return void
     * @link \App\Contracts\Check\ContractCheckRegistry::__construct()
     */
    public function testEveryCheckAnswersToTheFilter(): void
    {
        $running = new ContractCheckRegistry(true);
        $everything = new ContractCheckRegistry(false);

        foreach ($running->all() as $check) {
            $lifted = $everything->get($check->id());

            $this->assertNotNull($lifted);
            $this->assertLessThanOrEqual(
                $lifted->count(),
                $check->count(),
                sprintf('%s reports more when the filter is on than when it is lifted.', $check->id()),
            );

            $this->assertNotSame(
                $check->find()->sql(),
                $lifted->find()->sql(),
                sprintf('%s asks the same thing either way, so the filter passes it by.', $check->id()),
            );
        }
    }

    /**
     * The contract a check is asked about has to reach every check, because a contract shows
     * its own findings by asking all of them at once.
     *
     * @return void
     * @link \App\Contracts\Check\ContractCheckRegistry::__construct()
     */
    public function testEveryCheckIsToldWhichContractIsBeingAskedAbout(): void
    {
        $anywhere = new ContractCheckRegistry(contract_id: null);
        $here = new ContractCheckRegistry(contract_id: self::CONTRACT_ID);

        foreach ($anywhere->all() as $check) {
            $narrowed = $here->get($check->id());

            $this->assertNotNull($narrowed);

            // a check over the contracts themselves narrows on the key, the rest on the
            // field pointing at it - either way the contract has to reach the query
            $this->assertMatchesRegularExpression(
                '/(contract_id|Contracts\.id) = :/',
                $narrowed->find()->sql(),
                sprintf('%s does not narrow to the contract it was asked about.', $check->id()),
            );
            $this->assertNotSame(
                $check->find()->sql(),
                $narrowed->find()->sql(),
                sprintf('%s asks the same thing either way, so the contract passes it by.', $check->id()),
            );
        }
    }

    /**
     * The customer a check is asked about has to reach every check too, because a customer's
     * page shows the findings on every contract they hold.
     *
     * @return void
     * @link \App\Contracts\Check\ContractCheckRegistry::__construct()
     */
    public function testEveryCheckIsToldWhichCustomerIsBeingAskedAbout(): void
    {
        $anybody = new ContractCheckRegistry(customer_id: null);
        $theirs = new ContractCheckRegistry(customer_id: self::CUSTOMER_ID);

        foreach ($anybody->all() as $check) {
            $narrowed = $theirs->get($check->id());

            $this->assertNotNull($narrowed);

            // the contract is beside every one of these queries, as its subject or as what
            // the record hangs on, so they all narrow through the same field
            $this->assertStringContainsString(
                'Contracts.customer_id = :',
                $narrowed->find()->sql(),
                sprintf('%s does not narrow to the customer it was asked about.', $check->id()),
            );
        }
    }

    /**
     * A check with nothing to say gets no heading on the page that draws the findings, and
     * one that has something is handed what it found rather than being asked twice.
     *
     * @return void
     * @link \App\Check\AbstractCheckRegistry::findings()
     */
    public function testFindingsLeaveOutTheChecksWithNothingToSay(): void
    {
        $findings = (new ContractCheckRegistry(ignore_inactive: false))->findings();

        foreach ($findings as $finding) {
            $this->assertGreaterThan(
                0,
                count($finding['records']),
                $finding['check']->id() . ' was listed with nothing to show.',
            );
        }

        $listed = array_map(
            fn(array $finding): string => $finding['check']->id(),
            $findings,
        );

        $this->assertSame($listed, array_unique($listed));
    }

    /**
     * Asking twice gives the same instance, so that counting a check and then listing it
     * does not build it again.
     *
     * @return void
     * @link \App\Contracts\Check\ContractCheckRegistry::get()
     */
    public function testChecksAreKept(): void
    {
        $registry = new ContractCheckRegistry();

        $this->assertSame($registry->get('billing_gap'), $registry->get('billing_gap'));
        $this->assertNull($registry->get('no_such_check'));
    }
}
