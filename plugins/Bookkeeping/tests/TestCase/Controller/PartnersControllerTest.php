<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
use Bookkeeping\Controller\PartnersController;
use Bookkeeping\Provider\Pohoda\PohodaProvider;
use Cake\Cache\Cache;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use Settings\Utility\Settings;

/**
 * Bookkeeping\Controller\PartnersController Test Case
 *
 * The button on the customer's card writes their partner card at once. What mServer answers is made
 * up here; a customer whose card is not ours is turned away before anything is asked, and nothing
 * answers for any other address.
 */
#[UsesClass(PartnersController::class)]
class PartnersControllerTest extends TestCase
{
    use ControllerTestTrait;
    use HttpClientTrait;
    use IntegrationTestTrait;

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    private const URL = 'http://mserver.invalid';

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
        'plugin.Settings.Settings',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear('default');
        Settings::set('bookkeeping.accounting.default_provider', 'pohoda');
        Settings::set(PohodaProvider::SETTINGS_ROOT . '.api.url', self::URL);
        Settings::set(PohodaProvider::SETTINGS_ROOT . '.customers.min_customer_number', 0);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Cache::clear('default');

        parent::tearDown();
    }

    /**
     * mServer takes the customer, or refuses them with the given note.
     *
     * @param string|null $refusal What it says when it refuses them, null where it takes them.
     * @return void
     */
    private function mServerAnswers(?string $refusal = null): void
    {
        $state = $refusal === null ? 'ok' : 'error';

        $this->mockClientPost(
            self::URL . '/xml',
            $this->newClientResponse(
                200,
                ['Content-Type: application/xml'],
                '<?xml version="1.0" encoding="UTF-8"?>'
                . '<rsp:responsePack version="2.0" id="adb" state="ok"'
                . ' xmlns:rsp="http://www.stormware.cz/schema/version_2/response.xsd"'
                . ' xmlns:adb="http://www.stormware.cz/schema/version_2/addressbook.xsd">'
                . '<rsp:responsePackItem version="2.0" id="1" state="' . $state . '"'
                . ($refusal === null ? '' : ' note="' . $refusal . '"') . '>'
                . '<adb:addressbookResponse version="2.0" state="' . $state . '"/>'
                . '</rsp:responsePackItem>'
                . '</rsp:responsePack>',
            ),
        );
    }

    /**
     * Press the button as the given role.
     *
     * @param string $role Role to sign in as.
     * @return void
     */
    private function send(string $role = 'admin'): void
    {
        $this->login($role);
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/bookkeeping/partners/send/' . self::CUSTOMER_ID);
    }

    /**
     * What the card was told afterwards. The request has closed the session by now, so it is read
     * as the flash assertions read it.
     *
     * @return string
     */
    private function flashMessage(): string
    {
        return (string)Hash::get($_SESSION, 'Flash.flash.0.message');
    }

    /**
     * The customer is written and the card says so.
     *
     * @return void
     * @link \Bookkeeping\Controller\PartnersController::send()
     */
    public function testTheCustomerIsSentAtOnce(): void
    {
        $this->mServerAnswers();

        $this->send();

        $this->assertRedirectContains('/customers/' . self::CUSTOMER_ID);
        $this->assertFlashMessage('The customer has been sent to the accounting system.');
    }

    /**
     * What the accounting system refused the customer for is shown on the card, which is where
     * somebody can put it right.
     *
     * @return void
     * @link \Bookkeeping\Controller\PartnersController::send()
     */
    public function testARefusalIsShownWithItsReason(): void
    {
        $this->mServerAnswers('Duplicate identity number');

        $this->send();

        $this->assertRedirectContains('/customers/' . self::CUSTOMER_ID);
        $this->assertStringStartsWith('The customer could not be sent to the accounting system:', $this->flashMessage());
        $this->assertStringContainsString('Duplicate identity number', $this->flashMessage());
    }

    /**
     * A customer whose card is not ours is turned away rather than sent - asked about, they would
     * fail on the address nothing answers for.
     *
     * @return void
     * @link \Bookkeeping\Controller\PartnersController::send()
     */
    public function testACustomerNotSynchronizedIsNotSent(): void
    {
        $customers = $this->getTableLocator()->get('Customers');
        $customers->updateAll(['sync_to_accounting' => false], ['id' => self::CUSTOMER_ID]);

        $this->send();

        $this->assertRedirectContains('/customers/' . self::CUSTOMER_ID);
        $this->assertFlashMessage('This customer is not sent to the accounting system.');
    }

    /**
     * Below the number the partner cards are ours from, the button would set a second card beside
     * the one made by hand.
     *
     * @return void
     * @link \Bookkeeping\Controller\PartnersController::send()
     */
    public function testACustomerBelowTheFirstNumberIsNotSent(): void
    {
        Settings::set(PohodaProvider::SETTINGS_ROOT . '.customers.min_customer_number', 999999999);

        $this->send();

        $this->assertFlashMessage('This customer is not sent to the accounting system.');
    }

    /**
     * Only the button sends, never a link followed.
     *
     * @return void
     * @link \Bookkeeping\Controller\PartnersController::send()
     */
    public function testOnlyAPostSends(): void
    {
        $this->login();
        $this->get('/bookkeeping/partners/send/' . self::CUSTOMER_ID);

        $this->assertResponseCode(405);
    }

    /**
     * Whoever may edit the customer may send them: the card holds nothing they could not change,
     * and the scheduled run would send it anyway.
     *
     * @return array<string, array{string}>
     */
    public static function customerEditors(): array
    {
        return [
            'admin' => ['admin'],
            'manager' => ['manager'],
            'network manager' => ['network-manager'],
            'sales representative' => ['sales-representative'],
            'sales manager' => ['sales-manager'],
            'bookkeeper' => ['bookkeeper'],
        ];
    }

    /**
     * @param string $role Role to sign in as.
     * @return void
     * @link \Bookkeeping\Controller\PartnersController::send()
     */
    #[DataProvider('customerEditors')]
    public function testTheCustomerEditorsMaySend(string $role): void
    {
        $this->mServerAnswers();

        $this->send($role);

        $this->assertFlashMessage('The customer has been sent to the accounting system.');
    }

    /**
     * The others only look at the customer.
     *
     * @return array<string, array{string}>
     */
    public static function theOthers(): array
    {
        return [
            'user' => ['user'],
            'customer service technician' => ['customer-service-technician'],
            'network technician' => ['network-technician'],
        ];
    }

    /**
     * @param string $role Role to sign in as.
     * @return void
     * @link \Bookkeeping\Controller\PartnersController::send()
     */
    #[DataProvider('theOthers')]
    public function testTheOthersMayNot(string $role): void
    {
        $this->send($role);

        $this->assertRedirect('/');
        $this->assertNotSame('The customer has been sent to the accounting system.', $this->flashMessage());
    }
}
