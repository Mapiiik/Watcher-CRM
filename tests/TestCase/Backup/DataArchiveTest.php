<?php
declare(strict_types=1);

namespace App\Test\TestCase\Backup;

use App\Backup\DataArchive;
use App\Backup\Process;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

/**
 * App\Backup\DataArchive Test Case
 *
 * The archive is the half of a backup nothing else can put right, so what is asked of it is that
 * what goes in comes out again, byte for byte and in the same places.
 */
#[CoversClass(DataArchive::class)]
class DataArchiveTest extends TestCase
{
    /**
     * Where this test builds its data roots.
     *
     * @var string
     */
    private string $workspace;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        if (Process::locate(DataArchive::PROGRAM) === null) {
            $this->markTestSkipped('tar is not installed here.');
        }

        $this->workspace = TMP . 'data-archive-' . uniqid();
        mkdir($this->workspace . DS . 'source' . DS . 'images', 0770, true);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspace);

        parent::tearDown();
    }

    /**
     * @link \App\Backup\DataArchive::pack()
     * @link \App\Backup\DataArchive::unpack()
     * @return void
     */
    public function testWhatIsPackedComesBackWhereItWas(): void
    {
        $source = $this->workspace . DS . 'source';
        file_put_contents($source . DS . 'images' . DS . 'logo.png', 'a letterhead');
        file_put_contents($source . DS . 'note.txt', 'and something beside it');

        $archive = $this->workspace . DS . DataArchive::FILENAME;
        (new DataArchive($source))->pack($archive);

        $this->assertFileExists($archive);

        $target = $this->workspace . DS . 'target';
        (new DataArchive($target))->unpack($archive);

        $this->assertStringEqualsFile($target . DS . 'images' . DS . 'logo.png', 'a letterhead');
        $this->assertStringEqualsFile($target . DS . 'note.txt', 'and something beside it');
    }

    /**
     * A restore runs against a deployment that has been wiped, so the root it writes into is
     * usually not there yet.
     *
     * @link \App\Backup\DataArchive::unpack()
     * @return void
     */
    public function testUnpackingMakesTheDataRootWhenThereIsNone(): void
    {
        $source = $this->workspace . DS . 'source';
        file_put_contents($source . DS . 'note.txt', 'something');

        $archive = $this->workspace . DS . DataArchive::FILENAME;
        (new DataArchive($source))->pack($archive);

        $target = $this->workspace . DS . 'not' . DS . 'there' . DS . 'yet';
        $this->assertDirectoryDoesNotExist($target);

        (new DataArchive($target))->unpack($archive);

        $this->assertFileExists($target . DS . 'note.txt');
    }

    /**
     * @link \App\Backup\DataArchive::pack()
     * @return void
     */
    public function testPackingSaysSoWhenThereIsNoDataRootToPack(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('There is no data root at');

        (new DataArchive($this->workspace . DS . 'nothing here'))->pack($this->workspace . DS . 'out.tar');
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
