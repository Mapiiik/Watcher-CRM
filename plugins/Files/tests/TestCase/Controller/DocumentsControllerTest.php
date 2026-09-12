<?php
declare(strict_types=1);

namespace Files\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Files\Controller\DocumentsController;
use Files\Model\Entity\FileLink;
use Files\Service\FileStorage;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Files\Controller\DocumentsController Test Case
 *
 * @link \Files\Controller\DocumentsController
 */
#[UsesClass(DocumentsController::class)]
class DocumentsControllerTest extends TestCase
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
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
    ];

    /**
     * The record the documents in here hang on.
     *
     * @var string
     */
    private const RECORD = '11111111-2222-4333-8444-555555555555';

    /**
     * Where the bytes go while this runs.
     *
     * @var string
     */
    private string $root;
    private string $previews;

    /**
     * What the application said records lead to before this ran.
     *
     * @var mixed
     */
    private mixed $records;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->root = TMP . 'documents-controller-' . uniqid();
        $this->previews = TMP . 'documents-controller-previews-' . uniqid();
        Configure::write('Files.root', $this->root);
        Configure::write('Files.previews', $this->previews);
        $this->records = Configure::read('Files.records');

        $this->login();
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
        Configure::delete('Files.previews');
        Configure::write('Files.records', $this->records);
        $this->removeDirectory($this->root);
        $this->removeDirectory($this->previews);

        parent::tearDown();
    }

    /**
     * @link \Files\Controller\DocumentsController::index()
     * @return void
     */
    public function testTheIndexListsWhatIsFiled(): void
    {
        $this->file('a contract', 'IMG_001.jpg');

        $this->get('/files/documents');

        $this->assertResponseOk();
        $this->assertResponseContains('IMG_001.jpg');
        $this->assertResponseContains('contract-new');
    }

    /**
     * A document says what it is filed against, and offers the way to it where the application
     * has said where that model is to be found.
     *
     * @link \Files\View\Helper\RecordHelper::linkTo()
     * @return void
     */
    public function testADocumentOffersTheWayToWhatItIsFiledAgainst(): void
    {
        Configure::write('Files.records', [
            'ContractProposals' => ['plugin' => null, 'controller' => 'ContractProposals', 'action' => 'view'],
        ]);
        $this->file('a contract', 'IMG_001.jpg');

        $this->get('/files/documents');

        $this->assertResponseOk();
        $this->assertResponseContains('/contract-proposals/view/' . self::RECORD);
    }

    /**
     * The plugin knows a record by the name of its model and nothing else, so a name nobody has
     * declared is said plainly rather than guessed at.
     *
     * @link \Files\View\Helper\RecordHelper::urlFor()
     * @return void
     */
    public function testAModelTheApplicationHasNotDeclaredIsNamedRatherThanGuessedAt(): void
    {
        Configure::write('Files.records', []);
        $this->file('a contract', 'IMG_001.jpg');

        $this->get('/files/documents');

        $this->assertResponseOk();
        $this->assertResponseContains('ContractProposals');
        $this->assertResponseNotContains('/contract-proposals/view/');
    }

    /**
     * @link \Files\Controller\DocumentsController::download()
     * @return void
     */
    public function testTheContentComesBackUnderTheNameItArrivedWith(): void
    {
        $link = $this->file('what the customer signed', 'IMG_001.jpg');

        $this->get('/files/documents/download/' . $link->id);

        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Disposition', 'IMG_001.jpg');
        $this->assertNotNull($this->_response);
        $this->assertSame('what the customer signed', (string)$this->_response->getBody());
    }

    /**
     * Looking at a paper is a step shorter than keeping it, so it is offered for looking at.
     *
     * @link \Files\Controller\DocumentsController::open()
     * @return void
     */
    public function testAPaperMayBeLookedAtRatherThanKept(): void
    {
        $link = $this->file('what the customer signed', 'IMG_001.jpg');

        $this->get('/files/documents/open/' . $link->id);

        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Disposition', 'inline');
        $this->assertHeaderContains('Content-Disposition', 'IMG_001.jpg');
    }

    /**
     * Anything the browser would run instead of draw is handed over to be kept, whatever was
     * asked for. The content came from outside, and opening it in our own origin would be handing
     * a stranger the session.
     *
     * @link \Files\Controller\DocumentsController::open()
     * @return void
     */
    public function testWhatTheBrowserWouldRunIsNeverOpened(): void
    {
        $storage = new FileStorage();
        $file = $storage->store('<script>alert(1)</script>', 'image/svg+xml');
        $link = $storage->link(
            $file,
            'ContractProposals',
            self::RECORD,
            'contract-new',
            'received-signed-by-customer',
            ['name' => 'drawing.svg'],
        );

        $this->get('/files/documents/open/' . $link->id);

        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Disposition', 'attachment');
    }

    /**
     * The picture goes through the same door as the document, so that whoever may look at one may
     * look at the other and nobody else.
     *
     * @link \Files\Controller\DocumentsController::thumbnail()
     * @link \Files\Controller\DocumentsController::preview()
     * @return void
     */
    public function testAPictureOfAPageComesBackAsSomethingEveryBrowserDraws(): void
    {
        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('There are no pictures to be made without imagick.');
        }

        $link = $this->filed('picture.jpg', 'image/jpeg', 'IMG_001.jpg');

        foreach (['thumbnail', 'preview'] as $size) {
            $this->get('/files/documents/' . $size . '/' . $link->id);

            $this->assertResponseOk();
            $this->assertContentType('webp');
            // Content addressed by what is in it cannot change, so it is worth keeping - but only
            // in the browser that asked, since the door it came through has a login on it.
            $this->assertHeaderContains('Cache-Control', 'private');
        }
    }

    /**
     * A spreadsheet has no picture. Saying so is better than a broken image, because the page
     * that asked can then show something of its own.
     *
     * @link \Files\Controller\DocumentsController::thumbnail()
     * @return void
     */
    public function testWhatHasNoPictureSaysSoRatherThanSendingABrokenOne(): void
    {
        $storage = new FileStorage();
        $file = $storage->store('name,amount', 'text/csv');
        $link = $storage->link(
            $file,
            'ContractProposals',
            self::RECORD,
            'contract-new',
            'received-signed-by-customer',
            ['name' => 'ledger.csv'],
        );

        $this->get('/files/documents/thumbnail/' . $link->id);

        $this->assertResponseCode(404);
    }

    /**
     * A row whose bytes are gone is a torn backup, not a missing page, so it says so rather than
     * handing over nothing.
     *
     * @link \Files\Controller\DocumentsController::download()
     * @return void
     */
    public function testContentThatIsNotOnTheShelfIsNotHandedOver(): void
    {
        $link = $this->file('a paper that went missing', 'IMG_001.jpg');

        (new FileStorage())->filesystem()->delete($link->file->path);

        $this->get('/files/documents/download/' . $link->id);

        $this->assertResponseCode(404);
    }

    /**
     * @link \Files\Controller\DocumentsController::delete()
     * @return void
     */
    public function testUnfilingADocumentTakesTheBytesWithTheLastOfThem(): void
    {
        $link = $this->file('the only use of this content', 'IMG_001.jpg');

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/files/documents/delete/' . $link->id);

        $this->assertRedirect();
        $this->assertSame(0, $this->fetchTable('Files.FileLinks')->find()->count());
        $this->assertSame(0, $this->fetchTable('Files.Files')->find()->count());
    }

    /**
     * Files something for the test to look at.
     *
     * @param string $bytes What is in it.
     * @param string $name What it arrived called.
     * @return \Files\Model\Entity\FileLink
     */
    private function file(string $bytes, string $name): FileLink
    {
        $storage = new FileStorage();
        $file = $storage->store($bytes, 'image/jpeg');
        $link = $storage->link(
            $file,
            'ContractProposals',
            self::RECORD,
            'contract-new',
            'received-signed-by-customer',
            ['name' => $name],
        );
        $link->file = $file;

        return $link;
    }

    /**
     * Files one of the files beside these tests, for the ones that need real content.
     *
     * @param string $content Which file.
     * @param string $mime_type What to file it as.
     * @param string $name What it arrived called.
     * @return \Files\Model\Entity\FileLink
     */
    private function filed(string $content, string $mime_type, string $name): FileLink
    {
        $storage = new FileStorage();
        $file = $storage->storeFile(dirname(__DIR__, 2) . DS . 'content' . DS . $content, $mime_type);
        $link = $storage->link(
            $file,
            'ContractProposals',
            self::RECORD,
            'contract-new',
            'received-signed-by-customer',
            ['name' => $name],
        );
        $link->file = $file;

        return $link;
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
