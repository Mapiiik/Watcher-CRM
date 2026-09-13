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
     * @link \Files\View\Cell\DocumentationsCell::contents()
     * @return void
     */
    public function testWhatCanBeDrawnIsDrawnAndTheRestSaysWhatItIs(): void
    {
        $folder = $this->folder();
        $this->put($folder, 'roof.jpg', 'image/jpeg', (string)file_get_contents($this->content('picture.jpg')));
        $this->put($folder, 'rack.dwg', 'application/octet-stream', 'a drawing of the rack');

        $wall = $this->render('contents', [$folder]);

        $this->assertStringContainsString('files-thumb', $wall, 'The photograph should show itself.');
        $this->assertStringContainsString('>DWG<', $wall, 'The drawing should say what kind of file it is.');
        $this->assertStringContainsString('roof.jpg', $wall);
        $this->assertStringContainsString('rack.dwg', $wall);
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
            Documentations::FILED,
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
