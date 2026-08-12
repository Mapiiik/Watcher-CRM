<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\PhonesController;
use App\Test\Traits\ConfigureTestTrait;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\PhonesController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(PhonesController::class)]
class PhonesControllerTest extends TestCase
{
    use ConfigureTestTrait;
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    public function tearDown(): void
    {
        $this->restoreConfigure();

        parent::tearDown();
    }

    /**
     * Customer the nested routes hang off.
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
        'app.Phones',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\PhonesController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/phones');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\PhonesController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/phones?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\PhonesController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/phones/view/' . $this->firstId('Phones'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\PhonesController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/phones/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\PhonesController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/phones/edit/' . $this->firstId('Phones'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\PhonesController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/phones/delete/' . $this->firstId('Phones'));

        $this->assertRedirect();
    }

    /**
     * Added under its customer, the record is filed under them without the form saying so.
     *
     * The form under a customer leaves those fields out - the route already says which record it is,
     * and the controller fills them in. Posting them in the body instead, as a test reaching the
     * flat route does, asks a different question and leaves this one unasked.
     *
     * @return void
     * @link \App\Controller\PhonesController::add()
     */
    public function testAddUnderTheRouteFilesItUnderTheRoute(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->idsIn('Phones');
        $this->post('/customers/' . self::CUSTOMER_ID . '/phones/add', [
            'phone' => '+420 601 234 567',
        ]);

        $this->assertRedirect();
        $added = $this->addedRecord('Phones', $before);
        $this->assertSame(self::CUSTOMER_ID, $added->get('customer_id'));
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\PhonesController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $phoneId = $this->firstId('Phones');
        $this->post('/phones/edit/' . $phoneId, ['note' => 'Reaches the caretaker.']);

        $this->assertRedirect();
        $this->assertSame(
            'Reaches the caretaker.',
            $this->getTableLocator()->get('Phones')->get($phoneId)->note,
        );
    }

    /**
     * Numbers that were stored before they were formatted on save - or by a version that formatted
     * them differently - are brought into one format by the run from the settings page.
     *
     * @return void
     * @link \App\Controller\PhonesController::formatAll()
     */
    public function testFormatAllPutsTheStoredNumbersIntoOneFormat(): void
    {
        $this->withConfigure(['Phones.defaultRegion' => 'CZ']);
        $phoneId = $this->storePhoneUnformatted('601234567');

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/phones/format-all');

        $this->assertRedirect();
        $this->assertSame(
            '+420 601 234 567',
            $this->getTableLocator()->get('Phones')->get($phoneId)->phone,
        );
    }

    /**
     * A value that cannot be read as a number is left as it stands - the run reports it rather
     * than making something up for it.
     *
     * @return void
     * @link \App\Controller\PhonesController::formatAll()
     */
    public function testFormatAllLeavesANumberItCannotReadAlone(): void
    {
        $this->withConfigure(['Phones.defaultRegion' => 'CZ']);
        $phoneId = $this->storePhoneUnformatted('reception desk');

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/phones/format-all');

        $this->assertRedirect();
        $this->assertSame(
            'reception desk',
            $this->getTableLocator()->get('Phones')->get($phoneId)->phone,
        );
    }

    /**
     * Stores a number the way one could already be sitting in the table - past the marshalling
     * that would format it and past the rule that would refuse it.
     *
     * @param string $phone Number to store as it stands.
     * @return string Id of the stored record.
     */
    private function storePhoneUnformatted(string $phone): string
    {
        $phones = $this->getTableLocator()->get('Phones');

        $entity = $phones->newEmptyEntity();
        $entity->set('customer_id', self::CUSTOMER_ID);
        $entity->set('phone', $phone);

        $phones->saveOrFail($entity, ['checkRules' => false]);

        return (string)$entity->get('id');
    }
}
