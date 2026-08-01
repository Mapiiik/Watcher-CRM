<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Enum\ConnectionHistorySource;
use App\Model\Enum\FirstSeenSource;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\ConnectionHistoryController Test Case
 *
 * @link \App\Controller\ConnectionHistoryController
 */
class ConnectionHistoryControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Customer the recorded intervals belong to
     */
    private const string CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Contract the recorded intervals belong to
     */
    private const string CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * Account in the source system, which lives in another database and is
     * therefore only ever referred to by its identifier
     */
    private const string ACCOUNT_ID = 'ab8f2c14-6d3e-4b91-9f0a-7c25d8e41b63';

    /**
     * Station shared by two accounts
     */
    private const string STATION_ID = 'aa:11:22:33:44:55';

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
        'app.Queues',
        'app.Services',
        'app.Contracts',
        'app.ConnectionHistory',
    ];

    /**
     * login method
     *
     * @return void
     */
    protected function login(): void
    {
        /** @var \App\Model\Table\AppUsersTable $usersTable */
        $usersTable = $this->getTableLocator()->get(Configure::read('Users.table', 'Users'));

        $user = $usersTable->newEmptyEntity();
        $user->username = 'tester';
        $user->role = 'admin';
        $user->active = true;

        $this->session(['Auth' => $user]);
    }

    /**
     * The listing renders and shows what was recorded.
     *
     * @return void
     * @link \App\Controller\ConnectionHistoryController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->record('smith.john', '10.20.0.1');

        $this->get('/connection-history');

        $this->assertResponseOk();
        $this->assertResponseContains('10.20.0.1');
        $this->assertResponseContains(self::STATION_ID);
    }

    /**
     * The access point and the board serving it answer different questions and
     * have to be told apart, a single place column leaves the reader guessing
     * which name is which.
     *
     * @return void
     * @link \App\Controller\ConnectionHistoryController::index()
     */
    public function testIndexKeepsTheAccessPointAndTheBoardApart(): void
    {
        $this->login();
        // names that are not substrings of one another, so finding one in the
        // response is no proof of the other having rendered
        $this->record('smith.john', '10.20.0.1', accessPoint: 'North Hill', routerosDevice: 'RB-north-01');

        $this->get('/connection-history');

        $this->assertResponseOk();
        // columns of their own, asserted through the sort links rather than the
        // headings, which are translated
        $this->assertResponseContains('sort=access_point_name');
        $this->assertResponseContains('sort=routeros_device_name');
        $this->assertResponseContains('North Hill');
        $this->assertResponseContains('RB-north-01');
    }

    /**
     * The address of the network access server gets a column of its own, so it
     * can be picked up and pasted into WinBox without hunting around it.
     *
     * @return void
     * @link \App\Controller\ConnectionHistoryController::index()
     */
    public function testIndexCarriesTheNasAddressOnItsOwn(): void
    {
        $this->login();
        $this->record('smith.john', '10.20.0.1', accessPoint: 'North Hill');

        $this->get('/connection-history');

        $this->assertResponseOk();
        $this->assertResponseContains('sort=nas_ip_address');
        $this->assertResponseContains('<td>10.20.0.1</td>');
    }

    /**
     * With no access point known the column stays empty rather than repeating
     * the address standing next to it.
     *
     * @return void
     * @link \App\Controller\ConnectionHistoryController::index()
     */
    public function testIndexLeavesAnUnknownAccessPointBlank(): void
    {
        $this->login();
        $this->record('smith.john', '10.20.0.1');

        $this->get('/connection-history');

        $this->assertResponseOk();
        $this->assertResponseContains('<td>10.20.0.1</td>');
        // once for the address column and nowhere else
        $this->assertSame(1, substr_count($this->_getBodyAsString(), '<td>10.20.0.1</td>'));
    }

    /**
     * Outside the card of a customer the listing has to say who each interval
     * belongs to, there is nothing else on the page saying it.
     *
     * @return void
     * @link \App\Controller\ConnectionHistoryController::index()
     */
    public function testIndexShowsTheCustomerAndContractOutsideTheirCards(): void
    {
        $this->login();
        $this->record('smith.john', '10.20.0.1');

        $this->get('/connection-history');

        $this->assertResponseOk();
        $this->assertResponseContains('sort=Customers.company');
        $this->assertResponseContains('sort=Contracts.number');
    }

    /**
     * Inside the card of a customer the customer column would only repeat what
     * the page already says, while the contract one still earns its place: a
     * customer may hold several.
     *
     * @return void
     * @link \App\Controller\ConnectionHistoryController::index()
     */
    public function testIndexDropsOnlyTheCustomerColumnInsideTheCustomerCard(): void
    {
        $this->login();
        $this->record('smith.john', '10.20.0.1');

        $this->get('/customers/' . self::CUSTOMER_ID . '/connection-history');

        $this->assertResponseOk();
        $this->assertResponseContains('10.20.0.1');
        $this->assertResponseNotContains('sort=Customers.company');
        $this->assertResponseContains('sort=Contracts.number');
    }

    /**
     * Inside the card of a contract both are already settled.
     *
     * @return void
     * @link \App\Controller\ConnectionHistoryController::index()
     */
    public function testIndexDropsBothColumnsInsideTheContractCard(): void
    {
        $this->login();
        $this->record('smith.john', '10.20.0.1');

        $this->get('/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID . '/connection-history');

        $this->assertResponseOk();
        $this->assertResponseNotContains('sort=Customers.company');
        $this->assertResponseNotContains('sort=Contracts.number');
    }

    /**
     * The account is worth following back to the RADIUS side.
     *
     * @return void
     * @link \App\Controller\ConnectionHistoryController::index()
     */
    public function testIndexLinksTheAccountToItsRadiusRecord(): void
    {
        $this->login();
        $this->record('smith.john', '10.20.0.1', accountId: self::ACCOUNT_ID);

        $this->get('/connection-history');

        $this->assertResponseOk();
        $this->assertResponseContains('/radius/accounts/view/' . self::ACCOUNT_ID);
    }

    /**
     * The reference says what it is a reference to, which is what lets the
     * column stay headed "Source Reference" once there are other sources.
     *
     * @return void
     * @link \App\Controller\ConnectionHistoryController::index()
     */
    public function testIndexNamesWhatTheReferenceRefersTo(): void
    {
        $this->login();
        $this->record('smith.john', '10.20.0.1', accountId: self::ACCOUNT_ID);

        $this->get('/connection-history');

        $this->assertResponseOk();
        $this->assertResponseContains(
            ConnectionHistorySource::Radius->referenceLabel() . ': ',
        );
    }

    /**
     * An account already gone from the source leaves nothing to follow, but its
     * name still has to show, that is the whole point of keeping it as text.
     *
     * @return void
     * @link \App\Controller\ConnectionHistoryController::index()
     */
    public function testIndexKeepsTheAccountNameWithoutARecordToLinkTo(): void
    {
        $this->login();
        $this->record('smith.john', '10.20.0.1');

        $this->get('/connection-history');

        $this->assertResponseOk();
        $this->assertResponseContains('smith.john');
        $this->assertResponseNotContains('/radius/accounts/view/');
    }

    /**
     * A start that is only a lower bound has to say so, an exact one must not.
     *
     * @return void
     * @link \App\Controller\ConnectionHistoryController::index()
     */
    public function testIndexMarksAnUncertainStart(): void
    {
        $this->login();
        $this->record('smith.john', '10.20.0.1', firstSeenSource: FirstSeenSource::InitialLoad);

        $this->get('/connection-history');

        $this->assertResponseOk();
        // the marker rather than its wording, which is translated
        $this->assertResponseContains('class="approximate"');
    }

    /**
     * A start taken from an observed session carries no such warning.
     *
     * @return void
     * @link \App\Controller\ConnectionHistoryController::index()
     */
    public function testIndexLeavesAnExactStartUnmarked(): void
    {
        $this->login();
        $this->record('smith.john', '10.20.0.1');

        $this->get('/connection-history');

        $this->assertResponseOk();
        $this->assertResponseNotContains('class="approximate"');
    }

    /**
     * Filtering by account is what the link from the RADIUS monitoring relies on.
     *
     * @return void
     * @link \App\Controller\ConnectionHistoryController::index()
     */
    public function testIndexFiltersByAccount(): void
    {
        $this->login();
        $this->record('smith.john', '10.20.0.1');
        $this->record('doe.jane', '10.20.0.9');

        $this->get('/connection-history?source_reference=smith.john');

        $this->assertResponseOk();
        $this->assertResponseContains('10.20.0.1');
        $this->assertResponseNotContains('10.20.0.9');
    }

    /**
     * The heading says which interval is open, not where it happened: the place
     * is already spelled out beside it and does not tell two intervals of one
     * account apart, whereas the period does.
     *
     * @return void
     * @link \App\Controller\ConnectionHistoryController::view()
     */
    public function testViewIsHeadedByThePeriodAndTheReference(): void
    {
        $this->login();
        $interval = $this->record('smith.john', '10.20.0.1', accessPoint: 'North Hill');

        $this->get('/connection-history/view/' . $interval->id);

        $this->assertResponseOk();
        $this->assertResponseContains(
            ' &middot; ' . ConnectionHistorySource::Radius->referenceLabel() . ': smith.john</h3>',
        );
        $this->assertResponseNotContains('<h3>North Hill');
    }

    /**
     * The detail renders and points out the same station under another account.
     *
     * @return void
     * @link \App\Controller\ConnectionHistoryController::view()
     */
    public function testViewShowsTheSameStationElsewhere(): void
    {
        $this->login();
        $interval = $this->record('smith.john', '10.20.0.1');
        $elsewhere = $this->record('smith.jane', '10.20.0.1');

        $this->get('/connection-history/view/' . $interval->id);

        $this->assertResponseOk();
        $this->assertResponseContains('smith.jane');
        $this->assertResponseContains('/connection-history/view/' . $elsewhere->id);
    }

    /**
     * A station seen under one account only has nothing to point out.
     *
     * @return void
     * @link \App\Controller\ConnectionHistoryController::view()
     */
    public function testViewSaysNothingAboutAStationSeenOnce(): void
    {
        $this->login();
        $interval = $this->record('smith.john', '10.20.0.1');

        $this->get('/connection-history/view/' . $interval->id);

        $this->assertResponseOk();
        $this->assertResponseNotContains('/connection-history/view/' . $interval->id . '"');
    }

    /**
     * Records one interval.
     *
     * @param string $account Account the interval belongs to.
     * @param string $nasIpAddress Address of the network access server.
     * @param \App\Model\Enum\FirstSeenSource $firstSeenSource How accurate the start is.
     * @param string|null $accessPoint Access point as the NMS named it.
     * @param string|null $routerosDevice Board as the NMS named it.
     * @param string|null $accountId Account in the source system, if still known.
     * @return \App\Model\Entity\ConnectionHistory
     */
    private function record(
        string $account,
        string $nasIpAddress,
        FirstSeenSource $firstSeenSource = FirstSeenSource::Session,
        ?string $accessPoint = null,
        ?string $routerosDevice = null,
        ?string $accountId = null,
    ): mixed {
        $table = $this->getTableLocator()->get('ConnectionHistory');

        $interval = $table->newEmptyEntity();
        $interval->source = ConnectionHistorySource::Radius;
        $interval->source_reference = $account;
        $interval->customer_id = self::CUSTOMER_ID;
        $interval->contract_id = self::CONTRACT_ID;
        $interval->account_id = $accountId;
        $interval->station_id = self::STATION_ID;
        $interval->nas_ip_address = $nasIpAddress;
        $interval->nas_port_id = 'ether1';
        $interval->ip_address = '192.168.50.10';
        $interval->access_point_name = $accessPoint;
        $interval->routeros_device_name = $routerosDevice;
        $interval->first_seen = new DateTime('2026-02-03 17:12:00');
        $interval->first_seen_source = $firstSeenSource;
        $interval->last_seen = new DateTime('2026-03-04 20:15:00');

        return $table->saveOrFail($interval);
    }
}
