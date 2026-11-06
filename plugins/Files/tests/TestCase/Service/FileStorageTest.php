<?php
declare(strict_types=1);

namespace Files\Test\TestCase\Service;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Files\Model\Table\FileLinksTable;
use Files\Model\Table\FilesTable;
use Files\Service\FileStorage;
use Files\Service\Previews;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Files\Service\FileStorage Test Case
 *
 * What is asked of the store is the promise the whole thing rests on: that the same content is
 * kept once however many records want it, and that letting go of one of them does not take the
 * bytes away from the others.
 */
#[CoversClass(FileStorage::class)]
class FileStorageTest extends TestCase
{
    use LocatorAwareTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
        'plugin.Files.RemovedFileLinks',
    ];

    /**
     * A record for the links to hang on. Nothing reads it - the store takes the model and the
     * key as the words they are.
     *
     * @var string
     */
    private const RECORD = '11111111-2222-4333-8444-555555555555';
    private const OTHER_RECORD = '66666666-7777-4888-9999-000000000000';

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

        $this->root = TMP . 'file-storage-' . uniqid();
        $this->previews = TMP . 'file-storage-previews-' . uniqid();
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
     * @link \Files\Service\FileStorage::store()
     * @return void
     */
    public function testContentIsWrittenWhereItsHashSaysItGoes(): void
    {
        $storage = new FileStorage();
        $file = $storage->store('a document', 'application/pdf');

        $this->assertSame(hash(FilesTable::HASH_ALGORITHM, 'a document'), $file->hash);
        $this->assertSame(strlen('a document'), $file->byte_size);
        $this->assertSame('application/pdf', $file->mime_type);
        $this->assertSame(FileStorage::pathFor($file->hash), $file->path);
        $this->assertTrue($storage->has($file));
        $this->assertSame('a document', $storage->read($file));
    }

    /**
     * The promise the whole store rests on.
     *
     * @link \Files\Service\FileStorage::store()
     * @return void
     */
    public function testTheSameContentTwiceIsKeptOnceAndPointedAtTwice(): void
    {
        $storage = new FileStorage();

        $first = $storage->store('the same paper', 'application/pdf');
        $second = $storage->store('the same paper', 'application/pdf');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $this->fetchTable('Files.Files')->find()->count());

        $storage->link($first, 'Contracts', self::RECORD, 'contract-new', 'generated');
        $storage->link($second, 'Contracts', self::OTHER_RECORD, 'contract-new', 'generated');

        $this->assertSame(2, $this->fetchTable('Files.FileLinks')->find()->count());
        $this->assertSame(1, $this->fetchTable('Files.Files')->find()->count());
    }

    /**
     * @link \Files\Service\FileStorage::storeFile()
     * @return void
     */
    public function testAFileIsTakenWithoutBeingReadWhole(): void
    {
        $storage = new FileStorage();

        $incoming = TMP . 'incoming-' . uniqid() . '.jpg';
        file_put_contents($incoming, 'a scanned page');

        try {
            $file = $storage->storeFile($incoming, 'image/jpeg');
        } finally {
            unlink($incoming);
        }

        $this->assertSame(hash(FilesTable::HASH_ALGORITHM, 'a scanned page'), $file->hash);
        $this->assertSame('a scanned page', $storage->read($file));
    }

    /**
     * @link \Files\Service\FileStorage::unlink()
     * @return void
     */
    public function testRemovingAUseMovesItToTheTrashAndKeepsTheBytes(): void
    {
        $storage = new FileStorage();
        $file = $storage->store('a paper nobody else wants', 'application/pdf');
        $link = $storage->link($file, 'Contracts', self::RECORD, 'contract-new', 'generated', ['name' => 'a.pdf']);

        $storage->unlink($link);

        $this->assertSame(0, $this->fetchTable('Files.FileLinks')->find()->count());

        /** @var \Files\Model\Entity\RemovedFileLink $removed */
        $removed = $this->fetchTable('Files.RemovedFileLinks')->get($link->id);

        $this->assertSame($file->id, $removed->file_id);
        $this->assertSame('contract-new', $removed->document_type);
        $this->assertSame('a.pdf', $removed->name);
        $this->assertNotNull($removed->removed);
        $this->assertNull($removed->documentation);

        $this->assertSame(1, $this->fetchTable('Files.Files')->find()->count());
        $this->assertTrue($storage->has($file), 'The bytes should wait in the trash with the link.');
    }

    /**
     * The row in the trash carries the id of the link, so one left behind by a failed attempt
     * would stand in the way of every attempt after it.
     *
     * @link \Files\Service\FileStorage::unlink()
     * @return void
     */
    public function testALinkThatCannotGoLeavesNothingInTheTrash(): void
    {
        $storage = new FileStorage();
        $file = $storage->store('a paper that refuses to go', 'application/pdf');
        $link = $storage->link($file, 'Contracts', self::RECORD, 'contract-new', 'generated');

        $links = $this->fetchTable(FileLinksTable::class);
        $refuse = function (EventInterface $event): void {
            $event->setResult(false);
            $event->stopPropagation();
        };
        $links->getEventManager()->on('Model.beforeDelete', $refuse);

        try {
            $storage->unlink($link);
            $this->fail('A link that cannot go should have been said out loud.');
        } catch (PersistenceFailedException) {
            $this->assertSame(0, $this->fetchTable('Files.RemovedFileLinks')->find()->count());
            $this->assertSame(1, $links->find()->count());
        } finally {
            $links->getEventManager()->off('Model.beforeDelete', $refuse);
        }

        $storage->unlink($link);

        $this->assertSame(1, $this->fetchTable('Files.RemovedFileLinks')->find()->count());
    }

    /**
     * Nothing written inside a transaction reaches the log by itself.
     *
     * @link \Files\Service\FileStorage::unlink()
     * @return void
     */
    public function testRemovingReachesTheAuditLog(): void
    {
        $storage = new FileStorage();
        $file = $storage->store('a paper somebody will ask about', 'application/pdf');
        $link = $storage->link($file, 'Contracts', self::RECORD, 'contract-new', 'generated');

        $connection = $this->fetchTable(FileLinksTable::class)->getConnection();
        $logged = fn(): int => (int)$connection->selectQuery(['c' => 'count(*)'], 'audit_logs')
            ->execute()
            ->fetchAll('assoc')[0]['c'];

        $before = $logged();

        $storage->unlink($link);

        $this->assertSame($before + 2, $logged(), 'The row leaving and the row arriving are both on record.');
    }

    /**
     * @link \Files\Service\FileStorage::letGoOf()
     * @return void
     */
    public function testTheContentGoesOnlyWhenNothingWantsItAnyMore(): void
    {
        $storage = new FileStorage();
        $file = $storage->store('a paper two records share', 'application/pdf');

        $one = $storage->link($file, 'Contracts', self::RECORD, 'contract-new', 'generated');
        $two = $storage->link($file, 'Contracts', self::OTHER_RECORD, 'contract-new', 'generated');

        $storage->unlink($one);

        $this->assertFalse($storage->letGoOf($file->id), 'A link still wants it.');

        $storage->unlink($two);

        $this->assertFalse($storage->letGoOf($file->id), 'The trash still wants it.');
        $this->assertTrue($storage->has($file));

        $this->fetchTable('Files.RemovedFileLinks')->deleteAll([]);

        $this->assertTrue($storage->letGoOf($file->id));
        $this->assertSame(0, $this->fetchTable('Files.Files')->find()->count());
        $this->assertFalse($storage->has($file));
    }

    /**
     * Otherwise a picture of a page somebody wanted gone for good stays on the disk.
     *
     * @link \Files\Service\FileStorage::letGoOf()
     * @return void
     */
    public function testThePicturesGoWithTheContent(): void
    {
        $storage = new FileStorage();
        $file = $storage->store('a page with a picture of it', 'image/jpeg');

        $pictures = [
            Previews::pathFor($file->id, Previews::THUMBNAIL),
            Previews::pathFor($file->id, Previews::PREVIEW),
        ];

        foreach ($pictures as $picture) {
            mkdir(dirname($picture), 0777, true);
            file_put_contents($picture, 'a picture');
        }

        $this->assertTrue($storage->letGoOf($file->id));

        foreach ($pictures as $picture) {
            $this->assertFileDoesNotExist($picture);
        }
    }

    /**
     * Pages of a scan arrive one after another and each goes after the last.
     *
     * @link \Files\Service\FileStorage::link()
     * @return void
     */
    public function testPagesFallInAfterOneAnother(): void
    {
        $storage = new FileStorage();

        $positions = [];
        foreach (['first page', 'second page', 'third page'] as $page) {
            $file = $storage->store($page, 'image/jpeg');
            $positions[] = $storage->link(
                $file,
                'Contracts',
                self::RECORD,
                'contract-new',
                'received-signed-by-customer',
            )->position;
        }

        $this->assertSame([0, 1, 2], $positions);
    }

    /**
     * Freezing, as the database sees it.
     *
     * @link \Files\Service\FileStorage::link()
     * @return void
     */
    public function testARecordHasOneGeneratedDocumentOfEachKindButAnyNumberOfPages(): void
    {
        $storage = new FileStorage();
        $first = $storage->store('what we printed', 'application/pdf');
        $second = $storage->store('what we printed again', 'application/pdf');

        $storage->link($first, 'Contracts', self::RECORD, 'contract-new', 'generated');

        $this->expectExceptionMessageMatches('/could not be linked|duplicate key/i');
        $storage->link($second, 'Contracts', self::RECORD, 'contract-new', 'generated');
    }

    /**
     * Two pages may share a position, which is what lets them be reordered by plain updates
     * rather than by shuffling around a constraint.
     *
     * @link \Files\Service\FileStorage::link()
     * @return void
     */
    public function testTwoPagesMayShareAPositionWhileTheyAreBeingReordered(): void
    {
        $storage = new FileStorage();
        $first = $storage->store('page one', 'image/jpeg');
        $second = $storage->store('page two', 'image/jpeg');

        $variant = 'received-signed-by-customer';
        $storage->link($first, 'Contracts', self::RECORD, 'contract-new', $variant, ['position' => 0]);
        $storage->link($second, 'Contracts', self::RECORD, 'contract-new', $variant, ['position' => 0]);

        $this->assertSame(2, $this->fetchTable('Files.FileLinks')->find()->count());
    }

    /**
     * @link \Files\Service\FileStorage::pathFor()
     * @return void
     */
    public function testThePathIsSpreadOutSoNoOneDirectoryFillsUp(): void
    {
        $hash = hash(FilesTable::HASH_ALGORITHM, 'anything');

        $this->assertSame(
            substr($hash, 0, 2) . '/' . substr($hash, 2, 2) . '/' . $hash,
            FileStorage::pathFor($hash),
        );
    }

    /**
     * A restore taken of the wrong moment, or a hand in the wrong directory, leaves a row whose
     * bytes are gone. Storing the same content again is how that is put right.
     *
     * @link \Files\Service\FileStorage::store()
     * @return void
     */
    public function testContentThatWentMissingUnderItsRowIsWrittenAgain(): void
    {
        $storage = new FileStorage();
        $file = $storage->store('a paper that went missing', 'application/pdf');

        $storage->filesystem()->delete($file->path);
        $this->assertFalse($storage->has($file));

        $again = $storage->store('a paper that went missing', 'application/pdf');

        $this->assertSame($file->id, $again->id);
        $this->assertTrue($storage->has($again));
    }

    /**
     * @link \Files\Service\FileStorage::root()
     * @return void
     */
    public function testTheStoreSitsUnderTheDataRootWhenNothingSaysOtherwise(): void
    {
        Configure::delete('Files.root');

        $this->assertSame(
            (string)Configure::read('Data.root') . DS . 'files',
            FileStorage::root(),
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
