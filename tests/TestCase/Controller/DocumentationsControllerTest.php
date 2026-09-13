<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\DocumentationsController;
use App\Model\Table\DocumentationsTable;
use App\Test\Traits\ControllerTestTrait;
use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Files\Service\Documentations;
use Files\Service\FileStorage;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\DocumentationsController Test Case
 *
 * The agenda reads what the address it was opened at says: under a connection it is that
 * connection's documentation, under a customer it is everything about them, and with no nesting
 * at all it is the lot. Filing works the same way round, which is what keeps documentation from
 * being hung on somebody else's record.
 */
#[UsesClass(DocumentationsController::class)]
class DocumentationsControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;
    use LocatorAwareTrait;

    /**
     * The records the documentation hangs on.
     *
     * @var string
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';
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
        'plugin.Files.DocumentationTypes',
        'plugin.Files.Documentations',
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
    ];

    /**
     * Where the bytes go while this runs.
     *
     * @var string
     */
    private string $root;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->root = TMP . 'documentations-' . uniqid();
        Configure::write('Files.root', $this->root);

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Configure::delete('Files.root');
        $this->removeDirectory($this->root);

        parent::tearDown();
    }

    /**
     * @link \App\Controller\DocumentationsController::add()
     * @return void
     */
    public function testWhatItHangsOnComesFromTheAddressAndNotFromTheForm(): void
    {
        $kind = $this->kind();

        $this->post(
            '/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID . '/documentations/add',
            ['documentation_type_id' => $kind, 'name' => 'The installation'],
        );

        $this->assertResponseSuccess();

        /** @var \App\Model\Entity\Documentation $filed */
        $filed = $this->documentations()->find()->firstOrFail();

        $this->assertSame(self::CONTRACT_ID, $filed->contract_id);
        $this->assertSame(
            self::CUSTOMER_ID,
            $filed->customer_id,
            'A route naming a connection names the customer with it.',
        );
    }

    /**
     * @link \App\Controller\DocumentationsController::index()
     * @return void
     */
    public function testTheListingFollowsTheAddressItWasOpenedAt(): void
    {
        $kind = $this->kind();
        $this->documentations()->saveOrFail($this->documentations()->newEntity([
            'documentation_type_id' => $kind,
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => self::CONTRACT_ID,
            'name' => 'The installation',
        ]));
        $this->documentations()->saveOrFail($this->documentations()->newEntity([
            'documentation_type_id' => $kind,
            'name' => 'Something filed against nobody',
        ]));

        $this->get('/documentations');
        $this->assertResponseContains('The installation');
        $this->assertResponseContains('Something filed against nobody');

        $this->get('/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID . '/documentations');
        $this->assertResponseContains('The installation');
        $this->assertResponseNotContains('Something filed against nobody');
    }

    /**
     * @link \App\Controller\DocumentationsController::add()
     * @return void
     */
    public function testAKindThatAsksForAConnectionIsNotFiledWithoutOne(): void
    {
        $kind = $this->kind(['contract_required' => true]);

        $this->post('/documentations/add', ['documentation_type_id' => $kind, 'name' => 'Nowhere in particular']);

        $this->assertResponseSuccess();
        $this->assertSame(0, $this->documentations()->find()->count());
    }

    /**
     * @link \App\Controller\DocumentationsController::delete()
     * @return void
     */
    public function testDeletingOneTakesWhatItHeldWithIt(): void
    {
        $documentation = $this->documentations()->saveOrFail($this->documentations()->newEntity([
            'documentation_type_id' => $this->kind(),
            'contract_id' => self::CONTRACT_ID,
            'name' => 'The installation',
        ]));

        $storage = new FileStorage();
        $file = $storage->store('a photograph of the mast', 'text/plain');
        $storage->link(
            $file,
            Documentations::MODEL,
            (string)$documentation->id,
            Documentations::ATTACHMENT,
            Documentations::FILED,
        );

        $this->post('/documentations/delete/' . $documentation->id);

        $this->assertRedirect();
        $this->assertSame(0, $this->documentations()->find()->count());
        $this->assertFalse($storage->has($file), 'The bytes should have gone with the last link to them.');
    }

    /**
     * Every page of the agenda answers. Little is asked of them beyond that, but a page that has
     * stopped rendering at all is worth hearing about from the suite rather than from somebody
     * clicking.
     *
     * @link \App\Controller\DocumentationsController::view()
     * @return void
     */
    public function testEveryPageOfTheAgendaAnswers(): void
    {
        $documentation = $this->documentations()->saveOrFail($this->documentations()->newEntity([
            'documentation_type_id' => $this->kind(),
            'contract_id' => self::CONTRACT_ID,
            'customer_id' => self::CUSTOMER_ID,
            'name' => 'The installation',
        ]));

        $under = '/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID . '/documentations/';

        $pages = [
            'add',
            'view/' . $documentation->id,
            'edit/' . $documentation->id,
            'add-files/' . $documentation->id,
        ];

        foreach ($pages as $page) {
            $this->get($under . $page);
            $this->assertResponseOk('The page at ' . $page . ' did not answer.');
        }
    }

    /**
     * A kind of documentation, saved, by its id.
     *
     * @param array<string, mixed> $said What to say about it.
     * @return string
     */
    private function kind(array $said = []): string
    {
        $types = $this->fetchTable('DocumentationTypes');

        return (string)$types->saveOrFail($types->newEntity($said + [
            'name' => 'Installation',
            'position' => 0,
            'currently_offered' => true,
            'date_required' => false,
            'customer_required' => false,
            'contract_required' => false,
        ]))->id;
    }

    /**
     * @return \App\Model\Table\DocumentationsTable
     */
    private function documentations(): DocumentationsTable
    {
        /** @var \App\Model\Table\DocumentationsTable $table */
        $table = $this->fetchTable('Documentations');

        return $table;
    }

    /**
     * Removes a directory and everything under it.
     *
     * @param string $directory The directory.
     * @return void
     */
    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (array_diff((array)scandir($directory), ['.', '..']) as $entry) {
            $path = $directory . DS . $entry;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
