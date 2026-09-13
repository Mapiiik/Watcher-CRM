<?php
declare(strict_types=1);

namespace Files\Test\TestCase\View\Cell;

use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Files\Model\Entity\Documentation;
use Files\Model\Table\DocumentationsTable;
use Files\Service\Documentations;
use Files\Service\FileStorage;
use Files\View\Cell\DocumentationsCell;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Files\View\Cell\DocumentationsCell Test Case
 *
 * What a folder shows of itself. The one thing worth holding it to is that nothing in it goes
 * missing from the wall: a drawing or an archive has no picture and must still be reachable.
 */
#[UsesClass(DocumentationsCell::class)]
class DocumentationsCellTest extends TestCase
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

        // A cell drawn outside a request has no routes at all, and it needs both sets: the
        // application's, which the folder links to, and the plugin's, which the tiles do. In
        // that order, because the application's are only read while nothing is connected yet.
        $this->loadRoutes();
        $this->loadPlugins(['Files' => ['routes' => true]]);

        /** @var \Files\Model\Table\DocumentationsTable $documentations */
        $documentations = $this->fetchTable('Files.Documentations');
        $this->Documentations = $documentations;

        $this->root = TMP . 'documentations-cell-' . uniqid();
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
     * The wall says what the viewer is to show.
     *
     * Nothing else on such a page names the group, so if the tiles do not carry the pages the
     * viewer is asked to open a gallery it knows nothing about, and nothing happens.
     *
     * @link \Files\View\Cell\DocumentationsCell::contents()
     * @return void
     */
    public function testTheWallTellsTheViewerWhatToShow(): void
    {
        $folder = $this->folder();
        $this->put($folder, 'roof.jpg', 'image/jpeg', (string)file_get_contents($this->content('picture.jpg')));
        $this->put($folder, 'mast.jpg', 'image/jpeg', (string)file_get_contents($this->content('picture.jpg')));

        $shown = $this->render('contents', [$folder]);

        $this->assertSame(
            1,
            substr_count($shown, 'data-files-pages'),
            'Once for the group, not once for every tile in it.',
        );

        // Read back rather than looked for: the pages are json inside an attribute, and unescaped
        // they close it on their own first quote - which leaves the attribute there and useless.
        $this->assertMatchesRegularExpression('~data-files-pages="([^"]+)"~', $shown);
        preg_match('~data-files-pages="([^"]+)"~', $shown, $found);
        $pages = json_decode(html_entity_decode($found[1] ?? '', ENT_QUOTES), true);

        $this->assertIsArray($pages, 'What the viewer is handed has to be readable json.');
        $this->assertCount(2, $pages, 'Both pictures are pages of the group.');
    }

    /**
     * Everything is in the table, and only what can be drawn is in the wall under it.
     *
     * A drawing standing in that wall left a hole in it and a name too small to read, so the
     * wall is for looking at and the table is for working with.
     *
     * @link \Files\View\Cell\DocumentationsCell::contents()
     * @return void
     */
    public function testWhatCanBeDrawnIsDrawnAndEverythingIsListed(): void
    {
        $folder = $this->folder();
        $this->put($folder, 'roof.jpg', 'image/jpeg', (string)file_get_contents($this->content('picture.jpg')));
        $this->put($folder, 'rack.dwg', 'application/octet-stream', 'a drawing of the rack');

        $shown = $this->render('contents', [$folder]);
        $wall = substr($shown, (int)strpos($shown, 'files-tiles'));

        $this->assertStringContainsString('roof.jpg', $shown, 'The photograph should be listed.');
        $this->assertStringContainsString('rack.dwg', $shown, 'The drawing should be listed.');
        $this->assertStringContainsString('files-thumb', $wall, 'The photograph should show itself.');
        $this->assertStringNotContainsString(
            'rack.dwg',
            $wall,
            'The drawing has nothing to show and has no place in the wall.',
        );
    }

    /**
     * @link \Files\View\Cell\DocumentationsCell::contents()
     * @return void
     */
    public function testAnEmptyFolderSaysSoRatherThanShowingNothing(): void
    {
        $this->assertStringContainsString(
            'Nothing is filed in this documentation yet.',
            $this->render('contents', [$this->folder()]),
        );
    }

    /**
     * @link \Files\View\Cell\DocumentationsCell::display()
     * @return void
     */
    public function testTheListingSaysHowMuchEachFolderHolds(): void
    {
        $folder = $this->folder(['name' => 'The wiring in the rack']);
        $this->put($folder, 'rack.dwg', 'application/octet-stream', 'a drawing of the rack');

        $listing = $this->render('display', [[$folder]]);

        $this->assertStringContainsString('The wiring in the rack', $listing);
        $this->assertStringContainsString('1 file', $listing);
    }

    /**
     * @link \Files\View\Cell\DocumentationsCell::display()
     * @return void
     */
    public function testARecordWithNoFoldersSaysSo(): void
    {
        $this->assertStringContainsString(
            'Nothing is filed here yet.',
            $this->render('display', [[]]),
        );
    }

    /**
     * The cell, drawn.
     *
     * @param string $action Which of the two.
     * @param array<mixed> $arguments What to draw.
     * @return string
     */
    private function render(string $action, array $arguments): string
    {
        return (string)(new View())->cell('Files.Documentations::' . $action, $arguments)->render();
    }

    /**
     * A folder, saved.
     *
     * @param array<string, mixed> $said What to say about it.
     * @return \Files\Model\Entity\Documentation
     */
    private function folder(array $said = []): Documentation
    {
        /** @var \Files\Model\Table\DocumentationTypesTable $types */
        $types = $this->fetchTable('Files.DocumentationTypes');

        $kind = $types->saveOrFail($types->newEntity([
            'name' => 'Documentation',
            'position' => 0,
            'currently_offered' => true,
            'date_required' => false,
        ]));

        $folder = $this->Documentations->saveOrFail($this->Documentations->newEntity($said + [
            'documentation_type_id' => $kind->id,
            'happened_on' => new Date('2026-03-12'),
        ]));
        $folder->set('documentation_type', $kind);

        return $folder;
    }

    /**
     * Puts something in the folder.
     *
     * @param \Files\Model\Entity\Documentation $folder Which folder.
     * @param string $name What it is called.
     * @param string $mime_type What kind of thing it is.
     * @param string $contents What is in it.
     * @return void
     */
    private function put(Documentation $folder, string $name, string $mime_type, string $contents): void
    {
        $storage = new FileStorage();

        $storage->link(
            $storage->store($contents, $mime_type),
            Documentations::MODEL,
            (string)$folder->id,
            Documentations::ATTACHMENT,
            Documentations::UPLOADED,
            ['name' => $name],
        );
    }

    /**
     * One of the files beside the service's test.
     *
     * @param string $name Which one.
     * @return string
     */
    private function content(string $name): string
    {
        return dirname(__DIR__, 3) . DS . 'content' . DS . $name;
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
