<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\DashboardController;
use App\Model\Enum\IpAddressTypeOfUse;
use App\Test\Traits\ControllerTestTrait;
use Cake\I18n\Date;
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
        'plugin.Bookkeeping.Invoices',
    ];

    /**
     * The dashboard is the landing page, so the root has to render it rather than redirect.
     *
     * @return void
     * @link \App\Controller\DashboardController::index()
     */
    public function testRootRendersTheDashboard(): void
    {
        $this->login();
        $this->get('/');

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
        $this->get('/dashboard');

        $this->assertResponseOk();
        $this->assertResponseContains('class="dashboard index content"');
        $this->assertResponseContains('class="dashboard-cards"');
        $this->assertResponseContains('class="related"');
        $this->assertResponseContains('css/dashboard.css');
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
        $this->get('/dashboard');

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
        $this->get('/dashboard');

        $this->assertResponseOk();

        /** @var list<\App\Dashboard\Card\DashboardCardInterface> $cards */
        $cards = $this->viewVariable('cards');
        $ids = array_map(fn($card): string => $card->id(), $cards);

        $this->assertContains('my_tasks', $ids);
        $this->assertNotContains('debtors', $ids);
        $this->assertNotContains('unassigned_tasks', $ids);
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

        /** @var \App\Dashboard\Card\DashboardCardInterface $card */
        $card = $this->viewVariable('card');
        $query = $card->data()['url']['?'];

        $this->assertSame(1, $query['pressing']);
        $this->assertSame(0, $query['stale']);
        $this->assertSame(0, $query['show_completed']);
        // a filter the operator last used must not still be narrowing the listing
        $this->assertSame('', $query['dealer_id']);
        $this->assertSame('', $query['task_state_id']);
        $this->assertSame('', $query['search']);
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
        $this->get('/tasks?pressing=1&stale=0&show_completed=0&dealer_id=');

        $this->assertResponseOk();

        $this->get('/tasks?pressing=0&stale=1&show_completed=0&dealer_id=');

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

        /** @var \App\Dashboard\Card\DashboardCardInterface $card */
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

        /** @var \App\Dashboard\Card\DashboardCardInterface $card */
        $card = $this->viewVariable('card');
        $query = $card->data()['urls'][$label->get('id')]['?'];

        $this->assertSame(1, $query['advanced_search']);
        $this->assertSame([$label->get('id')], $query['label_ids']);
        // the fields being cleared go as empty strings, which the URL keeps
        $this->assertSame('', $query['not_label_ids']);
        $this->assertSame('', $query['contract_state_id']);

        $this->get('/dashboard/card/contract_states');

        $this->assertResponseOk();

        /** @var \App\Dashboard\Card\DashboardCardInterface $card */
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
            /** @var \App\Dashboard\Card\DashboardCardInterface $offered */
            $offered = $this->viewVariable('card');
            $this->assertNotSame([], $offered->data()['urls'], $alias . ' for the role it names');

            $this->login('sales-manager');
            $this->get('/dashboard/card/' . $card);
            $this->assertResponseOk();
            /** @var \App\Dashboard\Card\DashboardCardInterface $withheld */
            $withheld = $this->viewVariable('card');
            $this->assertSame([], $withheld->data()['urls'], $alias . ' for a role it does not name');

            // naming no role at all puts it back in front of everybody
            $record->set('dashboard_roles', []);
            $table->saveOrFail($record);

            $this->get('/dashboard/card/' . $card);
            $this->assertResponseOk();
            /** @var \App\Dashboard\Card\DashboardCardInterface $forAll */
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
        /** @var \App\Dashboard\Card\DashboardCardInterface $card */
        $card = $this->viewVariable('card');
        /** @var iterable<\App\Model\Entity\Contract> $rows */
        $rows = $card->data()['contracts'];
        foreach ($rows as $row) {
            $ids[] = $row->id;
        }

        return $ids;
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
}
