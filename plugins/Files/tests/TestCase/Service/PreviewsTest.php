<?php
declare(strict_types=1);

namespace Files\Test\TestCase\Service;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Files\Model\Entity\File;
use Files\Service\FileStorage;
use Files\Service\Previews;
use Imagick;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Files\Service\Previews Test Case
 *
 * The two things worth asking are whether what comes out is a picture of what went in, and
 * whether it is made once. Both are asked of real content rather than of a mock: the whole job is
 * handing bytes to something outside PHP and reading back what it made of them, and a mock of
 * that would only prove the mock.
 */
#[CoversClass(Previews::class)]
class PreviewsTest extends TestCase
{
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

        $this->root = TMP . 'previews-store-' . uniqid();
        $this->previews = TMP . 'previews-' . uniqid();

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
     * @link \Files\Service\Previews::thumbnail()
     * @link \Files\Service\Previews::preview()
     * @return void
     */
    public function testAPictureIsMadeOfWhatWasFiled(): void
    {
        $file = $this->stored('picture.jpg', 'image/jpeg');
        $previews = new Previews();

        $thumbnail = $previews->thumbnail($file);
        $preview = $previews->preview($file);

        $this->assertNotNull($thumbnail);
        $this->assertNotNull($preview);

        // Both are pictures of the same thing, one small enough for a strip and one large enough
        // to read. The source is 600 by 400, so the preview is left at its own size.
        $this->assertSame([240, 160], $this->sizeOf($thumbnail));
        $this->assertSame([600, 400], $this->sizeOf($preview));
    }

    /**
     * The whole reason for keeping them: the second asking costs nothing.
     *
     * Proven by taking the content away afterwards. Anything that went back to the store for it
     * would have nothing to work from, so an answer at all is an answer from what was kept.
     *
     * @link \Files\Service\Previews::generate()
     * @return void
     */
    public function testWhatHasBeenMadeIsNotMadeAgain(): void
    {
        $file = $this->stored('picture.jpg', 'image/jpeg');
        $previews = new Previews();

        $first = $previews->thumbnail($file);
        $this->assertNotNull($first);

        (new FileStorage())->filesystem()->delete($file->path);

        $this->assertSame($first, $previews->thumbnail($file));
    }

    /**
     * A scanned contract is the case this was built for, and it is the one that needs a program
     * outside ImageMagick to draw it.
     *
     * @link \Files\Service\Previews::preview()
     * @return void
     */
    public function testTheFirstPageOfAPaperIsDrawn(): void
    {
        $file = $this->stored('page.pdf', 'application/pdf');

        $preview = (new Previews())->preview($file);

        $this->assertNotNull($preview, 'A PDF has a first page and this should be a picture of it.');

        // The page is twice as wide as it is tall, and asking for it at 150 dots per inch is what
        // decides how large that comes out.
        [$width, $height] = $this->sizeOf($preview);
        $this->assertGreaterThan(100, $width);
        $this->assertEqualsWithDelta($width / 2, $height, 1);
    }

    /**
     * @link \Files\Service\Previews::generate()
     * @link \Files\Service\Previews::generates()
     * @return void
     */
    public function testWhatHasNoPictureSaysSoRatherThanFailing(): void
    {
        $file = $this->stored('picture.jpg', 'application/vnd.ms-excel');

        $this->assertFalse(Previews::generates('application/vnd.ms-excel'));
        $this->assertNull((new Previews())->thumbnail($file));
        $this->assertNull((new Previews())->preview($file));
    }

    /**
     * @link \Files\Service\Previews::generate()
     * @return void
     */
    public function testThereAreOnlyTheTwoSizes(): void
    {
        $file = $this->stored('picture.jpg', 'image/jpeg');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a size a preview comes in');

        (new Previews())->generate($file, 'enormous');
    }

    /**
     * @link \Files\Service\Previews::pathFor()
     * @return void
     */
    public function testEachSizeIsKeptApartAndSpreadByTheContentItIsOf(): void
    {
        $thumbnail = Previews::pathFor('abcdef00-1111-2222-3333-444444444444', Previews::THUMBNAIL);
        $preview = Previews::pathFor('abcdef00-1111-2222-3333-444444444444', Previews::PREVIEW);

        $this->assertNotSame($thumbnail, $preview);

        foreach ([$thumbnail, $preview] as $path) {
            $this->assertStringStartsWith($this->previews . DS, $path);
            $this->assertStringEndsWith('abcdef00-1111-2222-3333-444444444444.webp', $path);
            // Two levels of two characters, so that no one directory fills up.
            $this->assertStringContainsString(DS . 'ab' . DS . 'cd' . DS, $path);
        }
    }

    /**
     * Puts one of the files beside this test into the store.
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
     * How large a picture is.
     *
     * @param string $path The picture.
     * @return array{int, int}
     */
    private function sizeOf(string $path): array
    {
        $imagick = new Imagick($path);

        try {
            $this->assertSame('WEBP', $imagick->getImageFormat());

            return [$imagick->getImageWidth(), $imagick->getImageHeight()];
        } finally {
            $imagick->clear();
        }
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
