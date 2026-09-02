<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\HomeController;
use App\Model\Enum\LandingPage;
use App\Test\Traits\ControllerTestTrait;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\HomeController Test Case
 *
 * The root is the one address everybody arrives at - by signing in, and by the title in the
 * corner of every page - so what it answers with decides where the day starts.
 */
#[UsesClass(HomeController::class)]
class HomeControllerTest extends TestCase
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
        'app.TaskStates',
        'app.TaskTypes',
    ];

    /**
     * Somebody who has chosen nothing is sent to the dashboard, which is what the application
     * opened on before the choice existed.
     *
     * @return void
     * @link \App\Controller\HomeController::index()
     */
    public function testTheDashboardIsWhereTheUndecidedLand(): void
    {
        $this->login();
        $this->get('/');

        $this->assertRedirect('/dashboard');
    }

    /**
     * The page chosen is the page landed on.
     *
     * @return void
     * @link \App\Controller\HomeController::index()
     */
    public function testTheChosenPageIsWhereTheUserLands(): void
    {
        $this->login('admin', ['landing_page' => 'customers']);
        $this->get('/');

        $this->assertRedirect('/customers');
    }

    /**
     * Every page on offer has to be one the router can actually build - a plugin's as much as
     * the application's own - and it has to lead to the page it is named after rather than to
     * whatever else that address happens to answer.
     *
     * @param \App\Model\Enum\LandingPage $page The page on offer.
     * @return void
     * @link \App\Model\Enum\LandingPage::url()
     */
    #[DataProvider('pageProvider')]
    public function testEveryPageOnOfferIsLandedOn(LandingPage $page): void
    {
        // the admin role is allowed everywhere, so what is being asked here is the address
        // rather than the permissions
        $this->login('admin', ['landing_page' => $page->value]);
        $this->get('/');

        $this->assertRedirect();

        $location = $this->_response?->getHeaderLine('Location') ?? '';
        $params = Router::parseRequest(new ServerRequest(['uri' => new Uri($location)]));

        $this->assertSame($page->url()['plugin'], $params['plugin']);
        $this->assertSame($page->url()['controller'], $params['controller']);
        $this->assertSame($page->url()['action'], $params['action']);
    }

    /**
     * @return array<string, array{\App\Model\Enum\LandingPage}>
     */
    public static function pageProvider(): array
    {
        $cases = [];
        foreach (LandingPage::cases() as $case) {
            $cases[$case->value] = [$case];
        }

        return $cases;
    }

    /**
     * A setting left over from a page that is no longer offered is not a page, so it is passed
     * over rather than turned into a redirect to nowhere.
     *
     * @return void
     * @link \App\Controller\HomeController::index()
     */
    public function testASettingThatNamesNoPageIsPassedOver(): void
    {
        $this->login('admin', ['landing_page' => 'somewhere-we-no-longer-go']);
        $this->get('/');

        $this->assertRedirect('/dashboard');
    }

    /**
     * A choice made before a role was narrowed would send the user into a page the permissions
     * then refuse, and being turned away at the door of the application reads as being unable
     * to sign in at all. So they get the dashboard, and are told why.
     *
     * @return void
     * @link \App\Controller\HomeController::index()
     */
    public function testAPageTheRoleMayNotOpenSendsThemToTheDashboardWithAWord(): void
    {
        // the plain user role is only let as far as the dashboard, the customer listing and
        // their own settings
        $this->login('user', ['landing_page' => 'tasks']);
        $this->get('/');

        $this->assertRedirect('/dashboard');
        $this->assertFlashElement('flash/error');
    }

    /**
     * The choice is made in the settings, and offering a page there that the role would then
     * be turned away from is offering somewhere to be stuck.
     *
     * @return void
     * @link \App\Controller\Traits\UserSettingsTrait::userSettings()
     */
    public function testTheSettingsOfferOnlyThePagesTheRoleMayOpen(): void
    {
        // the settings belong to a user, so the one signed in has to be one the table holds -
        // and a superuser is allowed everywhere, which would answer the question before it is
        // asked
        $users = $this->getTableLocator()->get('AppUsers');
        $user = $users->find()->firstOrFail();
        $user->set('role', 'user');
        $user->set('is_superuser', false);
        $this->session(['Auth' => $user]);

        $this->get('/app-users/user-settings');

        $this->assertResponseOk();

        $offered = array_keys((array)$this->viewVariable('landingPages'));

        $this->assertContains(LandingPage::Dashboard->value, $offered);
        $this->assertContains(LandingPage::Customers->value, $offered);
        // the plain user role is not let into the tasks or the invoices
        $this->assertNotContains(LandingPage::Tasks->value, $offered);
        $this->assertNotContains(LandingPage::Bookkeeping->value, $offered);
    }
}
