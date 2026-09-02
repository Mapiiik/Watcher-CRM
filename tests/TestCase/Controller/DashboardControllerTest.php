<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\DashboardController;
use App\Model\Enum\IpAddressTypeOfUse;
use App\Test\Traits\ControllerTestTrait;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\I18n\Date;
use Cake\Routing\Router;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\DashboardController Test Case
 */
#[UsesClass(DashboardController::class)]
class DashboardControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * Customer the invoice and the contracts of the fixtures belong to.
     *
     * @var string
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
        'app.ContractVersions',
        'app.IpAddresses',
        'app.IpNetworks',
        'app.Labels',
        'app.CustomerLabels',
        'app.TaskStates',
        'app.TaskTypes',
        'app.Tasks',
        'app.TaskCollaborators',
        'plugin.Bookkeeping.Invoices',
    ];

    /**
     * The page must not sit at the bare `/dashboard`, whatever the router would otherwise
     * make of it.
     *
     * A plugin that ships a webroot has it linked into the application's own under the
     * plugin's name, and this plugin is named after the page it draws. A web server that
     * answers directories itself - nginx does, with the `$uri/` of its `try_files` - takes
     * that path before the router is asked, and finding no index file inside answers 403.
     * Caddy does not, so a rename back to `index` would pass every test and every look at
     * the development server, and break only where it is deployed.
     *
     * @return void
     * @link \Dashboard\Controller\Trait\DashboardControllerTrait::cards()
     */
    public function testTheDashboardIsNotAtThePathItsOwnAssetsOwn(): void
    {
        // nothing is requested here, so the routes are not otherwise loaded
        $this->loadRoutes();

        $this->assertDirectoryExists(
            Plugin::path('Dashboard') . 'webroot',
            'The plugin has a webroot, which is what takes the path.',
        );

        $this->assertNotSame(
            '/dashboard',
            Router::url(['controller' => 'Dashboard', 'action' => 'cards', 'plugin' => null]),
        );
    }

    /**
     * The root asks which page this user starts on, and the dashboard is the answer for
     * everybody who has not chosen another one.
     *
     * @return void
     * @link \App\Controller\HomeController::index()
     */
    public function testRootSendsYouToTheDashboard(): void
    {
        $this->login();
        $this->get('/');

        $this->assertRedirect('/dashboard/cards');

        $this->get('/dashboard/cards');

        $this->assertResponseOk();
        $this->assertNotEmpty($this->viewVariable('cards'));
    }

    /**
     * Signing in has to arrive at the root rather than at a page named here, as the root is
     * where the page a user starts on is decided. Naming the dashboard in the setting would
     * put that choice out of reach of everybody who arrives by signing in.
     *
     * @return void
     */
    public function testTheLoginLandsWhereTheChoiceIsMade(): void
    {
        // the plugin that owns this setting merges it in as the application boots, which a
        // request is what brings about
        $this->login();
        $this->get('/dashboard/cards');
        $this->assertResponseOk();

        $redirect = (string)Configure::read('Auth.AuthenticationComponent.loginRedirect');

        $this->assertSame(Router::url(['controller' => 'Home', 'action' => 'index', 'plugin' => null]), $redirect);

        $this->get($redirect);

        $this->assertRedirect('/dashboard/cards');

        $this->get('/dashboard/cards');

        $this->assertResponseOk();
        $this->assertNotEmpty($this->viewVariable('cards'));
    }

    /**
     * The cards sit on the panel every other index page uses, drawn as the grouped blocks
     * elsewhere are.
     *
     * @return void
     * @link \App\Controller\DashboardController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/dashboard/cards');

        $this->assertResponseOk();
        $this->assertResponseContains('class="dashboard cards content"');
        $this->assertResponseContains('class="dashboard-cards"');
        $this->assertResponseContains('class="related"');
        // served out of the plugin, so the path says which one it came from
        $this->assertResponseContains('/dashboard/css/dashboard.css');
        // the deferred cards are the only thing that fetches itself, so the script comes with
        // this page rather than with every page
        $this->assertResponseContains('js/lazy-load.js');
    }

    /**
     * A page with no deferred content does not drag the script that fetches it along.
     *
     * @return void
     * @link \App\Controller\DashboardController::index()
     */
    public function testTheLazyLoadScriptIsNotOnEveryPage(): void
    {
        $this->login();
        $this->get('/tasks');

        $this->assertResponseOk();
        $this->assertResponseNotContains('js/lazy-load.js');
    }

    /**
     * Every role has to get past the landing page, whatever it is then shown there. A role
     * that 403s here cannot sign in and reach anything at all.
     *
     * @param string $role The role to sign in as.
     * @return void
     * @link \App\Controller\DashboardController::index()
     */
    #[DataProvider('roleProvider')]
    public function testEveryRoleReachesTheDashboard(string $role): void
    {
        $this->login($role);
        $this->get('/dashboard/cards');

        $this->assertResponseOk();
    }

    /**
     * @return list<array{string}>
     */
    public static function roleProvider(): array
    {
        return [
            ['user'],
            ['customer-service-technician'],
            ['network-technician'],
            ['network-manager'],
            ['sales-representative'],
            ['sales-manager'],
            ['bookkeeper'],
            ['admin'],
        ];
    }

    /**
     * A technician is not offered the debtor cards, so their listing has to leave them out.
     *
     * @return void
     * @link \App\Dashboard\DashboardCardRegistry::forRole()
     */
    public function testCardsAreChosenByRole(): void
    {
        $this->login('customer-service-technician');
        $this->get('/dashboard/cards');

        $this->assertResponseOk();

        /** @var list<\Dashboard\Card\DashboardCardInterface> $cards */
        $cards = $this->viewVariable('cards');
        $ids = array_map(fn($card): string => $card->id(), $cards);

        $this->assertContains('my_tasks', $ids);
        $this->assertNotContains('debtors', $ids);
        $this->assertNotContains('unassigned_tasks', $ids);
        // chasing paperwork is the office's work, not the technician's
        $this->assertNotContains('unsigned_contracts', $ids);
    }

    /**
     * The card counts unsigned paperwork twice over, by two sets of deadlines, so both
     * queries have to actually run.
     *
     * @return void
     * @link \App\Dashboard\Card\UnsignedContractsCard::data()
     */
    public function testUnsignedContractsCard(): void
    {
        $this->login();
        $this->get('/dashboard/card/unsigned_contracts');

        $this->assertResponseOk();
        $this->assertResponseNotContains('<html');
    }

    /**
     * A deferred card is fetched on its own and answers with the bare fragment.
     *
     * @return void
     * @link \App\Controller\DashboardController::card()
     */
    public function testCard(): void
    {
        $this->login();
        $this->get('/dashboard/card/pressing_tasks');

        $this->assertResponseOk();
        $this->assertResponseNotContains('<html');
    }

    /**
     * The debtor cards count in SQL rather than by loading every unpaid invoice, so the
     * aggregate has to actually run.
     *
     * @return void
     * @link \Bookkeeping\Debtors\DebtorsProcessor::findFilteredOverdueDebtorIds()
     */
    public function testDebtorCards(): void
    {
        $this->login();
        $this->get('/dashboard/card/debtors');

        $this->assertResponseOk();

        $this->get('/dashboard/card/manual_shutoff_debtors');

        $this->assertResponseOk();
    }

    /**
     * The link under a card has to reproduce that card's set, so it names every filter the
     * listing keeps in the session rather than leaving the last one used in force.
     *
     * @return void
     * @link \App\Dashboard\Card\AbstractTaskListCard::listingUrl()
     */
    public function testTaskCardsLinkToTheirOwnSet(): void
    {
        $this->login();
        $this->get('/dashboard/card/pressing_tasks');

        $this->assertResponseOk();

        /** @var \Dashboard\Card\DashboardCardInterface $card */
        $card = $this->viewVariable('card');
        $query = $card->data()['url']['?'];

        $this->assertSame(1, $query['pressing']);
        $this->assertSame(0, $query['stale']);
        $this->assertSame(0, $query['show_completed']);
        // a filter the operator last used must not still be narrowing the listing
        $this->assertSame('', $query['user_id']);
        $this->assertSame('', $query['task_state_ids']);
        $this->assertSame('', $query['search']);
    }

    /**
     * A card that stands for a filter of its own puts that filter in its link, rather than the
     * blank the others clear theirs to.
     *
     * The unassigned card is the one that does, and it is what says the key the card writes and the
     * key the listing reads are still the same word.
     *
     * @return void
     * @link \App\Dashboard\Card\UnassignedTasksCard::data()
     */
    public function testTheUnassignedCardLinksToTheTasksNobodyHolds(): void
    {
        $this->login();
        $this->get('/dashboard/card/unassigned_tasks');

        $this->assertResponseOk();

        /** @var \Dashboard\Card\DashboardCardInterface $card */
        $card = $this->viewVariable('card');

        $this->assertSame('none', $card->data()['url']['?']['user_id']);
    }

    /**
     * The listing understands the filters those links carry, and narrows by them.
     *
     * @return void
     * @link \App\Controller\TasksController::index()
     */
    public function testTheListingUnderstandsTheCardFilters(): void
    {
        $this->login();
        $this->get('/tasks?pressing=1&stale=0&show_completed=0&user_id=');

        $this->assertResponseOk();

        $this->get('/tasks?pressing=0&stale=1&show_completed=0&user_id=');

        $this->assertResponseOk();
    }

    /**
     * The obligations card points at the listing narrowed the same way, and the listing
     * understands that narrowing.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::index()
     */
    public function testEndingObligationsLinkToTheirOwnSet(): void
    {
        $this->login();
        $this->get('/dashboard/card/ending_obligations');

        $this->assertResponseOk();

        /** @var \Dashboard\Card\DashboardCardInterface $card */
        $card = $this->viewVariable('card');
        $this->assertSame(1, $card->data()['url']['?']['obligations_ending']);

        $this->get('/contract-versions?obligations_ending=1');

        $this->assertResponseOk();
        $this->assertTrue($this->viewVariable('obligations_ending'));
    }

    /**
     * A sales representative only reaches the contract versions of one customer. The card
     * still links them there: following it turns them back with a flash rather than
     * answering, which says more than a link that quietly is not there.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::index()
     */
    public function testTheObligationsListingTurnsSalesRepresentativesBack(): void
    {
        $this->login('sales-representative');
        $this->get('/contract-versions?obligations_ending=1');

        $this->assertRedirect();
        $this->assertNull($this->viewVariable('contractVersions'));
        $this->assertFlashElement('flash/error');
    }

    /**
     * A label and a contract state each link into the customer listing narrowed to it.
     * Those fields sit behind the advanced search, so the link has to switch that on.
     *
     * @return void
     * @link \App\Dashboard\Card\AbstractCustomerListingCard::customerListingUrl()
     */
    public function testLabelsAndStatesLinkIntoTheCustomerListing(): void
    {
        $labels = $this->getTableLocator()->get('Labels');
        $label = $labels->find()->firstOrFail();
        $label->set('show_on_dashboard', true);
        $labels->saveOrFail($label);

        $states = $this->getTableLocator()->get('ContractStates');
        $state = $states->find()->firstOrFail();
        $state->set('show_on_dashboard', true);
        $states->saveOrFail($state);

        $this->login();
        $this->get('/dashboard/card/labels');

        $this->assertResponseOk();

        /** @var \Dashboard\Card\DashboardCardInterface $card */
        $card = $this->viewVariable('card');
        $query = $card->data()['urls'][$label->get('id')]['?'];

        $this->assertSame(1, $query['advanced_search']);
        $this->assertSame([$label->get('id')], $query['label_ids']);
        // the fields being cleared go as empty strings, which the URL keeps
        $this->assertSame('', $query['not_label_ids']);
        $this->assertSame('', $query['contract_state_id']);

        $this->get('/dashboard/card/contract_states');

        $this->assertResponseOk();

        /** @var \Dashboard\Card\DashboardCardInterface $card */
        $card = $this->viewVariable('card');
        $query = $card->data()['urls'][$state->get('id')]['?'];

        $this->assertSame($state->get('id'), $query['contract_state_id']);
        $this->assertSame('', $query['label_ids']);
    }

    /**
     * A label and a contract state alike are drawn only for the roles they name, and
     * naming none draws them for everybody.
     *
     * @return void
     * @link \App\Model\Entity\Trait\DashboardVisibilityTrait::isOnDashboardFor()
     */
    public function testDashboardRolesNarrowLabelsAndStates(): void
    {
        foreach (['Labels' => 'labels', 'ContractStates' => 'contract_states'] as $alias => $card) {
            $table = $this->getTableLocator()->get($alias);
            $record = $table->find()->firstOrFail();
            $record->set('show_on_dashboard', true);
            $record->set('dashboard_roles', ['bookkeeper']);
            $table->saveOrFail($record);

            $this->login('bookkeeper');
            $this->get('/dashboard/card/' . $card);
            $this->assertResponseOk();
            /** @var \Dashboard\Card\DashboardCardInterface $offered */
            $offered = $this->viewVariable('card');
            $this->assertNotSame([], $offered->data()['urls'], $alias . ' for the role it names');

            $this->login('sales-manager');
            $this->get('/dashboard/card/' . $card);
            $this->assertResponseOk();
            /** @var \Dashboard\Card\DashboardCardInterface $withheld */
            $withheld = $this->viewVariable('card');
            $this->assertSame([], $withheld->data()['urls'], $alias . ' for a role it does not name');

            // naming no role at all puts it back in front of everybody
            $record->set('dashboard_roles', []);
            $table->saveOrFail($record);

            $this->get('/dashboard/card/' . $card);
            $this->assertResponseOk();
            /** @var \Dashboard\Card\DashboardCardInterface $forAll */
            $forAll = $this->viewVariable('card');
            $this->assertNotSame([], $forAll->data()['urls'], $alias . ' naming no role');
        }
    }

    /**
     * The customer listing narrows by what those links carry.
     *
     * @return void
     * @link \App\Controller\CustomersController::index()
     */
    public function testTheCustomerListingUnderstandsThoseFilters(): void
    {
        $states = $this->getTableLocator()->get('ContractStates');
        $state = $states->find()->firstOrFail();

        $this->login();
        $this->get(
            '/customers?advanced_search=1&contract_state_id=' . $state->get('id')
            . '&label_ids=&not_label_ids=&service_type_id=&search=',
        );

        $this->assertResponseOk();
        $this->assertNotEmpty($this->viewVariable('customers'));
    }

    /**
     * What puts a debtor's contract beyond the automatic blocking is what is assigned to
     * it, not what its service type usually carries. Blocking writes the customer's
     * addresses into a firewall list, so a contract with one is reached and a contract
     * with only a technology address - our own equipment terminating the circuit - is not.
     * A contract marked VIP is passed over whatever it carries.
     *
     * @return void
     * @link \App\Dashboard\Card\ManualShutoffDebtorsCard::data()
     */
    public function testDebtsBeyondBlockingGoByTheAddressesAssigned(): void
    {
        // the tolerances are configurable and this installation allows a fortnight and a
        // small sum, so the debt is written here rather than leant on from the fixtures
        $invoices = $this->getTableLocator()->get('Bookkeeping.Invoices');
        $invoices->saveOrFail($invoices->newEntity([
            'customer_id' => self::CUSTOMER_ID,
            'number' => '9/TEST/2026',
            'creation_date' => Date::today()->subDays(400),
            'due_date' => Date::today()->subDays(365),
            'total' => 100000.0,
            'debt' => 100000.0,
            'accounting_identifier' => 'test-beyond-blocking',
        ]));

        $contracts = $this->getTableLocator()->get('Contracts');
        // the fixtures mark both VIP, which would answer the question before it is asked
        $contracts->updateAll(['vip' => false], []);

        // the fixtures give this one a customer address, and the other one none at all
        $reachable = '7f76dc3f-a11b-4109-958b-4b0382545a66';
        $beyond = '9c0d5e5c-2a6b-4f8e-9a3d-1b7c4e2f6a90';

        $this->login();
        $this->assertNotContains($reachable, $this->beyondBlocking(), 'has a customer address');
        $this->assertContains($beyond, $this->beyondBlocking(), 'has no address at all');

        // an address of our own equipment is not the customer's, so it changes nothing
        $addresses = $this->getTableLocator()->get('IpAddresses');
        $addresses->saveOrFail($addresses->newEntity([
            'ip_address' => '10.0.0.1',
            'type_of_use' => IpAddressTypeOfUse::TechnologyManually,
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => $beyond,
        ]));

        $this->assertContains($beyond, $this->beyondBlocking(), 'a technology address is ours, not theirs');

        // the nightly run passes VIP contracts over, addresses or not
        $contracts->updateAll(['vip' => true], ['id' => $reachable]);

        $this->assertContains($reachable, $this->beyondBlocking(), 'VIP is passed over');
    }

    /**
     * The contract ids the "beyond automatic blocking" card comes back with.
     *
     * @return list<string>
     */
    private function beyondBlocking(): array
    {
        $this->get('/dashboard/card/manual_shutoff_debtors');
        $this->assertResponseOk();

        $ids = [];
        /** @var \Dashboard\Card\DashboardCardInterface $card */
        $card = $this->viewVariable('card');
        /** @var iterable<\App\Model\Entity\Contract> $rows */
        $rows = $card->data()['contracts'];
        foreach ($rows as $row) {
            $ids[] = $row->id;
        }

        return $ids;
    }

    /**
     * A label earns its line by having found something. The checking labels sit at zero
     * most of the time, and a column of zeroes is where the one that is not zero would go
     * unread.
     *
     * @return void
     * @link \App\Dashboard\Card\LabelsCard::data()
     */
    public function testLabelsAreDrawnOnlyWhereTheyFoundSomething(): void
    {
        $labels = $this->getTableLocator()->get('Labels');

        // the fixture label is carried by a customer, so it has something to report
        $found = $labels->find()->firstOrFail();
        $found->set('show_on_dashboard', true);
        $labels->saveOrFail($found);

        // a second one nobody carries, which is what a checking label looks like at rest
        $quiet = $labels->saveOrFail($labels->newEntity([
            'name' => 'Nothing to report',
            'color' => '#ffffff',
            'dynamic' => false,
            'show_on_dashboard' => true,
        ]));

        $this->login();
        $this->get('/dashboard/card/labels');

        $this->assertResponseOk();

        /** @var \Dashboard\Card\DashboardCardInterface $card */
        $card = $this->viewVariable('card');
        $data = $card->data();
        $ids = array_map(fn($label): string => $label->id, $data['labels']);

        $this->assertContains($found->get('id'), $ids);
        $this->assertNotContains($quiet->get('id'), $ids, 'nothing found, so nothing to say');
        $this->assertTrue($data['configured'], 'labels are set for the card, they just found nothing');
    }

    /**
     * The second line of a task row is the summary without the subject, which the heading
     * above it already carries. Reading it needs the contract and an address per customer
     * while the rows are ordered by columns of the task, and left to the `subquery` strategy
     * PostgreSQL refuses that - so this asks with a task actually in hand, which is the only
     * way the eager loading runs at all.
     *
     * @return void
     * @link \App\Model\Entity\Task::getSummaryText()
     */
    public function testATaskRowCarriesItsSummary(): void
    {
        $states = $this->getTableLocator()->get('TaskStates');
        $open = $states->find()->where(['completed' => false])->first();
        if ($open === null) {
            $open = $states->saveOrFail($states->newEntity([
                'name' => 'Open',
                'color' => '#ffffff',
                'completed' => false,
                'priority' => 1,
            ]));
        }

        $tasks = $this->getTableLocator()->get('Tasks');
        $tasks->saveOrFail($tasks->newEntity([
            'task_type_id' => $this->firstId('TaskTypes'),
            'task_state_id' => $open->get('id'),
            'subject' => 'A subject of its own',
            'priority' => 0,
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => '7f76dc3f-a11b-4109-958b-4b0382545a66',
        ]));

        $this->login();
        $this->get('/dashboard/card/unassigned_tasks');

        $this->assertResponseOk();
        // the heading carries the subject, so the line below it must not repeat it
        $this->assertResponseContains('A subject of its own');
        $this->assertResponseContains('class="dashboard-hint"');
    }

    /**
     * A card nobody registered is not a page.
     *
     * @return void
     * @link \App\Controller\DashboardController::card()
     */
    public function testCardThatDoesNotExist(): void
    {
        $this->login();
        $this->get('/dashboard/card/not_a_card');

        $this->assertResponseCode(404);
    }

    /**
     * A card the signed-in role is not offered is not reachable by asking for it directly,
     * as the permissions only guard the action rather than the individual cards.
     *
     * @return void
     * @link \App\Controller\DashboardController::card()
     */
    public function testCardTheRoleIsNotOffered(): void
    {
        $this->login('customer-service-technician');
        $this->get('/dashboard/card/debtors');

        $this->assertResponseCode(404);
    }

    /**
     * The address checks are drawn for the roles that deal with customers end to end, and
     * for nobody else.
     *
     * @param string $role Role to sign in as.
     * @param bool $offered Whether that role is offered the card.
     * @return void
     * @link \App\Dashboard\Card\AddressProblemsCard::roles()
     */
    #[DataProvider('addressProblemRoles')]
    public function testAddressProblemsCardIsOfferedToTheCustomerRoles(string $role, bool $offered): void
    {
        $this->login($role);
        $this->get('/dashboard/card/address_problems');

        $this->assertResponseCode($offered ? 200 : 404);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function addressProblemRoles(): array
    {
        return [
            'bookkeeper' => ['bookkeeper', true],
            'sales manager' => ['sales-manager', true],
            'sales representative' => ['sales-representative', true],
            'admin' => ['admin', true],
            'network technician' => ['network-technician', false],
        ];
    }

    /**
     * The contract card is offered to the same roles as the address one beside it: what the
     * checks are about is the customer's file either way.
     *
     * @param string $role Role to sign in as.
     * @param bool $offered Whether that role is offered the card.
     * @return void
     * @link \App\Dashboard\Card\ContractProblemsCard::roles()
     */
    #[DataProvider('addressProblemRoles')]
    public function testContractProblemsCardIsOfferedToTheCustomerRoles(string $role, bool $offered): void
    {
        $this->login($role);
        $this->get('/dashboard/card/contract_problems');

        $this->assertResponseCode($offered ? 200 : 404);
    }

    /**
     * A count on the card links into the overview with that check alone switched on, and
     * every other check named as off - the same reading of the query string the address
     * card relies on.
     *
     * @return void
     * @link \App\Dashboard\Card\ContractProblemsCard::data()
     */
    public function testContractProblemsCardLinksIntoItsOwnOverview(): void
    {
        $this->login('bookkeeper');
        $this->get('/dashboard/card/contract_problems');

        $this->assertResponseOk();

        /** @var \Dashboard\Card\DashboardCardInterface $card */
        $card = $this->viewVariable('card');
        $data = $card->data();

        $this->assertSame('overviewOfContractProblems', $data['overview_url']['action']);

        foreach ($data['rows'] as $row) {
            $this->assertSame('overviewOfContractProblems', $row['url']['action']);
            $this->assertSame([1], array_values(array_unique(array_filter($row['url']['?']['checks']))));

            // the card counts only what is still ahead, so the overview it leads to has to
            // as well - the same finding under two different numbers reads as a fault
            $this->assertSame(1, $row['url']['?']['ignore_inactive']);
        }
    }

    /**
     * The customer card is offered to the same roles as the two beside it.
     *
     * @param string $role Role to sign in as.
     * @param bool $offered Whether that role is offered the card.
     * @return void
     * @link \App\Dashboard\Card\CustomerProblemsCard::roles()
     */
    #[DataProvider('addressProblemRoles')]
    public function testCustomerProblemsCardIsOfferedToTheCustomerRoles(string $role, bool $offered): void
    {
        $this->login($role);
        $this->get('/dashboard/card/customer_problems');

        $this->assertResponseCode($offered ? 200 : 404);
    }

    /**
     * @return void
     * @link \App\Dashboard\Card\CustomerProblemsCard::data()
     */
    public function testCustomerProblemsCardLinksIntoItsOwnOverview(): void
    {
        $this->login('bookkeeper');
        $this->get('/dashboard/card/customer_problems');

        $this->assertResponseOk();

        /** @var \Dashboard\Card\DashboardCardInterface $card */
        $card = $this->viewVariable('card');
        $data = $card->data();

        $this->assertSame('overviewOfCustomerProblems', $data['overview_url']['action']);

        foreach ($data['rows'] as $row) {
            $this->assertSame('overviewOfCustomerProblems', $row['url']['action']);
            $this->assertSame(1, $row['url']['?']['ignore_inactive']);
        }
    }

    /**
     * A count on the card links into the overview with that check alone switched on, and
     * every other check switched off by name. A check left out of the query string is read
     * as being at its default, so naming only the one would arrive with the rest beside it.
     *
     * @return void
     * @link \App\Dashboard\Card\AddressProblemsCard::data()
     */
    public function testAddressProblemsCardLinksToOneCheckAtATime(): void
    {
        $this->login('bookkeeper');
        $this->get('/dashboard/card/address_problems');

        $this->assertResponseOk();

        /** @var \Dashboard\Card\DashboardCardInterface $card */
        $card = $this->viewVariable('card');
        $data = $card->data();

        $this->assertNotEmpty($data['rows'], 'The fixtures hold a customer with no address at all.');

        foreach ($data['rows'] as $row) {
            $checks = $row['url']['?']['checks'];

            $this->assertSame([1], array_values(array_unique(array_filter($checks))));
            $this->assertGreaterThan(1, count($checks), 'The other checks have to be named as off.');

            // the card counts only what is running, so the overview it leads to has to as
            // well - the same finding under two different numbers reads as a fault
            $this->assertSame(1, $row['url']['?']['ignore_inactive']);
        }
    }

    /**
     * A check that found nothing does not get a line - a column of zeroes is where the one
     * that is not zero goes unread.
     *
     * @return void
     * @link \App\Dashboard\Card\AddressProblemsCard::data()
     */
    public function testAddressProblemsCardLeavesOutWhatFoundNothing(): void
    {
        $this->login('bookkeeper');
        $this->get('/dashboard/card/address_problems');

        $this->assertResponseOk();

        /** @var \Dashboard\Card\DashboardCardInterface $card */
        $card = $this->viewVariable('card');

        foreach ($card->data()['rows'] as $row) {
            $this->assertGreaterThan(0, $row['total']);
        }
    }
}
