<?php
declare(strict_types=1);

namespace Files\Test\TestCase\Service;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Files\Model\Table\FileLinksTable;
use Files\Service\FiledPages;
use Laminas\Diactoros\UploadedFile;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use RuntimeException;

/**
 * Files\Service\FiledPages Test Case
 *
 * The mechanics on their own, without an agenda's vocabulary around them: what may be filed, what
 * order it ends up in, and what happens to the order when one page goes or moves.
 */
#[UsesClass(FiledPages::class)]
class FiledPagesTest extends TestCase
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
     * The record the pages are filed against. Any four words will do - what they mean belongs to
     * whoever is filing.
     *
     * @var string
     */
    private const MODEL = 'Records';
    private const RECORD = 'e0a8a2c6-6e33-4b5f-9a1e-1f2d3c4b5a60';
    private const DOCUMENT = 'attachment';
    private const VARIANT = 'filed';

    /**
     * Where the bytes go, and where the things being uploaded are written first.
     *
     * @var string
     */
    private string $root;
    private string $outside;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->root = TMP . 'filed-pages-store-' . uniqid();
        $this->outside = TMP . 'filed-pages-sent-' . uniqid();
        mkdir($this->outside, 0777, true);

        Configure::write('Files.root', $this->root);
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
        $this->removeDirectory($this->outside);

        parent::tearDown();
    }

    /**
     * Documentation is whatever the work produced - a drawing, an archive, a recording - and the
     * caller that files it names no list at all.
     *
     * @link \Files\Service\FiledPages::take()
     * @return void
     */
    public function testAnythingIsTakenWhenNobodySaysOtherwise(): void
    {
        $filed = (new FiledPages())->take(
            self::MODEL,
            self::RECORD,
            self::DOCUMENT,
            self::VARIANT,
            [$this->upload('wiring.txt', 'rack 3, port 12')],
        );

        $this->assertSame(1, $filed);
        $this->assertSame(['wiring.txt'], $this->namesOnFile());
    }

    /**
     * @link \Files\Service\FiledPages::take()
     * @return void
     */
    public function testOnlyWhatTheCallerNamedIsTaken(): void
    {
        $pages = new FiledPages(null, ['image/jpeg']);

        $this->expectException(RuntimeException::class);

        try {
            $pages->take(
                self::MODEL,
                self::RECORD,
                self::DOCUMENT,
                self::VARIANT,
                [$this->upload('wiring.txt', 'rack 3, port 12')],
            );
        } finally {
            $this->assertSame([], $this->namesOnFile(), 'Nothing should have been filed.');
        }
    }

    /**
     * @link \Files\Service\FiledPages::take()
     * @return void
     */
    public function testPagesKeepTheOrderTheyArrivedIn(): void
    {
        $this->file(['one.txt', 'two.txt', 'three.txt']);

        $this->assertSame([0, 1, 2], $this->positionsOnFile());
        $this->assertSame(['one.txt', 'two.txt', 'three.txt'], $this->namesOnFile());
    }

    /**
     * A slot nobody filled is the ordinary case on a form offering several documents at once, so
     * it is passed over rather than complained about.
     *
     * @link \Files\Service\FiledPages::take()
     * @return void
     */
    public function testASlotNobodyFilledIsNotARefusal(): void
    {
        $filed = (new FiledPages())->take(
            self::MODEL,
            self::RECORD,
            self::DOCUMENT,
            self::VARIANT,
            [
                new UploadedFile($this->written('nothing.txt', ''), 0, UPLOAD_ERR_NO_FILE, '', null),
                $this->upload('one.txt', 'the first page'),
            ],
        );

        $this->assertSame(1, $filed);
        $this->assertSame(['one.txt'], $this->namesOnFile());
    }

    /**
     * @link \Files\Service\FiledPages::drop()
     * @return void
     */
    public function testLettingGoOfOneClosesTheGap(): void
    {
        $this->file(['one.txt', 'two.txt', 'three.txt']);

        (new FiledPages())->drop($this->onFile()[1]);

        $this->assertSame(['one.txt', 'three.txt'], $this->namesOnFile());
        $this->assertSame([0, 1], $this->positionsOnFile());
    }

    /**
     * @link \Files\Service\FiledPages::move()
     * @return void
     */
    public function testAPageGoesPastTheOneBesideIt(): void
    {
        $this->file(['one.txt', 'two.txt', 'three.txt']);

        (new FiledPages())->move($this->onFile()[2], true);

        $this->assertSame(['one.txt', 'three.txt', 'two.txt'], $this->namesOnFile());
        $this->assertSame([0, 1, 2], $this->positionsOnFile());
    }

    /**
     * The one at the top has nothing to go before, and asking is not an error.
     *
     * @link \Files\Service\FiledPages::move()
     * @return void
     */
    public function testTheFirstPageStaysWhereItIs(): void
    {
        $this->file(['one.txt', 'two.txt']);

        (new FiledPages())->move($this->onFile()[0], true);

        $this->assertSame(['one.txt', 'two.txt'], $this->namesOnFile());
    }

    /**
     * How large a request may be is the worse of the two limits to reach, and the one the server
     * cannot report afterwards: past it there is no request left to report anything about.
     *
     * @link \Files\Service\FiledPages::bytesOf()
     * @return void
     */
    public function testASizeIsReadTheWayTheConfigurationWritesOne(): void
    {
        $this->assertSame(268435456, FiledPages::bytesOf('256M'));
        $this->assertSame(2147483648, FiledPages::bytesOf('2G'));
        $this->assertSame(1024, FiledPages::bytesOf('1K'));
        $this->assertSame(4096, FiledPages::bytesOf('4096'));
        $this->assertSame(268435456, FiledPages::bytesOf(' 256M '));

        // Nothing at all, and the two ways of turning the limit off.
        $this->assertSame(0, FiledPages::bytesOf(''));
        $this->assertSame(0, FiledPages::bytesOf('0'));
        $this->assertSame(0, FiledPages::bytesOf('-1'));
    }

    /**
     * Files a page for each name, each with contents of its own so that the store keeps them
     * apart rather than recognising one it already has.
     *
     * @param list<string> $names What to call them.
     * @return void
     */
    private function file(array $names): void
    {
        $uploads = [];
        foreach ($names as $name) {
            $uploads[] = $this->upload($name, 'the contents of ' . $name);
        }

        (new FiledPages())->take(self::MODEL, self::RECORD, self::DOCUMENT, self::VARIANT, $uploads);
    }

    /**
     * Something arriving from a browser.
     *
     * @param string $name What it is called where it came from.
     * @param string $contents What is in it.
     * @return \Laminas\Diactoros\UploadedFile
     */
    private function upload(string $name, string $contents): UploadedFile
    {
        $path = $this->written($name, $contents);

        return new UploadedFile($path, (int)filesize($path), UPLOAD_ERR_OK, $name, null);
    }

    /**
     * @param string $name What to call it.
     * @param string $contents What to put in it.
     * @return string Where it was written.
     */
    private function written(string $name, string $contents): string
    {
        $path = $this->outside . DS . $name;
        file_put_contents($path, $contents);

        return $path;
    }

    /**
     * What is filed against the record, in the order it reads.
     *
     * @return list<\Files\Model\Entity\FileLink>
     */
    private function onFile(): array
    {
        /** @var \Files\Model\Table\FileLinksTable $links */
        $links = $this->fetchTable(FileLinksTable::class);

        /** @var list<\Files\Model\Entity\FileLink> $found */
        $found = $links->find(
            'group',
            model: self::MODEL,
            foreign_key: self::RECORD,
            document_type: self::DOCUMENT,
            variant: self::VARIANT,
        )->all()->toList();

        return $found;
    }

    /**
     * @return list<string>
     */
    private function namesOnFile(): array
    {
        return array_map(fn($link): string => (string)$link->name, $this->onFile());
    }

    /**
     * @return list<int>
     */
    private function positionsOnFile(): array
    {
        return array_map(fn($link): int => $link->position, $this->onFile());
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
