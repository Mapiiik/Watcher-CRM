<?php
declare(strict_types=1);

namespace App\Test\TestCase\Customers\Check;

use App\Customers\Check\CustomerCheckInterface;
use App\Customers\Check\CustomerCheckRegistry;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Customers\Check\CustomerCheckRegistry Test Case
 */
#[UsesClass(CustomerCheckRegistry::class)]
class CustomerCheckRegistryTest extends TestCase
{
    /**
     * The customer the fixtures hang everything on.
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
        'app.Emails',
        'app.Phones',
        'app.Labels',
        'app.CustomerLabels',
    ];

    /**
     * Every registered check has to be able to run and to say how many it found. A check
     * whose query does not hold together is not something to find out from the dashboard.
     *
     * @return void
     * @link \App\Customers\Check\CustomerCheckRegistry::all()
     */
    public function testEveryCheckRuns(): void
    {
        $checks = (new CustomerCheckRegistry())->all();

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
     * @link \App\Customers\Check\CustomerCheckRegistry::all()
     */
    public function testIdsAreDistinct(): void
    {
        $ids = array_map(
            fn(CustomerCheckInterface $check): string => $check->id(),
            (new CustomerCheckRegistry())->all(),
        );

        $this->assertSame($ids, array_unique($ids));
    }

    /**
     * Every check has to answer to the filter. One that quietly ignored it would report the
     * whole history under a heading that says it is showing today's work.
     *
     * @return void
     * @link \App\Customers\Check\CustomerCheckRegistry::__construct()
     */
    public function testEveryCheckAnswersToTheFilter(): void
    {
        $running = new CustomerCheckRegistry(true);
        $everything = new CustomerCheckRegistry(false);

        foreach ($running->all() as $check) {
            $lifted = $everything->get($check->id());

            $this->assertNotNull($lifted);
            $this->assertLessThanOrEqual(
                $lifted->count(),
                $check->count(),
                sprintf('%s reports more when the filter is on than when it is lifted.', $check->id()),
            );

            // A check whose subject is what is running has no longer history to fall back
            // on, and says so rather than inventing a narrower question to answer instead.
            if ($check->hasAWiderReading()) {
                $this->assertNotSame(
                    $check->find()->sql(),
                    $lifted->find()->sql(),
                    sprintf('%s asks the same thing either way, so the filter passes it by.', $check->id()),
                );
            }
        }
    }

    /**
     * The customer a check is asked about has to reach every check, because a customer's own
     * card shows what is missing about them.
     *
     * @return void
     * @link \App\Customers\Check\CustomerCheckRegistry::__construct()
     */
    public function testEveryCheckIsToldWhichCustomerIsBeingAskedAbout(): void
    {
        $anybody = new CustomerCheckRegistry(customer_id: null);
        $theirs = new CustomerCheckRegistry(customer_id: self::CUSTOMER_ID);

        foreach ($anybody->all() as $check) {
            $narrowed = $theirs->get($check->id());

            $this->assertNotNull($narrowed);

            // the customer is the subject of every one of these, so they all narrow the
            // same way
            $this->assertStringContainsString(
                'Customers.id = :',
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
        $findings = (new CustomerCheckRegistry(ignore_inactive: false))->findings();

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
     * @link \App\Customers\Check\CustomerCheckRegistry::get()
     */
    public function testChecksAreKept(): void
    {
        $registry = new CustomerCheckRegistry();

        $this->assertSame($registry->get('missing_email'), $registry->get('missing_email'));
        $this->assertNull($registry->get('no_such_check'));
    }
}
