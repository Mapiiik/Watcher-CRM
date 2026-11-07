<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\OverviewsController;
use App\Model\Enum\ContractPeriodSource;
use App\Model\Enum\CustomerLegalStatus;
use App\Test\Traits\ControllerTestTrait;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
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
     * A billing of the fixtures that is still open.
     *
     * @var string
     */
    private const OPEN_BILLING_ID = 'b2000000-0000-4000-8000-000000000002';

    /**
     * A service of the fixtures with a connection profile.
     *
     * @var string
     */
    private const CONNECTION_SERVICE_ID = 'eaacfeb3-1430-43ce-842e-497c5c95d953';

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
        'app.Emails',
        'app.Phones',
        'app.Commissions',
        'app.DealerCommissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.ContractVersions',
        'app.Labels',
        'app.CustomerLabels',
        'app.ConnectionProfiles',
        'app.AvailableConnections',
        'app.Services',
        'app.Billings',
        'plugin.Radius.Accounts',
        'plugin.Radius.Radacct',
        'plugin.Radius.Radreply',
        'plugin.Radius.Radusergroup',
        'app.ServiceOverrides',
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
     * The filter form sends every one of its fields, so a change to any filter arrives with the
     * address left empty, and a value in no known shape is no filter either.
     *
     * @param string $query What the form sends.
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfContracts()
     */
    #[TestWith(['registry_address_id='])]
    #[TestWith(['registry_address_id=nonsense'])]
    #[TestWith(['registry_address_id=cz|12345678'])]
    public function testOverviewOfContractsTakesWhatTheFilterFormSends(string $query): void
    {
        $this->login();

        $this->get('/overviews/overview-of-contracts?' . $query);

        $this->assertResponseOk();
    }

    /**
     * A contact is whatever somebody wrote down, so it is shown as text.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfContracts()
     */
    public function testTheContactsOfTheCustomersAreText(): void
    {
        $this->contactsWithMarkup();
        $this->login();

        $this->get('/overviews/overview-of-contracts');

        $this->assertResponseOk();
        $this->assertContactsAreText();
    }

    /**
     * Contacts that would come out as markup if they were not escaped.
     *
     * @return void
     */
    private function contactsWithMarkup(): void
    {
        $this->fetchTable('Emails')->updateAll(['email' => '<i>mail</i>'], ['1 = 1']);
        $this->fetchTable('Phones')->updateAll(['phone' => '<i>phone</i>'], ['1 = 1']);
    }

    /**
     * What was written as a contact is on the page as text.
     *
     * @return void
     */
    private function assertContactsAreText(): void
    {
        $this->assertResponseContains('&lt;i&gt;mail&lt;/i&gt;');
        $this->assertResponseContains('&lt;i&gt;phone&lt;/i&gt;');
        $this->assertResponseNotContains('<i>mail</i>');
        $this->assertResponseNotContains('<i>phone</i>');
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
        $this->assertNotNull($contracts[0]->billings[0]->service->connection_profile);
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
     * Who is in business is read off the legal status, as in every other report. A sole trader whose
     * number nobody filled in is still one, and a person with a number on file need not be.
     *
     * @param string $status The customer's legal status.
     * @param string|null $number Their identity number.
     * @param int $uses How many uses of the service are expected to count as not in business.
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfActiveServices()
     */
    #[DataProvider('whoIsInBusiness')]
    public function testOverviewOfActiveServicesGoesByTheLegalStatus(string $status, ?string $number, int $uses): void
    {
        $this->getTableLocator()->get('Customers')->updateAll(
            ['legal_status' => $status, 'identity_number' => $number],
            ['id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481'],
        );

        $this->login();
        $this->get('/overviews/overview-of-active-services');

        $this->assertResponseOk();

        $service = (new Collection($this->viewVariable('services')))
            ->firstMatch(['id' => '5f6a2f47-0a4d-4c05-9bcb-2f0dc0a3f0d2']);

        $this->assertSame($uses, $service->number_of_uses_nonbusiness);
    }

    /**
     * @return array<string, array{string, string|null, int}>
     */
    public static function whoIsInBusiness(): array
    {
        return [
            'a sole trader without a number' => [CustomerLegalStatus::SoleTrader->value, null, 0],
            'a natural person with a number' => [CustomerLegalStatus::NaturalPerson->value, '12345678', 2],
        ];
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
     * A category's file downloads under its own name even when there is nothing in it, and a
     * category ČTÚ does not have is not a file at all.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfCzechCustomerConnectionPoints()
     */
    public function testACategoryDownloadsAsItsOwnFile(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-czech-customer-connection-points/s2_catv.csv');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Disposition', 's2_catv.csv');
        $this->assertResponseContains('DOCSIS');

        $this->get('/overviews/overview-of-czech-customer-connection-points/s2_nothing.csv');
        $this->assertResponseCode(404);
    }

    /**
     * A building wired with nobody in it yet goes to ČTÚ as a covered address point with no
     * connections. The registry is not asked, so the address is the one recorded.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfCzechCustomerConnectionPoints()
     */
    public function testAWiredBuildingWithNobodyInItIsReported(): void
    {
        Configure::write('Addresses.url', '');
        $this->login();

        $this->get('/overviews/overview-of-czech-customer-connection-points/s2_ftth.csv?month_to_display=2026-09');

        $this->assertResponseOk();
        $this->assertResponseContains(
            "16936132;s2_ftth;0;0;ANO;1000;1000;1000;1000;1;Luční 464, Podmoklice, 51301 Semily\r\n",
        );
    }

    /**
     * ČTÚ asks for the state on the last day: a connection cancelled before it is left out, also
     * when it ran for most of the month.
     *
     * @return void
     * @link \App\RegulatoryReporting\ConnectionPointCollector::collect()
     */
    public function testAConnectionCancelledBeforeTheLastDayIsNotReported(): void
    {
        Configure::write('Addresses.url', '');
        $this->getTableLocator()->get('Addresses')->updateAll(['address_registry_reference' => '11111111'], ['1 = 1']);
        $billings = $this->getTableLocator()->get('Billings');
        $billings->deleteAll(['id !=' => self::OPEN_BILLING_ID]);
        $billings->updateAll(['billing_from' => '2020-01-01', 'service_id' => self::CONNECTION_SERVICE_ID], ['1 = 1']);
        $this->login();
        $points = '/overviews/overview-of-czech-customer-connection-points/s2_wifi.csv?month_to_display=2026-06-15';
        $speeds = '/overviews/overview-of-czech-customer-connection-speeds?month_to_display=2026-06-15';

        $billings->updateAll(['billing_until' => '2026-06-30'], ['1 = 1']);
        $this->get($points);
        $this->assertResponseContains('11111111;s2_wifi;1');
        $this->get($speeds);
        $this->assertNotEmpty($this->viewVariable('cto_categories')->toArray());

        $billings->updateAll(['billing_until' => '2026-06-29'], ['1 = 1']);
        $this->get($points);
        $this->assertResponseNotContains('11111111');
        $this->get($speeds);
        $this->assertEmpty($this->viewVariable('cto_categories')->toArray());
    }

    /**
     * HAKOM's quarterly rows come as a page and as a file with the form's own headers.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfCroatianQuarterlyReport()
     */
    public function testOverviewOfCroatianQuarterlyReport(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-croatian-quarterly-report?year=2026&quarter=3');
        $this->assertResponseOk();
        $this->assertResponseContains('Usluge i paketi usluga');

        $this->get('/overviews/overview-of-croatian-quarterly-report.csv?year=2026&quarter=3');
        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Disposition', 'hakom-2026Q3.csv');
        $this->assertResponseContains('Obrazac;Pokazatelj;Naziv;Vrijednost;Mjerna jedinica');
    }

    /**
     * HAKOM's address listing comes as a page and as a file.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfCroatianConnectionPoints()
     */
    public function testOverviewOfCroatianConnectionPoints(): void
    {
        Configure::write('Addresses.url', '');
        $this->login();

        $this->get('/overviews/overview-of-croatian-connection-points');
        $this->assertResponseOk();

        $this->get('/overviews/overview-of-croatian-connection-points.csv?month_to_display=2026-09');
        $this->assertResponseOk();
        $this->assertResponseContains('kb_id;zu_ime;jls_ime;na_ime;ul_ime;kb;infrastructure_owner');
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
     * A dealer is paid from what their contracts bill in the month, so a billing that runs in
     * another month or on a contract of nobody's commission must not reach the sum.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfDealerCommissions()
     */
    public function testOverviewOfDealerCommissions(): void
    {
        $this->fetchTable('Billings')->updateAll(['fixed_discount' => 0, 'percentage_discount' => 0], ['1 = 1']);
        $this->login();

        $this->assertSame(1.0, $this->dealerTotalIn('2021-11'), 'Only the billing of that month.');
        $this->assertSame(0.0, $this->dealerTotalIn('2021-12'), 'A month with no billing running.');
        $this->assertSame(2.0, $this->dealerTotalIn('2022-03'), 'The billing that has not ended.');
    }

    /**
     * What the only dealer of the fixtures is paid from in a month.
     *
     * @param string $month The month, as the filter sends it.
     * @return float
     */
    private function dealerTotalIn(string $month): float
    {
        $this->get('/overviews/overview-of-dealer-commissions?month_to_display=' . $month);
        $this->assertResponseOk();

        /** @var \Cake\Collection\CollectionInterface<string, list<\App\Model\Entity\DealerCommission>> $dealers */
        $dealers = $this->viewVariable('dealers');
        $commissions = array_values($dealers->toArray());
        $this->assertCount(1, $commissions);
        $this->assertCount(1, $commissions[0]);

        return (float)$commissions[0][0]->commission->get('total_price');
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
     * Customers with nothing running are passed over unless somebody asks for them, and the
     * tick that lifts that is read the same way as the ones beside it.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfAddressProblems()
     */
    public function testOverviewOfAddressProblemsIgnoresTheDormantByDefault(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-address-problems');

        $this->assertResponseOk();
        $this->assertTrue($this->viewVariable('ignore_inactive'));
        $this->assertResponseContains('name="ignore_inactive" value="0"');

        $this->get('/overviews/overview-of-address-problems?ignore_inactive=0');

        $this->assertResponseOk();
        $this->assertFalse($this->viewVariable('ignore_inactive'));

        // and lifting it leaves the per-check ticks where they were
        $this->assertTrue($this->viewVariable('shown')['unclear_billing_address']);
        $this->assertFalse($this->viewVariable('shown')['several_contracts_at_one_address']);
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
     * The contract checks read the query string exactly as the address ones do, and are
     * offered to the same roles - so what is tested there is tested here too, once.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfContractProblems()
     */
    public function testOverviewOfContractProblemsRunsTheDefaultChecks(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-contract-problems');

        $this->assertResponseOk();
        $this->assertTrue($this->viewVariable('shown')['billing_gap']);
        $this->assertArrayHasKey('billing_gap', $this->viewVariable('results'));
    }

    /**
     * The RADIUS checks come from their plugin and are listed by its own element. The fixtures
     * put the account in another group than its tariff names.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfContractProblems()
     */
    public function testOverviewOfContractProblemsListsTheRadiusAccounts(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-contract-problems');

        $this->assertResponseOk();
        $this->assertTrue($this->viewVariable('shown')['radius_group_mismatch']);
        $this->assertCount(1, $this->viewVariable('results')['radius_group_mismatch']);
        $this->assertResponseContains('/radius/accounts/view/ab8f2c14-6d3e-4b91-9f0a-7c25d8e41b63');
    }

    /**
     * An account holding an address its contract does not have is listed with the address.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfContractProblems()
     */
    public function testOverviewOfContractProblemsListsAnAddressOutsideItsContract(): void
    {
        $account = $this->fetchTable('Radius.Accounts')->find()->firstOrFail();
        $this->fetchTable('Radius.Radreply')->updateAll(
            ['username' => $account->get('username'), 'attribute' => 'Framed-IP-Address', 'value' => '192.0.2.10'],
            ['1 = 1'],
        );

        $this->login();

        $this->get('/overviews/overview-of-contract-problems');

        $this->assertResponseOk();
        $this->assertCount(1, $this->viewVariable('results')['radius_address_outside_contract']);
        $this->assertResponseContains('<td>192.0.2.10</td>');
    }

    /**
     * Breaks that are long over are passed over unless somebody asks for them.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfContractProblems()
     */
    public function testOverviewOfContractProblemsIgnoresWhatIsOverByDefault(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-contract-problems');

        $this->assertResponseOk();
        $this->assertTrue($this->viewVariable('ignore_inactive'));

        $this->get('/overviews/overview-of-contract-problems?ignore_inactive=0');

        $this->assertResponseOk();
        $this->assertFalse($this->viewVariable('ignore_inactive'));
    }

    /**
     * A check switched off does not run.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfContractProblems()
     */
    public function testOverviewOfContractProblemsTurnsOffWhatWasUnticked(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-contract-problems?checks[billing_gap]=0');

        $this->assertResponseOk();
        $this->assertFalse($this->viewVariable('shown')['billing_gap']);
        $this->assertArrayNotHasKey('billing_gap', $this->viewVariable('results'));
    }

    /**
     * The customer checks read the query string exactly as the two families above do.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfCustomerProblems()
     */
    public function testOverviewOfCustomerProblemsRunsTheDefaultChecks(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-customer-problems');

        $this->assertResponseOk();
        $this->assertTrue($this->viewVariable('shown')['missing_email']);
        $this->assertArrayHasKey('missing_email', $this->viewVariable('results'));

        $this->get('/overviews/overview-of-customer-problems?checks[missing_email]=0&ignore_inactive=0');

        $this->assertResponseOk();
        $this->assertFalse($this->viewVariable('shown')['missing_email']);
        $this->assertFalse($this->viewVariable('ignore_inactive'));
        $this->assertArrayNotHasKey('missing_email', $this->viewVariable('results'));
    }

    /**
     * The card offers this overview to the same roles as the address one, so the link has to
     * lead somewhere for them - and the rack of overviews still must not.
     *
     * @param string $role Role to sign in as.
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfContractProblems()
     */
    #[DataProvider('addressProblemRoles')]
    public function testOverviewOfContractProblemsIsOpenToTheCardsRoles(string $role): void
    {
        $this->login($role);

        $this->get('/overviews/overview-of-contract-problems');

        $this->assertResponseOk();
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
     * Whoever is let into the rack of overviews at all works the whole file rather than one
     * customer at a time, so what does not add up in it is theirs to look up as much as
     * anybody's. Network managers were the one such role the checks were closed to.
     *
     * @param string $overview The check overview to open.
     * @return void
     */
    #[DataProvider('checkOverviews')]
    public function testTheChecksAreOpenToWhoeverIsLetIntoTheOverviews(string $overview): void
    {
        $this->login('network-manager');

        $this->get('/overviews');
        $this->assertResponseOk('A role that cannot open the rack proves nothing about the rest.');

        $this->get($overview);
        $this->assertResponseOk();
    }

    /**
     * Being let into the checks is not being let into everything else in the rack.
     *
     * @param string $overview The check overview a role reaches by name.
     * @return void
     */
    #[DataProvider('checkOverviews')]
    public function testTheChecksStayShutToWhoeverTheOverviewsAreShutTo(string $overview): void
    {
        $this->login('network-technician');

        $this->get($overview);

        // a role that may not reach an action is sent away rather than shown it
        $this->assertRedirect();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function checkOverviews(): array
    {
        return [
            'addresses' => ['/overviews/overview-of-address-problems'],
            'contracts' => ['/overviews/overview-of-contract-problems'],
            'customers' => ['/overviews/overview-of-customer-problems'],
        ];
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
     * Being let into the rack is not being let into everything standing in it. The rack only
     * lists what whoever opened it may follow, so what is shut stays shut behind it.
     *
     * @return void
     */
    public function testTheChecksDoNotOpenTheOtherOverviewsBesideThem(): void
    {
        $this->login('bookkeeper');

        $this->get('/overviews');
        $this->assertResponseOk();

        $this->get('/overviews/overview-of-active-services');

        // a role that may not reach an action is sent away rather than shown it
        $this->assertRedirect();
    }

    /**
     * Asked nothing, the page answers about the month being lived through.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfNewAndEndingContracts()
     */
    public function testOverviewOfNewAndEndingContractsDefaultsToThisMonth(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-new-and-ending-contracts');

        $this->assertResponseOk();

        $today = new Date('now');
        $this->assertEquals($today->firstOfMonth(), $this->viewVariable('from'));
        $this->assertEquals($today->lastOfMonth(), $this->viewVariable('to'));
        $this->assertSame(ContractPeriodSource::Versions, $this->viewVariable('source'));
    }

    /**
     * The switch is read off the query string, where anybody can put anything - an array
     * among other things, which is what a `?string` would have fatalled on.
     *
     * @param string $query What arrives under the name.
     * @return void
     * @link \App\Model\Enum\ContractPeriodSource::fromQuery()
     */
    #[DataProvider('nonsenseSources')]
    public function testTheSourceFallsBackToTheVersions(string $query): void
    {
        $this->login();

        $this->get('/overviews/overview-of-new-and-ending-contracts?' . $query);

        $this->assertResponseOk();
        $this->assertSame(ContractPeriodSource::Versions, $this->viewVariable('source'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonsenseSources(): array
    {
        return [
            'a word that names nothing' => ['source=nonsense'],
            'the empty companion of a radio group' => ['source='],
            'an array where a name was expected' => ['source[]=versions'],
        ];
    }

    /**
     * The period the form shows is the period the tables answered about.
     *
     * Neither value source has anything to say when the query string is empty, so without
     * the value being passed outright the boxes open blank over tables that have already
     * decided on this month.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfNewAndEndingContracts()
     */
    public function testTheFormShowsThePeriodItActuallyUsed(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-new-and-ending-contracts');

        $this->assertResponseOk();

        $today = new Date('now');
        $this->assertResponseContains('value="' . $today->firstOfMonth()->toDateString() . '"');
        $this->assertResponseContains('value="' . $today->lastOfMonth()->toDateString() . '"');
        $this->assertResponseContains('type="radio"');
        $this->assertResponseContains('checked="checked"');
    }

    /**
     * Every control auto-submits, the radio among them - there being no submit button to
     * fall back on anywhere in the rack.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfNewAndEndingContracts()
     */
    public function testTheFilterSubmitsOnChange(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-new-and-ending-contracts');

        $this->assertResponseOk();
        $this->assertResponseContains('onchange="this.form.submit();"');
    }

    /**
     * A form clearing a multiple select sends an empty string beside it. Taken for a town,
     * it matches nothing and empties both tables.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfNewAndEndingContracts()
     */
    public function testClearingTheCityFilterIsNotFilteringByNothing(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-new-and-ending-contracts?from=1900-01-01&to=2100-01-01');
        $this->assertResponseOk();
        $unfiltered = $this->viewVariable('starting')->count();

        $this->get(
            '/overviews/overview-of-new-and-ending-contracts?from=1900-01-01&to=2100-01-01&cities[]=',
        );
        $this->assertResponseOk();

        $this->assertSame(
            $unfiltered,
            $this->viewVariable('starting')->count(),
            'Clearing the towns asks about all of them, not about none.',
        );
    }

    /**
     * A town is whatever somebody typed, so it is bound rather than spelled into the SQL -
     * unlike the labels beside it, which get away with it by being checked for UUIDs first.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfNewAndEndingContracts()
     */
    public function testTheCityFilterIsBoundRatherThanSpelled(): void
    {
        $this->login();

        $this->get(
            '/overviews/overview-of-new-and-ending-contracts'
            . '?from=1900-01-01&to=2100-01-01&cities[]=' . urlencode("' OR 1=1 --"),
        );

        $this->assertResponseOk();
        $this->assertCount(0, $this->viewVariable('starting'));
    }

    /**
     * What came in and what went out over a month is when invoicing starts and stops.
     *
     * @param string $role The office role to open it as.
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfNewAndEndingContracts()
     */
    #[DataProvider('addressProblemRoles')]
    public function testOverviewOfNewAndEndingContractsIsOpenToTheOfficeRoles(string $role): void
    {
        $this->login($role);

        $this->get('/overviews/overview-of-new-and-ending-contracts');

        $this->assertResponseOk();
    }

    /**
     * Network managers are admitted to the checks because what does not add up in the file
     * is theirs to look up. This is not a check but a commercial report, and the listing of
     * contracts beside it has always been shut to them.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfNewAndEndingContracts()
     */
    public function testOverviewOfNewAndEndingContractsStaysShutToNetworkManagers(): void
    {
        $this->login('network-manager');

        $this->get('/overviews');
        $this->assertResponseOk('A role that cannot open the rack proves nothing about the rest.');

        $this->get('/overviews/overview-of-new-and-ending-contracts');

        $this->assertRedirect();
    }

    /**
     * The contract number leads to the contract, nested under its customer.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfNewAndEndingContracts()
     */
    public function testTheContractNumberLeadsToTheContract(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-new-and-ending-contracts?from=1900-01-01&to=2100-01-01');

        $this->assertResponseOk();
        $contract = $this->viewVariable('starting')->first();
        $this->assertNotNull($contract, 'the fixtures have to reach the overview at all');
        $this->assertResponseContains(
            '/customers/' . $contract->customer_id . '/contracts/' . $contract->id . '">'
            . h($contract->number) . '</a>',
        );
    }

    /**
     * Asked nothing, the billings answer about the month being lived through.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfNewAndEndingBillings()
     */
    public function testOverviewOfNewAndEndingBillingsDefaultsToThisMonth(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-new-and-ending-billings');

        $this->assertResponseOk();

        $today = new Date('now');
        $this->assertEquals($today->firstOfMonth(), $this->viewVariable('from'));
        $this->assertEquals($today->lastOfMonth(), $this->viewVariable('to'));
    }

    /**
     * A billing starts on its first day and ends on its last, and one doing both inside the
     * period is in both listings - so the two sums cancel out.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfNewAndEndingBillings()
     */
    public function testABillingIsListedByItsFirstAndLastDay(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-new-and-ending-billings?from=2021-11-01&to=2021-11-30');

        $this->assertResponseOk();
        $this->assertSame(
            ['b1000000-0000-4000-8000-000000000001'],
            $this->viewVariable('starting')->extract('id')->toList(),
        );
        $this->assertSame(
            ['b1000000-0000-4000-8000-000000000001'],
            $this->viewVariable('ending')->extract('id')->toList(),
        );
        $this->assertTrue($this->viewVariable('startingTotal')->equals($this->viewVariable('endingTotal')));
    }

    /**
     * What starts is summed, and one still running is in no ending listing.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfNewAndEndingBillings()
     */
    public function testTheStartingBillingsAreSummed(): void
    {
        $this->login();

        $this->get('/overviews/overview-of-new-and-ending-billings?from=2022-01-01&to=2022-01-31');

        $this->assertResponseOk();
        $this->assertCount(2, $this->viewVariable('starting'));
        $this->assertCount(0, $this->viewVariable('ending'));
        $this->assertSame('5', $this->viewVariable('startingTotal')->trim()->toString());
    }

    /**
     * The filters narrow both listings down to what they name.
     *
     * @param string $query The filter as it arrives.
     * @param int $count How many of the January billings it leaves.
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfNewAndEndingBillings()
     */
    #[DataProvider('billingFilters')]
    public function testTheBillingFiltersNarrowTheListing(string $query, int $count): void
    {
        $this->login();

        $this->get('/overviews/overview-of-new-and-ending-billings?from=2021-11-01&to=2022-01-31&' . $query);

        $this->assertResponseOk();
        $this->assertCount($count, $this->viewVariable('starting'));
    }

    /**
     * @return array<string, array{string, int}>
     */
    public static function billingFilters(): array
    {
        return [
            'nothing' => ['', 3],
            'a service' => ['service_id=5f6a2f47-0a4d-4c05-9bcb-2f0dc0a3f0d2', 2],
            'a separate invoice' => ['separate_invoice=1', 1],
            'no separate invoice' => ['separate_invoice=0', 2],
            'the empty choice' => ['separate_invoice=', 3],
            'a cleared city select' => ['cities[]=', 3],
            'a service type nobody has' => ['service_type_id=00000000-0000-4000-8000-000000000000', 0],
        ];
    }

    /**
     * The billings are the same commercial report as the contracts, open to the same roles.
     *
     * @param string $role The office role to open it as.
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfNewAndEndingBillings()
     */
    #[DataProvider('addressProblemRoles')]
    public function testOverviewOfNewAndEndingBillingsIsOpenToTheOfficeRoles(string $role): void
    {
        $this->login($role);

        $this->get('/overviews/overview-of-new-and-ending-billings');

        $this->assertResponseOk();
    }

    /**
     * And shut to network managers the same way.
     *
     * @return void
     * @link \App\Controller\OverviewsController::overviewOfNewAndEndingBillings()
     */
    public function testOverviewOfNewAndEndingBillingsStaysShutToNetworkManagers(): void
    {
        $this->login('network-manager');

        $this->get('/overviews/overview-of-new-and-ending-billings');

        $this->assertRedirect();
    }
}
