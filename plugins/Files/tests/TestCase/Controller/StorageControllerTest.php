<?php
declare(strict_types=1);

namespace Files\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Files\Controller\StorageController;
use Files\Service\FileStorage;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Files\Controller\StorageController Test Case
 *
 * The page exists so that content nothing points at is visible somewhere, which is the one thing
 * the documents listing cannot show. That is what is asked of it here.
 */
#[UsesClass(StorageController::class)]
class StorageControllerTest extends TestCase
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
     * The record the filed document hangs on.
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

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->root = TMP . 'storage-controller-' . uniqid();
        Configure::write('Files.root', $this->root);

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
        $this->removeDirectory($this->root);

        parent::tearDown();
    }

    /**
     * @link \Files\Controller\StorageController::index()
     * @return void
     */
    public function testTheShelfShowsWhatIsOnItIncludingWhatNothingWants(): void
    {
        $storage = new FileStorage();

        $wanted = $storage->store('a paper somebody filed', 'application/pdf');
        $storage->link($wanted, 'ContractProposals', self::RECORD, 'contract-new', 'generated');

        $orphan = $storage->store('bytes a torn backup left behind', 'application/pdf');

        $this->get('/files/storage');

        $this->assertResponseOk();
        $this->assertResponseContains(substr($wanted->hash, 0, 12));
        $this->assertResponseContains(substr($orphan->hash, 0, 12));

        $this->get('/files/storage?unused=1');

        $this->assertResponseOk();
        $this->assertResponseContains(substr($orphan->hash, 0, 12));
        $this->assertResponseNotContains(substr($wanted->hash, 0, 12));
    }

    /**
     * @link \Files\Controller\StorageController::view()
     * @return void
     */
    public function testOnePieceOfContentShowsEverythingThatPointsAtIt(): void
    {
        $storage = new FileStorage();
        $file = $storage->store('a paper two records share', 'application/pdf');

        $storage->link($file, 'ContractProposals', self::RECORD, 'contract-new', 'generated');
        $storage->link($file, 'Customers', self::RECORD, 'gdpr-new', 'generated');

        $this->get('/files/storage/view/' . $file->id);

        $this->assertResponseOk();
        $this->assertResponseContains('contract-new');
        $this->assertResponseContains('gdpr-new');
    }

    /**
     * @link \Files\Controller\StorageController::view()
     * @return void
     */
    public function testContentWhoseBytesAreGoneSaysSo(): void
    {
        $storage = new FileStorage();
        $file = $storage->store('a paper that went missing', 'application/pdf');
        $storage->filesystem()->delete($file->path);

        $this->get('/files/storage/view/' . $file->id);

        $this->assertResponseOk();
        $this->assertResponseContains('not in the store');
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
