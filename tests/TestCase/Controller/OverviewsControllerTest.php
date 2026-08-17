<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\OverviewsController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\OverviewsController Test Case
 */
#[UsesClass(OverviewsController::class)]
class OverviewsControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

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
        'app.Emails',
        'app.Phones',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.Labels',
        'app.CustomerLabels',
        'app.Queues',
        'app.Services',
        'app.Billings',
    ];

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\OverviewsController::index()
     */
    public function testIndex(): void
    {
        $this->login();

        $this->get('/overviews');

        $this->assertResponseOk();
        // the whole table of historical connections is reachable from here, there is
        // nowhere else to get at it outside a single customer or contract
        $this->assertResponseContains('/historical-connections');
    }

    /**
     * Test overview of contracts method
     *
     * The listing is sorted by columns of the customers while it eager loads the billings of the
     * contracts - the combination the `subquery` strategy of CakePHP 5.4 turns into an `ORDER BY`
     * over a column that is neither grouped nor aggregated, hence the `select` strategy there.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfContracts()
     */
    public function testOverviewOfContracts(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-contracts');

        $this->assertResponseOk();

        /** @var iterable<\App\Model\Entity\Contract> $contracts */
        $contracts = $this->viewVariable('contracts');
        $contracts = iterator_to_array($contracts, false);

        $this->assertNotEmpty($contracts);
        // the eager loaded branches the listing renders
        $this->assertNotEmpty($contracts[0]->billings);
        $this->assertNotNull($contracts[0]->billings[0]->service);
        $this->assertNotNull($contracts[0]->billings[0]->service->queue);
        $this->assertNotNull($contracts[0]->customer->emails);
        $this->assertNotNull($contracts[0]->customer->phones);
    }

    /**
     * Test overview of active services method
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfActiveServices()
     */
    public function testOverviewOfActiveServices(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-active-services');

        $this->assertResponseOk();
    }

    /**
     * Test overview of Czech customer connection points method
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfCzechCustomerConnectionPoints()
     */
    public function testOverviewOfCzechCustomerConnectionPoints(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-czech-customer-connection-points');

        $this->assertResponseOk();
    }

    /**
     * Test overview of Czech customer connection speeds method
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfCzechCustomerConnectionSpeeds()
     */
    public function testOverviewOfCzechCustomerConnectionSpeeds(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-czech-customer-connection-speeds');

        $this->assertResponseOk();

        $connection_points = [];
        foreach ($this->viewVariable('cto_categories') as $category_points) {
            foreach ($category_points as $connection_point) {
                $connection_points[] = $connection_point;
            }
        }

        $this->assertNotEmpty($connection_points, 'the fixtures have to reach the overview at all');

        // the template reads every speed bucket as a property, which an ArrayObject
        // only permits with ARRAY_AS_PROPS - without the flag the counts silently
        // render as empty cells instead of failing
        foreach ($connection_points as $connection_point) {
            foreach (['advertised_speeds', 'advertised_speeds_nonbusiness'] as $buckets) {
                foreach (array_keys($connection_point->{$buckets}->getArrayCopy()) as $speed) {
                    $this->assertTrue(
                        isset($connection_point->{$buckets}->{$speed}),
                        sprintf('%s.%s is unreachable as a property', $buckets, $speed),
                    );
                }
            }
        }
    }

    /**
     * Test overview of dealer commissions method
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfDealerCommissions()
     */
    public function testOverviewOfDealerCommissions(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-dealer-commissions');

        $this->assertResponseOk();
    }

    /**
     * Asked for with nothing said about the checks, every check that is a list of faults
     * runs and the informational one does not.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfAddressProblems()
     */
    public function testOverviewOfAddressProblemsRunsTheDefaultChecks(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-address-problems');

        $this->assertResponseOk();

        $shown = $this->viewVariable('shown');

        $this->assertTrue($shown['unclear_billing_address']);
        $this->assertTrue($shown['missing_installation_address']);
        $this->assertTrue($shown['unregistered_installation_address']);
        $this->assertFalse($shown['several_contracts_at_one_address']);

        // a check nobody asked for must not have been run
        $this->assertArrayNotHasKey('several_contracts_at_one_address', $this->viewVariable('results'));
        $this->assertArrayHasKey('unclear_billing_address', $this->viewVariable('results'));
    }

    /**
     * The reading of the query string rests on an unticked box saying so rather than saying
     * nothing, which is the hidden zero `FormHelper` puts beside every checkbox. Without it
     * the form could only ever switch checks on.
     *
     * @return void
     */
    public function testTheCheckBoxesSendAZeroWhenUnticked(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-address-problems');

        $this->assertResponseOk();
        $this->assertResponseContains('name="checks[duplicate_address]" value="0"');
        $this->assertResponseContains('name="checks[duplicate_address]" value="1"');
    }

    /**
     * The informational check is there for whoever asks for it.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfAddressProblems()
     */
    public function testOverviewOfAddressProblemsRunsTheOptionalCheckWhenAsked(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-address-problems?checks[several_contracts_at_one_address]=1');

        $this->assertResponseOk();
        $this->assertTrue($this->viewVariable('shown')['several_contracts_at_one_address']);
        $this->assertArrayHasKey('several_contracts_at_one_address', $this->viewVariable('results'));
    }

    /**
     * Switching one check off leaves the others alone. An unticked box sends a zero of its
     * own rather than nothing, so this is the branch a wrong reading of the query string
     * would take down - either by dropping everything or by ignoring the zero.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfAddressProblems()
     */
    public function testOverviewOfAddressProblemsTurnsOffOnlyWhatWasUnticked(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-address-problems?checks[duplicate_address]=0');

        $this->assertResponseOk();

        $shown = $this->viewVariable('shown');

        $this->assertFalse($shown['duplicate_address']);
        $this->assertTrue($shown['unclear_billing_address']);
        $this->assertTrue($shown['missing_installation_address']);

        $this->assertArrayNotHasKey('duplicate_address', $this->viewVariable('results'));
    }

    /**
     * The card offers this overview to two roles the overviews otherwise do not admit, so
     * the link has to lead somewhere for them - and the rack of overviews still must not.
     *
     * @param string $role Role to sign in as.
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfAddressProblems()
     */
    #[DataProvider('addressProblemRoles')]
    public function testOverviewOfAddressProblemsIsOpenToTheCardsRoles(string $role): void
    {
        $this->login($role);

        $this->get('/overviews/overview-of-address-problems');

        $this->assertResponseOk();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function addressProblemRoles(): array
    {
        return [
            'bookkeeper' => ['bookkeeper'],
            'sales representative' => ['sales-representative'],
            'sales manager' => ['sales-manager'],
        ];
    }

    /**
     * Opening the one overview does not open the rest of them.
     *
     * @return void
     */
    public function testTheAddressProblemsRoleIsNotLetIntoTheOtherOverviews(): void
    {
        $this->login('bookkeeper');

        $this->get('/overviews');

        // a role that may not reach an action is sent away rather than shown it
        $this->assertRedirect();
    }
}
