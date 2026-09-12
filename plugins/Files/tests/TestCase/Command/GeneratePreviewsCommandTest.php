<?php
declare(strict_types=1);

namespace Files\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Files\Command\GeneratePreviewsCommand;
use Files\Model\Entity\File;
use Files\Service\FileStorage;
use Files\Service\Previews;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Files\Command\GeneratePreviewsCommand Test Case
 *
 * What is asked of it is that a second run has nothing to do. That is the whole reason it can be
 * put on a cron: a run every hour over a store of thousands costs a directory listing, not
 * thousands of conversions.
 */
#[UsesClass(GeneratePreviewsCommand::class)]
class GeneratePreviewsCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
    ];

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

        if (!extension_loaded('imagick')) {
            $this->markTestSkipped('There are no pictures to be made without imagick.');
        }

        $this->root = TMP . 'generate-previews-store-' . uniqid();
        $this->previews = TMP . 'generate-previews-' . uniqid();

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
     * @link \Files\Command\GeneratePreviewsCommand::execute()
     * @return void
     */
    public function testWhatIsFiledGetsItsPictureWithoutAnybodyAsking(): void
    {
        $file = $this->stored('picture.jpg', 'image/jpeg');

        $this->exec('generate_previews');

        $this->assertExitSuccess();
        // What it says it did, before what is on disk. A picture that was refused and a picture
        // written somewhere else are the same missing file, and the reason for a refusal goes to
        // the log, where a failure here would never show it.
        $this->assertOutputContains('1 made.');
        $this->assertErrorEmpty();

        foreach ([Previews::THUMBNAIL, Previews::PREVIEW] as $size) {
            $this->assertFileExists(Previews::pathFor((string)$file->id, $size));
        }
    }

    /**
     * @link \Files\Command\GeneratePreviewsCommand::execute()
     * @return void
     */
    public function testASecondRunHasNothingToDo(): void
    {
        $this->stored('picture.jpg', 'image/jpeg');

        $this->exec('generate_previews');
        $this->assertExitSuccess();

        $made = $this->howManyMade();

        $this->exec('generate_previews');

        $this->assertExitSuccess();
        $this->assertSame($made, $this->howManyMade(), 'Nothing should have been made a second time.');
    }

    /**
     * A spreadsheet has no picture and never will. Passing over it quietly is what keeps a nightly
     * run from reporting the same non-problem for ever.
     *
     * @link \Files\Command\GeneratePreviewsCommand::execute()
     * @return void
     */
    public function testWhatHasNoPictureIsPassedOverWithoutComplaint(): void
    {
        $this->stored('picture.jpg', 'application/vnd.ms-excel');

        $this->exec('generate_previews');

        $this->assertExitSuccess();
        $this->assertSame(0, $this->howManyMade());
        $this->assertErrorEmpty();
    }

    /**
     * @link \Files\Command\GeneratePreviewsCommand::execute()
     * @return void
     */
    public function testItStopsWhereItIsToldTo(): void
    {
        $this->stored('picture.jpg', 'image/jpeg');
        $this->stored('page.pdf', 'application/pdf');

        $this->exec('generate_previews --limit 1');

        $this->assertExitSuccess();

        $this->assertSame(1, $this->howManyMade(), 'Only the one it was allowed should have been made.');
    }

    /**
     * How many have a thumbnail so far.
     *
     * @return int
     */
    private function howManyMade(): int
    {
        return count((array)glob(
            $this->previews . DS . Previews::THUMBNAIL . DS . '*' . DS . '*' . DS . '*.webp',
        ));
    }

    /**
     * Puts one of the files beside the service's test into the store.
     *
     * @param string $name Which one.
     * @param string $mime_type What to file it as.
     * @return \Files\Model\Entity\File
     */
    private function stored(string $name, string $mime_type): File
    {
        return (new FileStorage())->storeFile(
            dirname(__DIR__, 2) . DS . 'content' . DS . $name,
            $mime_type,
        );
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
