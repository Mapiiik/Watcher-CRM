<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ContractVersionsController;
use App\Model\Enum\ContractPrintType;
use App\Model\Enum\DocumentVariant;
use App\Service\ContractPrint\ContractDocuments;
use App\Test\Traits\ControllerTestTrait;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\I18n\Date;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Files\Service\FileStorage;
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
     * The version the fixture proposal hangs on, and that proposal.
     *
     * @var string
     */
    private const VERSION_ID = '74824fba-20b2-46fc-806c-df795aa9e429';
    private const PROPOSAL_ID = 'c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f60';

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
        'app.CustomerProposals',
        'app.ContractProposals',
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
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
     * A version is a storey of the workbench's address of its own: sometimes what somebody is
     * dealing with is one version of a contract and nothing else of it.
     *
     * @return void
     * @link \App\Controller\DocumentsController::manage()
     */
    public function testThePapersMayBeWorkedOnForOneVersionAlone(): void
    {
        $versions = $this->fetchTable('ContractVersions');
        $version = $versions->get(self::VERSION_ID);
        $contract = $this->fetchTable('Contracts')->get($version->contract_id);

        $this->login();
        $this->get(sprintf(
            '/customers/%s/contracts/%s/contract-versions/%s/documents/manage',
            $contract->customer_id,
            $contract->id,
            self::VERSION_ID,
        ));

        $this->assertResponseOk();

        // The version is a storey of the address, so the way back out says so.
        $this->assertResponseContains(h((string)$version->name));
        $this->assertNotSame([], (array)$this->viewVariable('rounds'));

        // And the table of papers is drawn at the version, not at the contract it belongs to.
        $this->assertSame(
            ['contractVersion', self::VERSION_ID],
            (array)$this->viewVariable('scope'),
        );

        // And only proposals that say something about this version are listed - the version
        // narrows this table as well as the papers below it. Move the fixture's papers off it and
        // the listing empties.
        $papers = $this->fetchTable('ContractProposals');
        $papers->saveOrFail(
            $papers->patchEntity($papers->get(self::PROPOSAL_ID), ['contract_version_id' => null]),
            ['checkRules' => false, 'validate' => false],
        );

        $this->get(sprintf(
            '/customers/%s/contracts/%s/contract-versions/%s/documents/manage',
            $contract->customer_id,
            $contract->id,
            self::VERSION_ID,
        ));

        $this->assertResponseOk();
        $this->assertSame([], (array)$this->viewVariable('rounds'));
    }

    /**
     * The papers of one version are reachable from it, above the heading and from the menu.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::view()
     */
    public function testTheVersionLeadsToItsOwnPapers(): void
    {
        $this->login();
        $this->get('/contract-versions/view/' . self::VERSION_ID);

        $this->assertResponseOk();
        $this->assertResponseContains('/contract-versions/' . self::VERSION_ID . '/documents/manage');
    }

    /**
     * A paper filed against one of the version's proposals is read where the papers are worked on,
     * and the version's page says the way there.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::view()
     */
    public function testTheCardLeadsToThePapersOfItsProposals(): void
    {
        $root = TMP . 'contract-version-papers-' . uniqid();
        Configure::write('Files.root', $root);

        $storage = new FileStorage();
        $link = $storage->link(
            $storage->store('%PDF-1.7 a scan', 'application/pdf'),
            ContractDocuments::MODEL,
            self::PROPOSAL_ID,
            ContractPrintType::ContractNew->value,
            DocumentVariant::ReceivedSignedByCustomer->value,
            ['name' => 'scan.pdf'],
        );

        $this->login();
        $this->get('/contract-versions/view/' . self::VERSION_ID);

        // The version's page is about the version. The papers are read where they are worked on,
        // and the page says the way there.
        $this->assertResponseOk();
        $this->assertResponseNotContains(__('Received Documents'));
        $this->assertResponseContains('/documents/manage');

        $contracts = $this->fetchTable('Contracts');
        $contract = $contracts->get($this->fetchTable('ContractVersions')->get(self::VERSION_ID)->contract_id);

        $this->get(sprintf(
            '/customers/%s/contracts/%s/documents/manage',
            $contract->customer_id,
            $contract->id,
        ));
        $this->assertResponseOk();
        $this->assertResponseContains(sprintf('/files/file-links/download/%s', $link->id));

        Configure::delete('Files.root');
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
     * The roles that write versions may now take one back, which they could not before - but only
     * while there is no paper behind it and it has not become history.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::delete()
     */
    public function testAnUnsignedVersionOfThisMonthMayBeDeletedByWhoeverWritesThem(): void
    {
        $version = $this->contractVersion([
            'valid_from' => Date::today()->firstOfMonth(),
            'conclusion_date' => null,
        ]);

        $this->login('bookkeeper');
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-versions/delete/' . $version->get('id'));

        $this->assertRedirect();
        $this->assertFalse($this->getTableLocator()->get('ContractVersions')->exists(['id' => $version->get('id')]));
    }

    /**
     * What was signed stays, and so does what belongs to a month already behind us.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::delete()
     */
    public function testASignedOrOlderVersionIsNotTheirsToDelete(): void
    {
        $signed = $this->contractVersion([
            'valid_from' => Date::today()->firstOfMonth(),
            'conclusion_date' => Date::today()->firstOfMonth(),
        ]);
        $older = $this->contractVersion([
            'valid_from' => Date::today()->firstOfMonth()->subDays(1),
            'conclusion_date' => null,
        ]);

        $versions = $this->getTableLocator()->get('ContractVersions');

        $this->login('bookkeeper');
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/contract-versions/delete/' . $signed->get('id'));
        $this->assertTrue($versions->exists(['id' => $signed->get('id')]), 'A signed version was deleted.');

        $this->post('/contract-versions/delete/' . $older->get('id'));
        $this->assertTrue($versions->exists(['id' => $older->get('id')]), 'A version from last month was deleted.');
    }

    /**
     * The admin keeps the reach they have always had.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::delete()
     */
    public function testAnAdminDeletesAVersionWhateverItSays(): void
    {
        // Its own version, because a version a proposal hangs on is held by the proposal rather
        // than by what the version says.
        $versionId = (string)$this->contractVersion(['valid_from' => Date::today()])->get('id');

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-versions/delete/' . $versionId);

        $this->assertRedirect();
        $this->assertFalse($this->getTableLocator()->get('ContractVersions')->exists(['id' => $versionId]));
    }

    /**
     * A stretch of time that cannot exist is refused by the form rather than stored for a check
     * to find months later.
     *
     * @return void
     * @link \App\Controller\ContractVersionsController::edit()
     */
    public function testTheFormRefusesAVersionThatEndsBeforeItBegins(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $versionId = $this->firstId('ContractVersions');
        $versions = $this->getTableLocator()->get('ContractVersions');
        $stored = $versions->get($versionId)->valid_until;

        $this->post('/contract-versions/edit/' . $versionId, [
            'valid_until' => $versions->get($versionId)->valid_from->subDays(1)->toDateString(),
        ]);

        $this->assertNoRedirect();
        $this->assertEquals($stored, $versions->get($versionId)->valid_until, 'The impossible period was stored.');
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
