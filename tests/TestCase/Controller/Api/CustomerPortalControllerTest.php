<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use App\Controller\Api\CustomerPortalController;
use App\CustomerPortal\Passwords;
use App\CustomerPortal\Tokens;
use App\Model\Enum\IpAddressTypeOfUse;
use App\Model\Enum\LoginRights;
use App\Test\Traits\ControllerTestTrait;
use Cake\Cache\Cache;
use Cake\Chronos\Chronos;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Settings\Utility\Settings;

/**
 * App\Controller\Api\CustomerPortalController Test Case
 *
 * The portal reaches this as a user of its own, with a Bearer token, and every answer about a
 * customer is limited to the one a customer token names. What is checked is the answer the portal
 * reads, and that nothing of another customer gets into it.
 */
#[UsesClass(CustomerPortalController::class)]
class CustomerPortalControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    private const PORTAL_KEY = 'customer-portal-test-key';

    private const LOGIN_ID = '5457ac8a-1c92-4d5f-a035-41de7fad3509';

    private const LOGIN = 'Lorem ipsum dolor sit amet';

    private const PASSWORD = 'correct horse battery staple';

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    private const OTHER_CUSTOMER_ID = 'ae128a49-82fd-4b80-921f-f11af75fd113';

    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    private const INVOICE_ID = 'dbcc7efa-8ae3-4bf9-afc5-273bd3a843f7';

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
        'app.Logins',
        'app.Labels',
        'app.CustomerLabels',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.ConnectionProfiles',
        'app.Services',
        'app.Contracts',
        'app.Billings',
        'app.IpAddresses',
        'app.IpNetworks',
        'plugin.Bookkeeping.Invoices',
        'plugin.Settings.Settings',
    ];

    /**
     * The portal signs in as a user of its own, and the fixture login gets a password it can
     * be signed in with.
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        // the failed attempts of one test would otherwise block the next
        Cache::clear('default');

        $this->getTableLocator()->get('Users')->updateAll(
            ['role' => 'customer-portal', 'is_superuser' => false, 'active' => true, 'api_token' => hash('sha256', self::PORTAL_KEY)],
            ['id' => $this->firstId('Users')],
        );
        $this->storePassword(Passwords::hash(self::PASSWORD));
        $this->getTableLocator()->get('Logins')->updateAll(['locked' => false], ['id' => self::LOGIN_ID]);
    }

    /**
     * A customer signs in with their password and gets a token naming them, without the portal
     * sending any CSRF token.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::login()
     */
    public function testACustomerSignsInWithTheirPassword(): void
    {
        $answer = $this->signIn(self::PASSWORD);

        $this->assertSame('granted', $answer['status']);
        $this->assertSame(self::CUSTOMER_ID, $answer['customer']['id']);
        $this->assertSame(self::LOGIN_ID, $answer['login']['id']);
        $this->assertSame(LoginRights::User->value, $answer['login']['rights']);
        $this->assertSame(8, $answer['password_min_length']);
        $this->assertSame(
            ['login_id' => self::LOGIN_ID, 'customer_id' => self::CUSTOMER_ID, 'expires' => $answer['expires']],
            Tokens::read($answer['token']),
        );
        $this->assertSame('192.0.2.10', $this->storedLogin()->get('last_granted_ip'));
    }

    /**
     * A password stored as a bare SHA-1 signs in, and is stored as Argon2id from then on.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::login()
     */
    public function testABareSha1PasswordSignsInAndIsHashedAfresh(): void
    {
        $this->storePassword(sha1(self::PASSWORD));

        $this->assertSame('granted', $this->signIn(self::PASSWORD)['status']);

        $stored = (string)$this->storedLogin()->get('password');
        $this->assertStringStartsWith('$argon2id$', $stored);
        $this->assertFalse(Passwords::needsRehash($stored));
        $this->assertTrue(Passwords::verify(self::PASSWORD, $stored));
    }

    /**
     * A wrapped SHA-1 signs in with the password, but not with the SHA-1 itself.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::login()
     */
    public function testAWrappedPasswordSignsInButItsSha1DoesNot(): void
    {
        $this->storePassword(Passwords::wrap(sha1(self::PASSWORD)));

        $this->assertSame('denied', $this->signIn(sha1(self::PASSWORD))['status']);
        $this->assertSame('granted', $this->signIn(self::PASSWORD)['status']);
        $this->assertStringStartsWith('$argon2id$', (string)$this->storedLogin()->get('password'));
    }

    /**
     * A wrong password is denied and the attempt recorded, an unknown login is denied the same way.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::login()
     */
    public function testAWrongPasswordIsDenied(): void
    {
        $answer = $this->signIn('wrong horse');

        $this->assertSame(['status' => 'denied'], $answer);
        $this->assertSame('192.0.2.10', $this->storedLogin()->get('last_denied_ip'));

        $this->assertSame(['status' => 'denied'], $this->signIn(self::PASSWORD, 'nobody'));
    }

    /**
     * A locked login is told so, and given no token.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::login()
     */
    public function testALockedLoginGetsNoToken(): void
    {
        $this->getTableLocator()->get('Logins')->updateAll(['locked' => true], ['id' => self::LOGIN_ID]);

        $this->assertSame(['status' => 'locked'], $this->signIn(self::PASSWORD));
    }

    /**
     * After too many failures the login is refused from that address even with the right
     * password, but not from another one.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::login()
     */
    public function testRepeatedFailuresBlockTheLoginFromThatAddress(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->signIn('wrong horse');
        }

        $this->assertSame(['status' => 'blocked'], $this->signIn(self::PASSWORD));
        $this->assertSame('granted', $this->signIn(self::PASSWORD, self::LOGIN, '192.0.2.99')['status']);
    }

    /**
     * Signing in without the customer's address is a malformed request rather than a failure.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::login()
     */
    public function testSigningInWantsTheCustomersAddress(): void
    {
        $this->asPortal();
        $this->post('/api/customer-portal/login', ['login' => self::LOGIN, 'password' => self::PASSWORD]);

        $this->assertResponseCode(400);
    }

    /**
     * Without a customer token, or with one that does not hold, nothing about a customer is
     * answered.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::customer()
     */
    public function testWithoutACustomerTokenNothingIsAnswered(): void
    {
        $this->asPortal();
        $this->get('/api/customer-portal/customer');
        $this->assertResponseCode(401);

        $this->asPortal('not-a-token');
        $this->get('/api/customer-portal/customer');
        $this->assertResponseCode(401);

        $token = Tokens::issue(self::LOGIN_ID, self::OTHER_CUSTOMER_ID, 8)['token'];
        $this->asPortal($token);
        $this->get('/api/customer-portal/customer');
        $this->assertResponseCode(401);
    }

    /**
     * A login locked after its token was issued stops being answered.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::customer()
     */
    public function testALoginLockedAfterSigningInIsNoLongerAnswered(): void
    {
        $token = $this->signIn(self::PASSWORD)['token'];
        $this->getTableLocator()->get('Logins')->updateAll(['locked' => true], ['id' => self::LOGIN_ID]);

        $this->asPortal($token);
        $this->get('/api/customer-portal/customer');

        $this->assertResponseCode(401);
    }

    /**
     * The signed-in customer is answered with their details and contacts.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::customer()
     */
    public function testTheCustomerIsAnsweredWithTheirDetails(): void
    {
        $this->asPortal($this->signIn(self::PASSWORD)['token']);
        $this->get('/api/customer-portal/customer');

        $this->assertResponseOk();
        $answer = $this->answer();
        $this->assertSame(self::CUSTOMER_ID, $answer['customer']['id']);
        $this->assertSame('NETAIR, s.r.o.', $answer['customer']['company']);
        $this->assertSame(['customer@example.com'], array_column($answer['customer']['emails'], 'email'));
        $this->assertSame(['+420 601 234 567'], array_column($answer['customer']['phones'], 'phone'));
        $this->assertIsBool($answer['customer']['phones'][0]['use_for_outages']);
        $this->assertSame(['Jablonec nad Jizerou'], array_column($answer['customer']['addresses'], 'city'));
        $this->assertArrayNotHasKey('ip_addresses', $answer['customer']);
        $this->assertArrayHasKey('b490f1c9-ff7e-430a-bfb0-f400878e1617', $answer['countries']);
        $this->assertArrayNotHasKey('password', $answer['customer']);
    }

    /**
     * The contracts with active services come with where they are installed, the services billed
     * today and the customer's own ip addresses and networks, not those of the technology.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::customer()
     */
    public function testTheCustomersContractsAreAnsweredWithTheirServicesAndAddresses(): void
    {
        $contracts = $this->getTableLocator()->get('Contracts');
        $states = $this->getTableLocator()->get('ContractStates');
        $ended = $states->saveOrFail($states->newEntity([
            'name' => 'Ended',
            'color' => '#000000',
            'active_services' => false,
            'billed' => false,
            'blocked' => false,
        ], ['accessibleFields' => ['*' => true]]));
        $contracts->updateAll(['contract_state_id' => $ended->id], ['number' => 'The other contract']);
        $ipAddresses = $this->getTableLocator()->get('IpAddresses');
        $ipAddresses->saveOrFail($ipAddresses->newEntity([
            'ip_address' => '192.168.11.1',
            'type_of_use' => IpAddressTypeOfUse::TechnologyManually,
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => self::CONTRACT_ID,
        ], ['accessibleFields' => ['*' => true]]));

        $this->asPortal($this->signIn(self::PASSWORD)['token']);
        $this->get('/api/customer-portal/customer');

        $this->assertResponseOk();
        $contractsAnswered = $this->answer()['customer']['contracts'];
        $this->assertSame(['Lorem ipsum dolor sit amet'], array_column($contractsAnswered, 'number'));
        $contract = $contractsAnswered[0];
        $this->assertSame('Lorem ipsum dolor sit amet', $contract['service_type']);
        $this->assertSame('Lorem ipsum dolor sit amet', $contract['subscriber_verification_code']);
        $this->assertSame('Jablonec nad Jizerou', $contract['installation_address']['city']);
        $this->assertSame(['Sed do eiusmod tempor'], $contract['services']);
        $this->assertSame(['192.168.11.11'], $contract['ip_addresses']);
        $this->assertSame(['10.0.0.0/8'], $contract['ip_networks']);
    }

    /**
     * Consents are saved, and a given GDPR consent labels the customer once.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::consents()
     */
    public function testConsentsAreSavedAndTheConsentLabelled(): void
    {
        $label = $this->getTableLocator()->get('Labels')->get('e9cbb697-be8b-4e05-8226-1b0aeb53130d');
        $this->getTableLocator()->get('Labels')->updateAll(['name' => 'GDPR'], ['id' => $label->get('id')]);
        $customerLabels = $this->getTableLocator()->get('CustomerLabels');
        $customerLabels->deleteAll(['customer_id' => self::CUSTOMER_ID]);
        $token = $this->signIn(self::PASSWORD)['token'];

        foreach ([1, 2] as $time) {
            $this->asPortal($token);
            $this->post('/api/customer-portal/consents', [
                'agree_gdpr' => '1',
                'agree_mailing_commercial' => '0',
            ]);
            $this->assertResponseOk();
            $this->assertSame(['status' => 'saved'], $this->answer());
        }

        $customer = $this->getTableLocator()->get('Customers')->get(self::CUSTOMER_ID);
        $this->assertTrue($customer->get('agree_gdpr'));
        $this->assertFalse($customer->get('agree_mailing_commercial'));
        $labelled = $customerLabels->find()->where(['customer_id' => self::CUSTOMER_ID])->all();
        $this->assertCount(1, $labelled);
        $this->assertSame(['form'], $labelled->extract('note')->toList());
    }

    /**
     * The signed-in login gets a new password, stored as Argon2id.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::password()
     */
    public function testThePasswordIsChanged(): void
    {
        $this->asPortal($this->signIn(self::PASSWORD)['token']);
        $this->post('/api/customer-portal/password', ['password' => 'another horse']);

        $this->assertResponseOk();
        $stored = (string)$this->storedLogin()->get('password');
        $this->assertStringStartsWith('$argon2id$', $stored);
        $this->assertTrue(Passwords::verify('another horse', $stored));
    }

    /**
     * A password shorter than the settings allow is refused, saying how long it has to be.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::password()
     */
    public function testAShortPasswordIsRefused(): void
    {
        $stored = $this->storedLogin()->get('password');
        Settings::set('core.customer_portal.password_min_length', 10);

        $this->asPortal($this->signIn(self::PASSWORD)['token']);
        $this->post('/api/customer-portal/password', ['password' => 'too short']);

        $this->assertResponseCode(422);
        $this->assertSame(['status' => 'too_short', 'min_length' => 10], $this->answer());
        $this->assertSame($stored, $this->storedLogin()->get('password'));
    }

    /**
     * Only the signed-in customer's invoices are listed.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::invoices()
     */
    public function testOnlyTheCustomersInvoicesAreListed(): void
    {
        $invoices = $this->getTableLocator()->get('Bookkeeping.Invoices');
        $invoices->saveOrFail($invoices->newEntity([
            'customer_id' => self::OTHER_CUSTOMER_ID,
            'number' => 'FOREIGN/1',
            'variable_symbol' => '654321',
            'creation_date' => '2022-01-01',
            'due_date' => '2022-01-15',
            'text' => 'Another customer',
            'total' => '10',
            'debt' => '0',
        ]));

        $this->asPortal($this->signIn(self::PASSWORD)['token']);
        $this->get('/api/customer-portal/invoices');

        $this->assertResponseOk();
        $answer = $this->answer()['invoices'];
        $this->assertSame([self::INVOICE_ID], array_column($answer, 'id'));
        $this->assertSame('2021-12-09', $answer[0]['creation_date']);
    }

    /**
     * The customer's own invoice is downloaded, another customer's is not found.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::invoiceDownload()
     */
    public function testOnlyTheCustomersInvoiceIsDownloaded(): void
    {
        $dataRoot = TMP . 'customer_portal_test';
        Configure::write('Data.root', $dataRoot);
        $this->getTableLocator()->get('Bookkeeping.Invoices')
            ->updateAll(['number' => 'T0001'], ['id' => self::INVOICE_ID]);
        $file = $dataRoot . DS . 'invoices' . DS . 'Faktura_T0001.pdf';
        mkdir(dirname($file), 0777, true);
        file_put_contents($file, '%PDF-1.4 test');
        $token = $this->signIn(self::PASSWORD)['token'];

        try {
            $this->asPortal($token);
            $this->get('/api/customer-portal/invoices/' . self::INVOICE_ID . '/download');
            $this->assertResponseOk();
            $this->assertSame('%PDF-1.4 test', $this->_getBodyAsString());

            $this->getTableLocator()->get('Bookkeeping.Invoices')
                ->updateAll(['customer_id' => self::OTHER_CUSTOMER_ID], ['id' => self::INVOICE_ID]);
            $this->asPortal($token);
            $this->get('/api/customer-portal/invoices/' . self::INVOICE_ID . '/download');
            $this->assertResponseCode(404);
        } finally {
            unlink($file);
            rmdir(dirname($file));
            rmdir($dataRoot);
        }
    }

    /**
     * An address is answered with who uses it, an unused one with nobody.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::identify()
     */
    public function testAnAddressIsAnsweredWithWhoUsesIt(): void
    {
        $this->asService();
        $this->get('/api/customer-portal/identify?ip=192.168.11.11');
        $this->assertResponseOk();
        $customer = $this->answer()['customer'];
        $this->assertSame(
            ['number', 'title', 'first_name', 'last_name', 'suffix', 'company', 'email', 'address'],
            array_keys($customer),
        );
        $this->assertSame('NETAIR, s.r.o.', $customer['company']);
        $this->assertArrayHasKey('last_name', $customer);
        $this->assertSame('customer@example.com', $customer['email']);
        $this->assertSame('Jablonec nad Jizerou', $customer['address']['city']);

        $this->asService();
        $this->get('/api/customer-portal/identify?ip=192.0.2.200');
        $this->assertResponseOk();
        $this->assertSame(['customer' => null], $this->answer());

        $this->asService();
        $this->get('/api/customer-portal/identify?ip=nowhere');
        $this->assertResponseCode(400);
    }

    /**
     * A service asking about more addresses in a minute than the settings allow is refused.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::identify()
     */
    public function testAServiceGoingThroughAddressesIsRefused(): void
    {
        Settings::set('core.customer_portal.identify_per_minute', 2);
        Chronos::setTestNow('2026-10-12 10:00:15');

        try {
            foreach (['192.0.2.1', '192.0.2.2'] as $ip) {
                $this->asService();
                $this->get('/api/customer-portal/identify?ip=' . $ip);
                $this->assertResponseOk();
            }
            $this->asService();
            $this->get('/api/customer-portal/identify?ip=192.168.11.11');
            $this->assertResponseCode(429);
            $this->assertResponseNotContains('NETAIR');
        } finally {
            Chronos::setTestNow(null);
        }
    }

    /**
     * The customer portal signs customers in but does not look up who uses an address.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::identify()
     */
    public function testThePortalDoesNotLookUpAddresses(): void
    {
        $this->asPortal();
        $this->get('/api/customer-portal/identify?ip=192.168.11.11');

        $this->assertResponseCode(403);
        $this->assertResponseNotContains('NETAIR');
    }

    /**
     * A user of another role is refused, even with a valid key.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::login()
     */
    public function testOtherRolesAreRefused(): void
    {
        $this->getTableLocator()->get('Users')->updateAll(['role' => 'api'], ['id' => $this->firstId('Users')]);

        $this->asPortal();
        $this->get('/api/customer-portal/identify?ip=192.168.11.11');

        $this->assertResponseCode(403);
        $this->assertResponseNotContains('192.168.11.11');
    }

    /**
     * A request signed in by the session still has to carry a CSRF token: only a Bearer client
     * is let past that check.
     *
     * @return void
     * @link \Access\Authentication\BearerRequest::isApi()
     */
    public function testWithoutBearerTheCsrfTokenIsStillRequired(): void
    {
        $this->login('customer-portal');
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->post('/api/customer-portal/login', [
            'login' => self::LOGIN,
            'password' => self::PASSWORD,
            'ip' => '192.0.2.10',
        ]);

        $this->assertResponseCode(403);
        $this->assertNotSame('192.0.2.10', $this->storedLogin()->get('last_granted_ip'));
    }

    /**
     * A customer who forgot their password asks by their number and an e-mail of theirs, in any
     * case, sets a new one with the link and signs in with it. The link is spent by it.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::accessRequest()
     * @link \App\Controller\Api\CustomerPortalController::access()
     * @link \App\Controller\Api\CustomerPortalController::setAccess()
     */
    public function testAnAccessLinkSetsAForgottenPassword(): void
    {
        $answer = $this->requestAccess($this->customerNumber(self::CUSTOMER_ID), 'Customer@Example.com');
        $this->assertSame('found', $answer['status']);
        $this->assertSame(self::CUSTOMER_ID, $answer['customer']['id']);
        $token = (string)$answer['token'];

        $this->asPortal();
        $this->get('/api/customer-portal/access?token=' . urlencode($token));
        $answer = $this->answer();
        $this->assertSame('valid', $answer['status']);
        $this->assertSame('customer@example.com', $answer['email']);
        $this->assertSame([['id' => self::LOGIN_ID, 'login' => self::LOGIN]], $answer['logins']);
        $this->assertNull($answer['new_login']);

        $this->asPortal();
        $this->post('/api/customer-portal/access', ['token' => $token, 'password' => 'short', 'ip' => '192.0.2.10']);
        $this->assertResponseCode(422);

        $this->asPortal();
        $this->post('/api/customer-portal/access', [
            'token' => $token,
            'password' => 'a new password of mine',
            'ip' => '192.0.2.10',
        ]);
        $this->assertResponseOk();
        $this->assertSame(['status' => 'saved', 'login' => self::LOGIN], $this->answer());
        $this->assertSame('granted', $this->signIn('a new password of mine')['status']);

        $this->asPortal();
        $this->get('/api/customer-portal/access?token=' . urlencode($token));
        $this->assertSame('invalid', $this->answer()['status']);
    }

    /**
     * A customer with no login gets one with the link, named after them.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::setAccess()
     */
    public function testAnAccessLinkMakesALoginWhereThereIsNone(): void
    {
        $this->getTableLocator()->get('Customers')->updateAll(
            ['last_name' => 'Smith', 'first_name' => 'Jane'],
            ['id' => self::OTHER_CUSTOMER_ID],
        );
        $emails = $this->getTableLocator()->get('Emails');
        $emails->saveOrFail($emails->newEntity(
            ['customer_id' => self::OTHER_CUSTOMER_ID, 'email' => 'jane@example.com'],
            ['accessibleFields' => ['customer_id' => true]],
        ));

        $token = (string)$this->requestAccess($this->customerNumber(self::OTHER_CUSTOMER_ID), 'jane@example.com')['token'];
        $this->asPortal();
        $this->get('/api/customer-portal/access?token=' . urlencode($token));
        $answer = $this->answer();
        $this->assertSame([], $answer['logins']);
        $this->assertSame('smith.jane', $answer['new_login']);

        $this->asPortal();
        $this->post('/api/customer-portal/access', ['token' => $token, 'password' => 'jane at home 2026', 'ip' => '192.0.2.10']);
        $this->assertSame(['status' => 'saved', 'login' => 'smith.jane'], $this->answer());
        $this->assertSame('granted', $this->signIn('jane at home 2026', 'smith.jane')['status']);
    }

    /**
     * A number and an e-mail that do not belong together get no link, whichever of them is
     * someone's.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::accessRequest()
     */
    public function testANumberAndAnEmailThatDoNotBelongTogetherGetNoLink(): void
    {
        $number = $this->customerNumber(self::CUSTOMER_ID);

        $this->assertSame(['status' => 'unknown'], $this->requestAccess($number, 'someone@example.com'));
        $this->assertSame(
            ['status' => 'unknown'],
            $this->requestAccess($this->customerNumber(self::OTHER_CUSTOMER_ID), 'customer@example.com'),
        );
        $this->assertSame(['status' => 'unknown'], $this->requestAccess('NETAIR', 'customer@example.com'));
    }

    /**
     * Where every login of the customer is locked, the portal is told so rather than given a
     * link, to tell the customer in the e-mail.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::accessRequest()
     */
    public function testEveryLoginLockedGetsNoLink(): void
    {
        $this->getTableLocator()->get('Logins')->updateAll(['locked' => true], ['customer_id' => self::CUSTOMER_ID]);

        $answer = $this->requestAccess($this->customerNumber(self::CUSTOMER_ID), 'customer@example.com');

        $this->assertSame('locked', $answer['status']);
        $this->assertArrayNotHasKey('token', $answer);
    }

    /**
     * Requests for one customer number are limited, as each of them sends an e-mail.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::accessRequest()
     */
    public function testAccessRequestsAreLimited(): void
    {
        Settings::set('core.customer_portal.access_requests_per_customer', 2);
        $number = $this->customerNumber(self::CUSTOMER_ID);

        $this->assertSame('found', $this->requestAccess($number, 'customer@example.com')['status']);
        $this->assertSame('unknown', $this->requestAccess($number, 'someone@example.com')['status']);
        $this->assertSame('blocked', $this->requestAccess($number, 'customer@example.com', '198.51.100.7')['status']);
    }

    /**
     * A link sets the password only of a login of its customer that is not locked.
     *
     * @return void
     * @link \App\Controller\Api\CustomerPortalController::setAccess()
     */
    public function testAnAccessLinkSetsOnlyAnOpenLoginOfItsCustomer(): void
    {
        $logins = $this->getTableLocator()->get('Logins');
        $other = $logins->newEntity(
            ['login' => 'other.login', 'customer_id' => self::OTHER_CUSTOMER_ID, 'password' => 'whatever it is'],
            ['accessibleFields' => ['customer_id' => true]],
        );
        $logins->saveOrFail($other);
        $token = (string)$this->requestAccess($this->customerNumber(self::CUSTOMER_ID), 'customer@example.com')['token'];

        $this->asPortal();
        $this->post('/api/customer-portal/access', [
            'token' => $token,
            'login_id' => $other->get('id'),
            'password' => 'a new password of mine',
            'ip' => '192.0.2.10',
        ]);

        $this->assertResponseCode(404);
        $this->assertSame('invalid', $this->answer()['status']);
    }

    /**
     * Asks for an access link through the portal and returns the answer.
     *
     * @param string $number The customer number typed.
     * @param string $email The e-mail typed.
     * @param string $ip The customer's address.
     * @return array<string, mixed>
     */
    private function requestAccess(string $number, string $email, string $ip = '192.0.2.10'): array
    {
        $this->asPortal();
        $this->post('/api/customer-portal/access-request', ['customer_number' => $number, 'email' => $email, 'ip' => $ip]);
        $this->assertResponseOk();

        return $this->answer();
    }

    /**
     * The number a customer knows themselves by.
     *
     * @param string $id The customer.
     * @return string
     */
    private function customerNumber(string $id): string
    {
        return (string)$this->getTableLocator()->get('Customers')->get($id)->get('number');
    }

    /**
     * The next request goes out as the portal, with a customer token when one is given.
     *
     * @param string|null $customerToken The customer token.
     * @return void
     */
    private function asPortal(?string $customerToken = null): void
    {
        $headers = ['Authorization' => 'Bearer ' . self::PORTAL_KEY, 'Accept' => 'application/json'];
        if ($customerToken !== null) {
            $headers[CustomerPortalController::TOKEN_HEADER] = $customerToken;
        }
        $this->configRequest(['headers' => $headers]);
    }

    /**
     * The next request goes out as a service that recognises customers by their address.
     *
     * @return void
     */
    private function asService(): void
    {
        $this->getTableLocator()->get('Users')
            ->updateAll(['role' => 'customer-identification'], ['id' => $this->firstId('Users')]);
        $this->asPortal();
    }

    /**
     * Signs the fixture customer in through the portal and returns the answer.
     *
     * @param string $password The password typed.
     * @param string $login The login typed.
     * @param string $ip The customer's address.
     * @return array<string, mixed>
     */
    private function signIn(string $password, string $login = self::LOGIN, string $ip = '192.0.2.10'): array
    {
        $this->asPortal();
        $this->post('/api/customer-portal/login', ['login' => $login, 'password' => $password, 'ip' => $ip]);
        $this->assertResponseOk();

        return $this->answer();
    }

    /**
     * The JSON answer of the last request.
     *
     * @return array<string, mixed>
     */
    private function answer(): array
    {
        $answer = json_decode($this->_getBodyAsString(), true);
        $this->assertIsArray($answer);

        return $answer;
    }

    /**
     * Stores a password hash as it is, past the setter that would hash it again.
     *
     * @param string $stored What is to be stored.
     * @return void
     */
    private function storePassword(string $stored): void
    {
        $this->getTableLocator()->get('Logins')->updateAll(['password' => $stored], ['id' => self::LOGIN_ID]);
    }

    /**
     * The fixture login as it is stored now.
     *
     * @return \Cake\Datasource\EntityInterface
     */
    private function storedLogin(): EntityInterface
    {
        return $this->getTableLocator()->get('Logins')->get(self::LOGIN_ID);
    }
}
