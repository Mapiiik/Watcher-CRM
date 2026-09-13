<?php
declare(strict_types=1);

namespace Files\Test\TestCase\Model\Table;

use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use Files\Model\Entity\Documentation;
use Files\Model\Entity\DocumentationType;
use Files\Model\Table\DocumentationsTable;
use Files\Model\Table\DocumentationTypesTable;
use Files\Model\Table\FileLinksTable;
use Files\Service\Documentations;
use Files\Service\FileStorage;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Files\Model\Table\DocumentationsTable Test Case
 *
 * A folder is either something that happened, which carries the day it happened on, or a standing
 * place kept up to date, which carries a name instead. Most of what is asked of it here is that
 * those two stay apart and that neither can end up being neither.
 */
#[UsesClass(DocumentationsTable::class)]
#[UsesClass(Documentation::class)]
class DocumentationsTableTest extends TestCase
{
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
    ];

    /**
     * @var \Files\Model\Table\DocumentationsTable
     */
    private DocumentationsTable $Documentations;

    /**
     * @var \Files\Model\Table\DocumentationTypesTable
     */
    private DocumentationTypesTable $DocumentationTypes;

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

        // Under the plugin's own name, not the bare one. An application puts its own table under
        // the bare alias, and whichever of the two a run reaches first is the one the rest of it
        // gets.
        /** @var \Files\Model\Table\DocumentationsTable $documentations */
        $documentations = $this->fetchTable('Files.Documentations');
        $this->Documentations = $documentations;

        /** @var \Files\Model\Table\DocumentationTypesTable $types */
        $types = $this->fetchTable('Files.DocumentationTypes');
        $this->DocumentationTypes = $types;

        $this->root = TMP . 'documentations-store-' . uniqid();
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

        parent::tearDown();
    }

    /**
     * @link \Files\Model\Table\DocumentationsTable::buildRules()
     * @return void
     */
    public function testAFolderWithNeitherANameNorADayIsNotFiled(): void
    {
        $nothing = $this->folder($this->kind(), []);

        $this->assertFalse($this->Documentations->save($nothing));
        $this->assertArrayHasKey('name', $nothing->getErrors());
    }

    /**
     * @link \Files\Model\Table\DocumentationsTable::buildRules()
     * @return void
     */
    public function testEitherOfThemOnItsOwnIsEnough(): void
    {
        $kind = $this->kind();

        $named = $this->folder($kind, ['name' => 'The wiring in the rack']);
        $dated = $this->folder($kind, ['happened_on' => new Date('2026-03-12')]);

        $this->assertNotFalse($this->Documentations->save($named));
        $this->assertNotFalse($this->Documentations->save($dated));
    }

    /**
     * @link \Files\Model\Table\DocumentationsTable::buildRules()
     * @return void
     */
    public function testAKindThatAsksForTheDayIsGivenIt(): void
    {
        $kind = $this->kind(['name' => 'Installation', 'date_required' => true]);

        $named = $this->folder($kind, ['name' => 'The one in March']);

        $this->assertFalse($this->Documentations->save($named));
        $this->assertArrayHasKey('happened_on', $named->getErrors());

        $named->set('happened_on', new Date('2026-03-12'));
        $this->assertNotFalse($this->Documentations->save($named));
    }

    /**
     * Asking for the day from now on is not the same as saying that what is already filed was
     * wrong, and a folder from before goes on reading exactly as it did.
     *
     * @link \Files\Model\Table\DocumentationsTable::buildRules()
     * @return void
     */
    public function testAskingForTheDayLaterLeavesWhatIsFiledAlone(): void
    {
        $kind = $this->kind();
        $filed = $this->Documentations->saveOrFail(
            $this->folder($kind, ['name' => 'The wiring in the rack']),
        );

        $kind->set('date_required', true);
        $this->DocumentationTypes->saveOrFail($kind);

        $again = $this->Documentations->get($filed->id);
        $this->assertSame('The wiring in the rack', $again->name);
    }

    /**
     * A folder that is kept up to date is the one somebody wants first, and the rest read newest
     * downwards under it.
     *
     * @link \Files\Model\Table\DocumentationsTable::findInReadingOrder()
     * @return void
     */
    public function testTheStandingFolderReadsAboveTheHistory(): void
    {
        $kind = $this->kind();

        $this->Documentations->saveOrFail($this->folder($kind, ['happened_on' => new Date('2026-03-12')]));
        $this->Documentations->saveOrFail($this->folder($kind, ['name' => 'The wiring in the rack']));
        $this->Documentations->saveOrFail($this->folder($kind, ['happened_on' => new Date('2026-07-01')]));

        /** @var list<\Files\Model\Entity\Documentation> $read */
        $read = $this->Documentations->find('inReadingOrder')->contain(['DocumentationTypes'])->all()->toList();

        // The days rather than the headings: a heading carries a date written the way the
        // application writes dates, and which way that is has nothing to do with the order.
        $this->assertSame(
            [null, '2026-07-01', '2026-03-12'],
            array_map(
                fn(Documentation $one): ?string => $one->happened_on?->format('Y-m-d'),
                $read,
            ),
        );
        $this->assertSame('The wiring in the rack', $read[0]->heading);
    }

    /**
     * What a folder holds goes with it. Anything else leaves files nothing points at and nobody
     * can reach, which is the one thing the store cannot put right by itself.
     *
     * @link \Files\Model\Table\DocumentationsTable::beforeDelete()
     * @return void
     */
    public function testAFolderLetsGoOfWhatItHoldsWhenItGoes(): void
    {
        $folder = $this->Documentations->saveOrFail(
            $this->folder($this->kind(), ['name' => 'The wiring in the rack']),
        );

        $storage = new FileStorage();
        $file = $storage->store('a drawing of the rack', 'text/plain');
        $storage->link(
            $file,
            Documentations::MODEL,
            (string)$folder->id,
            Documentations::ATTACHMENT,
            Documentations::UPLOADED,
        );

        $this->assertCount(1, (new Documentations())->contentsOf($folder));

        $this->Documentations->deleteOrFail($folder);

        /** @var \Files\Model\Table\FileLinksTable $links */
        $links = $this->fetchTable(FileLinksTable::class);

        $this->assertSame(
            0,
            $links->find()->where(['FileLinks.foreign_key' => $folder->id])->count(),
            'Nothing should still be pointing at a folder that has gone.',
        );
        $this->assertFalse($storage->has($file), 'The bytes should have gone with the last link to them.');
    }

    /**
     * A kind of folder, saved.
     *
     * @param array<string, mixed> $said What to say about it.
     * @return \Files\Model\Entity\DocumentationType
     */
    private function kind(array $said = []): DocumentationType
    {
        return $this->DocumentationTypes->saveOrFail($this->DocumentationTypes->newEntity($said + [
            'name' => 'Documentation',
            'position' => 0,
            'currently_offered' => true,
            'date_required' => false,
        ]));
    }

    /**
     * A folder of that kind, not saved.
     *
     * @param \Files\Model\Entity\DocumentationType $kind What kind it is.
     * @param array<string, mixed> $said What to say about it.
     * @return \Files\Model\Entity\Documentation
     */
    private function folder(DocumentationType $kind, array $said): Documentation
    {
        $folder = $this->Documentations->newEntity($said + ['documentation_type_id' => $kind->id]);
        $folder->set('documentation_type', $kind);

        return $folder;
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
