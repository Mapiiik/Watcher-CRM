<?php
declare(strict_types=1);

namespace Files\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Files\Controller\TrashController;
use Files\Model\Entity\FileLink;
use Files\Model\Table\DocumentationsTable;
use Files\Model\Table\DocumentationTypesTable;
use Files\Service\Documentations;
use Files\Service\FileStorage;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Files\Controller\TrashController Test Case
 *
 * Who may do what here is most of the point: a manager brings things back, only the administrator
 * throws them away, and nobody else gets to see what was removed at all.
 */
#[UsesClass(TrashController::class)]
class TrashControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
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
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->root = TMP . 'trash-controller-' . uniqid();
        $this->previews = TMP . 'trash-controller-previews-' . uniqid();
        $this->records = Configure::read('Files.records');

        Configure::write('Files.root', $this->root);
        Configure::write('Files.previews', $this->previews);
        Configure::write('Files.records', [
            Documentations::MODEL => ['plugin' => null, 'controller' => 'Documentations', 'action' => 'view'],
        ]);

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->login();
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
     * @link \Files\Controller\TrashController::index()
     * @return void
     */
    public function testTheTrashListsWhatWasRemovedAndOffersItBack(): void
    {
        $link = $this->filed('a paper removed by mistake', 'received', 'mistake.pdf');
        (new FileStorage())->unlink($link);

        $this->get('/files/trash');

        $this->assertResponseOk();
        $this->assertResponseContains('mistake.pdf');
        $this->assertResponseContains('/files/trash/open/' . $link->id);
        $this->assertResponseContains('/files/trash/restore/' . $link->id);
        $this->assertResponseContains('/files/trash/delete/' . $link->id);
    }

    /**
     * The viewer turns through the trash as it does through any papers, and asks the trash for
     * them: the ordinary address no longer knows what was removed.
     *
     * @link \Files\View\Helper\PreviewHelper
     * @return void
     */
    public function testThePicturesOpenTheViewerOnTheContentOfTheTrash(): void
    {
        $link = $this->filed('a paper to look through', 'received', 'look.pdf');
        (new FileStorage())->unlink($link);

        $this->get('/files/trash');

        $this->assertResponseOk();
        $this->assertResponseContains('data-files-gallery="trash"');
        $this->assertResponseContains('data-files-pages=');
        $this->assertResponseContains('/files/trash/thumbnail/' . $link->id);
        $this->assertResponseNotContains('/files/file-links/thumbnail/');
        $this->assertResponseNotContains('/files/file-links/open/');
    }

    /**
     * A button that would only refuse is not offered, and the page says why.
     *
     * @link \Files\Controller\TrashController::index()
     * @return void
     */
    public function testWhatHasNoPlaceToGoIsNotOfferedAndThePageSaysWhy(): void
    {
        $link = $this->filed('the old print', 'generated', 'old.pdf');
        (new FileStorage())->unlink($link);
        $this->filed('the new print', 'generated', 'new.pdf', $link->foreign_key);

        $this->get('/files/trash');

        $this->assertResponseOk();
        $this->assertResponseContains('its place is taken');
        $this->assertResponseNotContains('/files/trash/restore/' . $link->id);

        $this->post('/files/trash/restore/' . $link->id);

        $this->assertRedirect();
        $this->assertFlashMessageAt(
            0,
            'The file could not be restored: This document has been made again since, so its place is taken.',
        );
        $this->assertSame(1, $this->fetchTable('Files.RemovedFileLinks')->find()->count());
    }

    /**
     * @link \Files\Controller\TrashController::open()
     * @return void
     */
    public function testWhatWasRemovedCanBeOpenedFromTheTrashAlone(): void
    {
        $link = $this->filed('what the paper says', 'received', 'paper.pdf');
        (new FileStorage())->unlink($link);

        $this->get('/files/trash/open/' . $link->id);

        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Disposition', 'inline');
        $this->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->get('/files/trash/download/' . $link->id);

        $this->assertResponseOk();
        $this->assertHeaderContains('Content-Disposition', 'attachment');

        $this->get('/files/file-links/open/' . $link->id);

        $this->assertResponseCode(404);
    }

    /**
     * Who removed it is on the row, and who filed it stays who filed it after it comes back.
     *
     * @link \Files\Controller\TrashController::restore()
     * @return void
     */
    public function testRemovingSaysWhoAndRestoringLeavesTheAuthorAlone(): void
    {
        /** @var \App\Model\Entity\User $user */
        $user = $this->getTableLocator()->get(Configure::read('Users.table', 'Users'))->find()->firstOrFail();
        $user->role = 'admin';

        $link = $this->filed('a paper with an author', 'received', 'paper.pdf');
        $this->fetchTable('Files.FileLinks')->updateAll(['created_by' => $user->id], ['id' => $link->id]);

        $this->session(['Auth' => $user]);
        $this->post('/files/file-links/delete/' . $link->id);

        $this->assertRedirect();

        /** @var \Files\Model\Entity\RemovedFileLink $removed */
        $removed = $this->fetchTable('Files.RemovedFileLinks')->get($link->id);

        $this->assertSame($user->id, $removed->removed_by);

        $this->get('/files/trash');

        $this->assertResponseOk();
        $this->assertResponseContains(h((string)$user->username));

        // Somebody else, and somebody the footprint would write down as nobody.
        $this->login();
        $this->post('/files/trash/restore/' . $link->id);

        $this->assertRedirect();
        $this->assertFlashMessageAt(0, 'The file has been restored.');

        /** @var \Files\Model\Entity\FileLink $back */
        $back = $this->fetchTable('Files.FileLinks')->get($link->id);

        $this->assertSame($user->id, $back->created_by);
    }

    /**
     * @link \Files\Controller\TrashController::delete()
     * @return void
     */
    public function testAManagerBringsThingsBackButOnlyTheAdministratorThrowsThemAway(): void
    {
        $link = $this->filed('a paper with a future', 'received', 'paper.pdf');
        (new FileStorage())->unlink($link);

        $this->login('manager');

        $this->get('/files/trash');

        $this->assertResponseOk();
        $this->assertResponseContains('/files/trash/restore/' . $link->id);
        $this->assertResponseNotContains('/files/trash/delete/' . $link->id);

        $this->post('/files/trash/delete/' . $link->id);

        $this->assertRedirect('/');
        $this->assertSame(1, $this->fetchTable('Files.RemovedFileLinks')->find()->count());

        $this->post('/files/trash/restore/' . $link->id);

        $this->assertSame(0, $this->fetchTable('Files.RemovedFileLinks')->find()->count());
        $this->assertSame(1, $this->fetchTable('Files.FileLinks')->find()->count());
    }

    /**
     * The widest of the other roles, which still may not.
     *
     * @link \Files\Controller\TrashController
     * @return void
     */
    public function testNobodyElseSeesWhatWasRemoved(): void
    {
        $link = $this->filed('a paper for few eyes', 'received', 'paper.pdf');
        (new FileStorage())->unlink($link);

        $this->login('sales-manager');

        foreach (['', '/open/' . $link->id, '/download/' . $link->id, '/thumbnail/' . $link->id] as $page) {
            $this->get('/files/trash' . $page);

            $this->assertRedirect('/');
        }

        $this->post('/files/trash/restore/' . $link->id);

        $this->assertRedirect('/');
        $this->assertSame(1, $this->fetchTable('Files.RemovedFileLinks')->find()->count());
    }

    /**
     * @link \Files\Controller\TrashController::delete()
     * @return void
     */
    public function testDeletingForGoodSaysWhenTheContentHasToStay(): void
    {
        $link = $this->filed('a paper two records share', 'received', 'paper.pdf');
        (new FileStorage())->unlink($link);
        $this->filed('a paper two records share', 'received', 'paper.pdf');

        $this->post('/files/trash/delete/' . $link->id);

        $this->assertRedirect();
        $this->assertFlashMessageAt(0, 'Removed from the trash. The content stays, as another record still uses it.');
        $this->assertSame(1, $this->fetchTable('Files.Files')->find()->count());
    }

    /**
     * Files a paper in a folder of its own, unless told which record.
     *
     * @param string $bytes What is in it.
     * @param string $variant Which variant.
     * @param string $name What it arrived called.
     * @param string|null $foreign_key Which record.
     * @return \Files\Model\Entity\FileLink
     */
    private function filed(string $bytes, string $variant, string $name, ?string $foreign_key = null): FileLink
    {
        $storage = new FileStorage();

        return $storage->link(
            $storage->store($bytes, 'application/pdf'),
            Documentations::MODEL,
            $foreign_key ?? $this->folder(),
            'contract-new',
            $variant,
            ['name' => $name],
        );
    }

    /**
     * A folder, saved.
     *
     * @return string Its id.
     */
    private function folder(): string
    {
        /** @var \Files\Model\Table\DocumentationTypesTable $kinds */
        $kinds = $this->fetchTable(DocumentationTypesTable::class);
        $kind = $kinds->saveOrFail($kinds->newEntity([
            'name' => 'Documentation ' . uniqid(),
            'position' => 0,
            'currently_offered' => true,
            'date_required' => false,
        ]));

        /** @var \Files\Model\Table\DocumentationsTable $folders */
        $folders = $this->fetchTable(DocumentationsTable::class);
        $folder = $folders->newEntity(['name' => 'A folder', 'documentation_type_id' => $kind->id]);
        $folder->set('documentation_type', $kind);

        return (string)$folders->saveOrFail($folder)->id;
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
