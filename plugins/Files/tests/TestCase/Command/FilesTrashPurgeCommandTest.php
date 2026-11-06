<?php
declare(strict_types=1);

namespace Files\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Files\Command\FilesTrashPurgeCommand;
use Files\Service\FileStorage;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Files\Command\FilesTrashPurgeCommand Test Case
 */
#[UsesClass(FilesTrashPurgeCommand::class)]
class FilesTrashPurgeCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
        'plugin.Files.RemovedFileLinks',
    ];

    /**
     * The record the papers hang on. Nothing reads it.
     *
     * @var string
     */
    private const RECORD = '11111111-2222-4333-8444-555555555555';

    /**
     * Where the bytes and the pictures go while this runs.
     *
     * @var string
     */
    private string $root;
    private string $previews;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->root = TMP . 'trash-purge-' . uniqid();
        $this->previews = TMP . 'trash-purge-previews-' . uniqid();
        Configure::write('Files.root', $this->root);
        Configure::write('Files.previews', $this->previews);
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
        $this->removeDirectory($this->root);
        $this->removeDirectory($this->previews);

        parent::tearDown();
    }

    /**
     * @link \Files\Command\FilesTrashPurgeCommand::execute()
     * @return void
     */
    public function testThirtyDaysIsWhatItKeepsUnlessToldOtherwise(): void
    {
        $this->removedDaysAgo('removed long ago', 31);
        $this->removedDaysAgo('removed lately', 29);

        $this->exec('files_trash_purge');

        $this->assertExitSuccess();
        $this->assertOutputContains('1 thrown away.');
        $this->assertSame(1, $this->fetchTable('Files.RemovedFileLinks')->find()->count());

        $this->exec('files_trash_purge --days 7');

        $this->assertExitSuccess();
        $this->assertSame(0, $this->fetchTable('Files.RemovedFileLinks')->find()->count());
        $this->assertSame(0, $this->fetchTable('Files.Files')->find()->count());
    }

    /**
     * A typing error must not empty the trash.
     *
     * @link \Files\Command\FilesTrashPurgeCommand::execute()
     * @return void
     */
    public function testAnUnusableNumberOfDaysThrowsNothingAway(): void
    {
        $this->removedDaysAgo('removed long ago', 31);

        foreach (['0', 'thirty'] as $days) {
            $this->exec('files_trash_purge --days ' . $days);

            $this->assertExitError();
        }

        $this->assertSame(1, $this->fetchTable('Files.RemovedFileLinks')->find()->count());
    }

    /**
     * Puts a paper in the trash some time ago.
     *
     * @param string $bytes What is in it.
     * @param int $days How long ago.
     * @return void
     */
    private function removedDaysAgo(string $bytes, int $days): void
    {
        $storage = new FileStorage();
        $file = $storage->store($bytes, 'application/pdf');

        $storage->unlink(
            $storage->link($file, 'ContractProposals', self::RECORD, 'contract-new', 'received'),
            DateTime::now()->subDays($days),
        );
    }

    /**
     * Takes a directory and everything under it away again.
     *
     * @param string $directory Which one.
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
