<?php
declare(strict_types=1);

namespace Files\Test\TestCase\Service;

use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Files\Model\Entity\Documentation;
use Files\Model\Entity\DocumentationType;
use Files\Model\Entity\FileLink;
use Files\Model\Entity\RemovedFileLink;
use Files\Model\Table\DocumentationsTable;
use Files\Model\Table\DocumentationTypesTable;
use Files\Model\Table\FileLinksTable;
use Files\Model\Table\RemovedFileLinksTable;
use Files\Service\Documentations;
use Files\Service\FileStorage;
use Files\Service\Previews;
use Files\Service\Trash;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

/**
 * Files\Service\Trash Test Case
 *
 * What is asked here is that a mistake can be undone without making a new one: what comes back
 * comes back whole, and never over something that has taken its place since.
 *
 * The clock stands still under the tests, so whatever is meant as two acts is handed two moments.
 */
#[CoversClass(Trash::class)]
class TrashTest extends TestCase
{
    use LocatorAwareTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Files.DocumentationTypes',
        'plugin.Files.Documentations',
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
        'plugin.Files.RemovedFileLinks',
    ];

    /**
     * Where the bytes and the pictures go while this runs.
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
     * @var \Files\Service\FileStorage
     */
    private FileStorage $storage;

    /**
     * @var \Files\Service\Trash
     */
    private Trash $trash;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->root = TMP . 'trash-' . uniqid();
        $this->previews = TMP . 'trash-previews-' . uniqid();
        $this->records = Configure::read('Files.records');

        Configure::write('Files.root', $this->root);
        Configure::write('Files.previews', $this->previews);
        Configure::write('Files.records', [
            Documentations::MODEL => ['plugin' => null, 'controller' => 'Documentations', 'action' => 'view'],
        ]);

        $this->storage = new FileStorage();
        $this->trash = new Trash($this->storage);
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
     * The place it left may have been filled since, so it goes after whatever is there now.
     *
     * @link \Files\Service\Trash::restore()
     * @return void
     */
    public function testAPageComesBackToTheEndOfItsGroup(): void
    {
        $folder = (string)$this->folder()->id;

        $first = $this->filed('the first page', $folder, 'received');
        $second = $this->filed('the second page', $folder, 'received');
        $this->filed('the third page', $folder, 'received');

        $this->storage->unlink($first, new DateTime('2026-11-01 10:00:00'));

        $this->assertSame(1, $this->trash->restore($this->removed($first)));

        /** @var \Files\Model\Entity\FileLink $restored */
        $restored = $this->fetchTable(FileLinksTable::class)->get($first->id);

        $this->assertSame(3, $restored->position);
        $this->assertSame($first->file_id, $restored->file_id);
        $this->assertSame(1, $second->position);
        $this->assertSame(0, $this->fetchTable(RemovedFileLinksTable::class)->find()->count());
    }

    /**
     * The copy carrying our signature is the same document stamped, so the two leave together and
     * return together. Another generation of the same document stays where it is.
     *
     * @link \Files\Service\Trash::restore()
     * @return void
     */
    public function testWhatWentTogetherComesBackWithoutAnotherGeneration(): void
    {
        $folder = (string)$this->folder()->id;

        // To the microsecond, which is what the moment of a real act comes to.
        $at = new DateTime('2026-11-01 10:00:00.123456');
        $older = $this->filed('the first print', $folder, 'generated');
        $olderSigned = $this->filed('the first print, stamped', $folder, 'generated-signed-by-us');
        $this->storage->unlink($older, $at);
        $this->storage->unlink($olderSigned, $at);

        $later = new DateTime('2026-11-02 10:00:00');
        $newer = $this->filed('the second print', $folder, 'generated');
        $newerSigned = $this->filed('the second print, stamped', $folder, 'generated-signed-by-us');
        $this->storage->unlink($newer, $later);
        $this->storage->unlink($newerSigned, $later);

        $this->assertSame(2, $this->trash->restore($this->removed($older)));

        /** @var list<string> $back */
        $back = $this->fetchTable(FileLinksTable::class)->find()->all()->extract('id')->toList();
        sort($back);
        $expected = [$older->id, $olderSigned->id];
        sort($expected);

        $this->assertSame($expected, $back);
        $this->assertSame(2, $this->fetchTable(RemovedFileLinksTable::class)->find()->count());
    }

    /**
     * Nothing written inside a transaction reaches the log by itself.
     *
     * @link \Files\Service\Trash::restore()
     * @return void
     */
    public function testRestoringReachesTheAuditLog(): void
    {
        $page = $this->filed('a page', (string)$this->folder()->id, 'received');
        $this->storage->unlink($page);

        $connection = $this->fetchTable(FileLinksTable::class)->getConnection();
        $logged = fn(): int => (int)$connection->selectQuery(['c' => 'count(*)'], 'audit_logs')
            ->execute()
            ->fetchAll('assoc')[0]['c'];

        $before = $logged();

        $this->trash->restore($this->removed($page));

        $this->assertSame($before + 2, $logged(), 'The row leaving and the row arriving are both on record.');
    }

    /**
     * It is asked before anything is offered, and the answer stands when somebody tries anyway.
     *
     * @link \Files\Service\Trash::whatStopsIt()
     * @return void
     */
    public function testADocumentMadeAgainHasNoPlaceToComeBackTo(): void
    {
        $folder = (string)$this->folder()->id;

        $old = $this->filed('the old print', $folder, 'generated');
        $this->storage->unlink($old);
        $this->filed('the new print', $folder, 'generated');

        $removed = $this->removed($old);

        $this->assertStringContainsString('its place is taken', (string)$this->trash->whatStopsIt($removed));

        try {
            $this->trash->restore($removed);
            $this->fail('It should not have come back over the new one.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('its place is taken', $e->getMessage());
        }

        $this->assertSame(1, $this->fetchTable(FileLinksTable::class)->find()->count());
        $this->assertSame(1, $this->fetchTable(RemovedFileLinksTable::class)->find()->count());
    }

    /**
     * A page that came back is only ever added to, so nothing fills its place.
     *
     * @link \Files\Service\Trash::whatStopsIt()
     * @return void
     */
    public function testAScanAlwaysHasAPlace(): void
    {
        $folder = (string)$this->folder()->id;

        $page = $this->filed('a scan', $folder, 'received');
        $this->storage->unlink($page);
        $this->filed('another scan', $folder, 'received');

        $this->assertNull($this->trash->whatStopsIt($this->removed($page)));
    }

    /**
     * Two prints of one document in one act: the check sees a free place for both, the index lets
     * only one in, and neither may stay behind.
     *
     * @link \Files\Service\Trash::restore()
     * @return void
     */
    public function testTheIndexHasTheLastWordAndNothingIsLeftHalfWay(): void
    {
        $folder = (string)$this->folder()->id;
        $at = new DateTime('2026-11-01 10:00:00');

        $one = $this->filed('one print', $folder, 'generated');
        $this->storage->unlink($one, $at);
        $two = $this->filed('another print', $folder, 'generated');
        $this->storage->unlink($two, $at);

        try {
            $this->trash->restore($this->removed($one));
            $this->fail('The second print should have been refused.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('its place is taken', $e->getMessage());
        }

        $this->assertSame(0, $this->fetchTable(FileLinksTable::class)->find()->count());
        $this->assertSame(2, $this->fetchTable(RemovedFileLinksTable::class)->find()->count());
    }

    /**
     * @link \Files\Service\Trash::whatStopsIt()
     * @return void
     */
    public function testARecordThatIsGoneStopsItComingBack(): void
    {
        $folder = $this->folder();
        $page = $this->filed('a page', (string)$folder->id, 'received');
        $this->storage->unlink($page);

        $this->assertNull($this->trash->whatStopsIt($this->removed($page)));

        $this->fetchTable(DocumentationsTable::class)->deleteOrFail($folder);

        $this->assertStringContainsString(
            'no longer exists',
            (string)$this->trash->whatStopsIt($this->removed($page)),
        );
    }

    /**
     * The plugin knows no table but what the application declared.
     *
     * @link \Files\Service\Trash::whatStopsIt()
     * @return void
     */
    public function testAModelNobodyDeclaredStopsItComingBack(): void
    {
        $page = $this->filed('a page', (string)$this->folder()->id, 'received');
        $this->storage->unlink($page);

        Configure::write('Files.records', []);

        $this->assertStringContainsString(
            'no longer exists',
            (string)$this->trash->whatStopsIt($this->removed($page)),
        );
    }

    /**
     * @link \Files\Service\Trash::restore()
     * @return void
     */
    public function testAFolderComesBackUnderItsOwnIdWithWhatItHeld(): void
    {
        $folder = $this->folder(['name' => 'The wiring in the rack', 'note' => 'Taken before the upgrade']);
        $id = (string)$folder->id;

        $one = $this->filed('a photograph', $id, Documentations::UPLOADED, Documentations::ATTACHMENT);
        $two = $this->filed('a drawing', $id, Documentations::UPLOADED, Documentations::ATTACHMENT);

        $this->fetchTable(DocumentationsTable::class)->deleteOrFail($folder);

        $removed = $this->removed($one);

        $this->assertSame('The wiring in the rack', $removed->folderName());
        $this->assertNull($this->trash->whatStopsIt($removed));
        $this->assertSame(2, $this->trash->restore($removed));

        /** @var \Files\Model\Entity\Documentation $back */
        $back = $this->fetchTable(DocumentationsTable::class)->get($id);

        $this->assertSame('The wiring in the rack', $back->name);
        $this->assertSame('Taken before the upgrade', $back->note);
        $this->assertSame((string)$folder->documentation_type_id, (string)$back->documentation_type_id);

        $links = $this->fetchTable(FileLinksTable::class)->find()->where(['foreign_key' => $id])->count();
        $this->assertSame(2, $links);
        $this->assertTrue($this->fetchTable(FileLinksTable::class)->exists(['id' => $two->id]));
    }

    /**
     * What the folder needs may have gone in the meantime, and then nothing comes back at all.
     *
     * @link \Files\Service\Trash::restore()
     * @return void
     */
    public function testAFolderWhoseKindIsGoneStaysInTheTrash(): void
    {
        $kind = $this->kind();
        $folder = $this->folder(['name' => 'The wiring in the rack'], $kind);
        $page = $this->filed(
            'a photograph',
            (string)$folder->id,
            Documentations::UPLOADED,
            Documentations::ATTACHMENT,
        );

        $this->fetchTable(DocumentationsTable::class)->deleteOrFail($folder);
        $this->fetchTable(DocumentationTypesTable::class)->deleteOrFail($kind);

        try {
            $this->trash->restore($this->removed($page));
            $this->fail('A folder cannot come back without its kind.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('no longer exists', $e->getMessage());
        }

        $this->assertSame(1, $this->fetchTable(RemovedFileLinksTable::class)->find()->count());
        $this->assertSame(0, $this->fetchTable(FileLinksTable::class)->find()->count());
    }

    /**
     * @link \Files\Service\Trash::delete()
     * @return void
     */
    public function testDeletingForGoodTakesTheContentAndItsPictures(): void
    {
        $page = $this->filed('a page filed by mistake', (string)$this->folder()->id, 'received');
        $this->storage->unlink($page);

        $picture = Previews::pathFor($page->file_id, Previews::THUMBNAIL);
        mkdir(dirname($picture), 0777, true);
        file_put_contents($picture, 'a picture');

        $this->assertTrue($this->trash->delete($this->removed($page)));

        $this->assertSame(0, $this->fetchTable(RemovedFileLinksTable::class)->find()->count());
        $this->assertSame(0, $this->fetchTable('Files.Files')->find()->count());
        $this->assertFileDoesNotExist($picture);
    }

    /**
     * @link \Files\Service\Trash::delete()
     * @return void
     */
    public function testContentAnotherRecordUsesStays(): void
    {
        $folder = (string)$this->folder()->id;
        $page = $this->filed('a page two records share', $folder, 'received');
        $other = $this->filed('a page two records share', (string)$this->folder()->id, 'received');
        $this->storage->unlink($page);

        $this->assertSame($page->file_id, $other->file_id);
        $this->assertFalse($this->trash->delete($this->removed($page)));

        $this->assertSame(1, $this->fetchTable('Files.Files')->find()->count());
    }

    /**
     * @link \Files\Service\Trash::purge()
     * @return void
     */
    public function testOnlyWhatHasWaitedLongEnoughIsThrownAway(): void
    {
        $folder = (string)$this->folder()->id;

        $expired = $this->filed('removed long ago', $folder, 'received');
        $this->storage->unlink($expired, DateTime::now()->subDays(31));

        $fresh = $this->filed('removed lately', $folder, 'received');
        $this->storage->unlink($fresh, DateTime::now()->subDays(29));

        $this->assertSame(['deleted' => 1, 'failed' => 0], $this->trash->purge(30));

        $removed = $this->fetchTable(RemovedFileLinksTable::class);

        $this->assertFalse($removed->exists(['id' => $expired->id]));
        $this->assertTrue($removed->exists(['id' => $fresh->id]));
        $this->assertSame(1, $this->fetchTable('Files.Files')->find()->count());
    }

    /**
     * Files something against a record.
     *
     * @param string $bytes What is in it.
     * @param string $foreign_key Which record.
     * @param string $variant Which variant.
     * @param string $document_type Which document.
     * @return \Files\Model\Entity\FileLink
     */
    private function filed(
        string $bytes,
        string $foreign_key,
        string $variant,
        string $document_type = 'contract-new',
    ): FileLink {
        return $this->storage->link(
            $this->storage->store($bytes, 'application/pdf'),
            Documentations::MODEL,
            $foreign_key,
            $document_type,
            $variant,
        );
    }

    /**
     * What the trash holds of a link.
     *
     * @param \Files\Model\Entity\FileLink $link The link.
     * @return \Files\Model\Entity\RemovedFileLink
     */
    private function removed(FileLink $link): RemovedFileLink
    {
        /** @var \Files\Model\Entity\RemovedFileLink $removed */
        $removed = $this->fetchTable(RemovedFileLinksTable::class)->get($link->id);

        return $removed;
    }

    /**
     * A folder, saved.
     *
     * @param array<string, mixed> $said What to say about it.
     * @param \Files\Model\Entity\DocumentationType|null $kind What kind it is.
     * @return \Files\Model\Entity\Documentation
     */
    private function folder(array $said = [], ?DocumentationType $kind = null): Documentation
    {
        $kind ??= $this->kind();

        /** @var \Files\Model\Table\DocumentationsTable $folders */
        $folders = $this->fetchTable(DocumentationsTable::class);

        $folder = $folders->newEntity($said + ['name' => 'A folder', 'documentation_type_id' => $kind->id]);
        $folder->set('documentation_type', $kind);

        return $folders->saveOrFail($folder);
    }

    /**
     * A kind of folder, saved.
     *
     * @return \Files\Model\Entity\DocumentationType
     */
    private function kind(): DocumentationType
    {
        /** @var \Files\Model\Table\DocumentationTypesTable $kinds */
        $kinds = $this->fetchTable(DocumentationTypesTable::class);

        return $kinds->saveOrFail($kinds->newEntity([
            'name' => 'Documentation ' . uniqid(),
            'position' => 0,
            'currently_offered' => true,
            'date_required' => false,
        ]));
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
