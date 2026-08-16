<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ContractVersionsController;
use App\Test\Traits\ControllerTestTrait;
use Cake\Datasource\EntityInterface;
use Cake\I18n\Date;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\ContractVersionsController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(ContractVersionsController::class)]
class ContractVersionsControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * Customer the nested routes hang off.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Contract the nested routes hang off.
     *
     * @var string
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

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
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/contract-versions');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/contract-versions?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/contract-versions/view/' . $this->firstId('ContractVersions'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/contract-versions/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/contract-versions/edit/' . $this->firstId('ContractVersions'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-versions/delete/' . $this->firstId('ContractVersions'));

        $this->assertRedirect();
    }

    /**
     * Added under its contract, the record is filed under them without the form saying so.
     *
     * The form under a customer and the contract leaves those fields out - the route already says which record it is,
     * and the controller fills them in. Posting them in the body instead, as a test reaching the
     * flat route does, asks a different question and leaves this one unasked.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::add()
     */
    public function testAddUnderTheRouteFilesItUnderTheRoute(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->idsIn('ContractVersions');
        $this->post('/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID . '/contract-versions/add', [
            'valid_from' => '2026-08-05',
            'obligations_settled' => false,
            'number_of_amendments' => 0,
        ]);

        $this->assertRedirect();
        $added = $this->addedRecord('ContractVersions', $before);
        // a contract version hangs off the contract alone and has no customer of its own
        $this->assertSame(self::CONTRACT_ID, $added->get('contract_id'));
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $contractVersionId = $this->firstId('ContractVersions');
        $this->post('/contract-versions/edit/' . $contractVersionId, ['note' => 'Signed on paper.']);

        $this->assertRedirect();
        $this->assertSame(
            'Signed on paper.',
            $this->getTableLocator()->get('ContractVersions')->get($contractVersionId)->note,
        );
    }

    /**
     * The listing can be narrowed to the minimum terms about to run out.
     *
     * Nothing else in the application looks ahead at a date - every other listing asks what
     * is current or past - and a term counted from today cannot be put in a fixture, so the
     * versions this asks about are written here.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::index()
     */
    public function testIndexNarrowedToObligationsEndingSoon(): void
    {
        $ending = $this->contractVersion([
            'obligation_until' => Date::today()->addDays(14),
            'obligations_settled' => false,
        ]);
        $distant = $this->contractVersion([
            'obligation_until' => Date::today()->addDays(400),
            'obligations_settled' => false,
        ]);
        $settled = $this->contractVersion([
            'obligation_until' => Date::today()->addDays(14),
            'obligations_settled' => true,
        ]);
        $past = $this->contractVersion([
            'obligation_until' => Date::today()->subDays(14),
            'obligations_settled' => false,
        ]);

        $this->login();
        $this->get('/contract-versions?obligations_ending=1');

        $this->assertResponseOk();
        $this->assertTrue($this->viewVariable('obligations_ending'));

        $ids = [];
        /** @var iterable<\App\Model\Entity\ContractVersion> $versions */
        $versions = $this->viewVariable('contractVersions');
        foreach ($versions as $version) {
            $ids[] = $version->id;
        }

        $this->assertContains($ending->get('id'), $ids);
        $this->assertNotContains($distant->get('id'), $ids);
        $this->assertNotContains($settled->get('id'), $ids, 'a settled term wants nothing');
        $this->assertNotContains($past->get('id'), $ids, 'a term already gone is not ending');
    }

    /**
     * Without the filter the listing is the whole of it, sorted as it always was.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::index()
     */
    public function testIndexWithoutTheObligationFilter(): void
    {
        $distant = $this->contractVersion([
            'obligation_until' => Date::today()->addDays(400),
            'obligations_settled' => false,
        ]);

        $this->login();
        $this->get('/contract-versions');

        $this->assertResponseOk();
        $this->assertFalse($this->viewVariable('obligations_ending'));

        $ids = [];
        /** @var iterable<\App\Model\Entity\ContractVersion> $versions */
        $versions = $this->viewVariable('contractVersions');
        foreach ($versions as $version) {
            $ids[] = $version->id;
        }

        $this->assertContains($distant->get('id'), $ids);
    }

    /**
     * A term on a version that a later one has replaced binds nobody: the customer was
     * re-signed over and the term that holds is the new version's. Its own is often left
     * unsettled on record, so being unsettled is not enough to raise it.
     *
     * What stays is a version whose validity has run out with no later one behind it - a
     * contract that ended while its term runs on, which is the case most worth seeing.
     *
     * @return void
     * @link \App\Model\Table\ContractVersionsTable::findObligationsEnding()
     */
    public function testIndexLeavesOutATermADeplacedVersionCarries(): void
    {
        // ended, nothing after it: the contract is over and the term runs on. On the other
        // contract of the fixtures, so the versions written below do not stand behind it.
        $ended = $this->contractVersion([
            'contract_id' => '9c0d5e5c-2a6b-4f8e-9a3d-1b7c4e2f6a90',
            'valid_from' => Date::today()->subDays(400),
            'valid_until' => Date::today()->subDays(30),
            'obligation_until' => Date::today()->addDays(14),
            'obligations_settled' => false,
        ]);

        // the same, but re-signed over since - the newer version carries the term that binds
        $replaced = $this->contractVersion([
            'valid_from' => Date::today()->subDays(300),
            'valid_until' => Date::today()->subDays(20),
            'obligation_until' => Date::today()->addDays(14),
            'obligations_settled' => false,
        ]);
        $this->contractVersion([
            'valid_from' => Date::today()->subDays(10),
            'obligation_until' => Date::today()->addDays(900),
            'obligations_settled' => false,
        ]);

        $this->login();
        $this->get('/contract-versions?obligations_ending=1');

        $this->assertResponseOk();

        $ids = [];
        /** @var iterable<\App\Model\Entity\ContractVersion> $versions */
        $versions = $this->viewVariable('contractVersions');
        foreach ($versions as $version) {
            $ids[] = $version->id;
        }

        $this->assertContains($ended->get('id'), $ids, 'ended, with nothing after it');
        $this->assertNotContains($replaced->get('id'), $ids, 'a later version carries the term now');
    }

    /**
     * A contract version of the fixture contract, differing by what it is asked for.
     *
     * @param array<string, mixed> $data What this version differs by.
     * @return \Cake\Datasource\EntityInterface
     */
    private function contractVersion(array $data): EntityInterface
    {
        $versions = $this->getTableLocator()->get('ContractVersions');

        return $versions->saveOrFail($versions->newEntity($data + [
            'contract_id' => self::CONTRACT_ID,
            'valid_from' => Date::today()->subDays(30),
        ]));
    }
}
