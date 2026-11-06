<?php
declare(strict_types=1);

namespace Files\Test\TestCase\Command;

use App\Test\Traits\ConfigureTestTrait;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;
use Files\Command\CheckFilesCommand;
use Files\Model\Entity\File;
use Files\Service\FileStorage;
use Files\Service\Previews;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Files\Command\CheckFilesCommand Test Case
 *
 * The seam between the store and the records is torn here on purpose, one way at a time, because
 * the whole point of the command is what it says when that has happened.
 */
#[UsesClass(CheckFilesCommand::class)]
class CheckFilesCommandTest extends TestCase
{
    use ConfigureTestTrait;
    use ConsoleIntegrationTestTrait;
    use EmailTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
        'plugin.Files.RemovedFileLinks',
    ];

    /**
     * Where the bytes and the pictures go while this runs. The command throws away what it finds
     * there, so neither may be left pointing at a real store.
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

        $this->root = TMP . 'check-files-' . uniqid();
        $this->previews = TMP . 'check-files-previews-' . uniqid();
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
        $this->restoreConfigure();
        $this->removeDirectory($this->root);
        $this->removeDirectory($this->previews);

        parent::tearDown();
    }

    /**
     * @link \Files\Command\CheckFilesCommand::execute()
     * @return void
     */
    public function testAStoreNobodyHasTornSaysSo(): void
    {
        $this->fileOnTheShelf();

        $this->exec('check_files');

        $this->assertExitSuccess();
        $this->assertOutputContains('The store and the records agree.');
    }

    /**
     * The half that cannot be put right: the run ends badly, which is what makes it worth having
     * in cron.
     *
     * @link \Files\Command\CheckFilesCommand::execute()
     * @return void
     */
    public function testARowWhoseBytesAreGoneEndsTheRunBadly(): void
    {
        $this->withConfigure(['Report.errorEmails' => ['oncall@example.com']]);
        $file = $this->fileOnTheShelf();
        unlink($this->root . DS . str_replace('/', DS, $file->path));

        $this->exec('check_files');

        $this->assertExitError();
        $this->assertErrorContains('Rows whose bytes are gone');
        // Nobody reads what a cron run said.
        $this->assertMailSentTo('oncall@example.com');
        $this->assertMailContains('Rows whose bytes are gone: 1');
    }

    /**
     * Bytes that were written and never claimed are the harmless way round for a backup to tear,
     * so they are said out loud and thrown away only when asked.
     *
     * @link \Files\Command\CheckFilesCommand::execute()
     * @return void
     */
    public function testBytesNobodyClaimsAreThrownAwayOnlyWhenAskedFor(): void
    {
        $stray = $this->root . DS . 'ab' . DS . 'cd' . DS . 'abcdef';
        mkdir(dirname($stray), 0777, true);
        file_put_contents($stray, 'nobody wrote a row for this');
        touch($stray, (int)strtotime('-2 days'));

        $this->withConfigure(['Report.errorEmails' => ['oncall@example.com']]);

        $this->exec('check_files');

        $this->assertExitSuccess();
        $this->assertErrorContains('Bytes nobody has a row for');
        $this->assertFileExists($stray);
        // Rubbish is not worth an email a day, or the ones that matter stop being read.
        $this->assertNoMailSent();

        $this->exec('check_files --fix');

        $this->assertExitSuccess();
        $this->assertOutputContains('thrown away');
        $this->assertFileDoesNotExist($stray);
    }

    /**
     * Bytes are written before their row, so bytes that have only just arrived may be somebody
     * filing a paper at this moment. They are reported and stay.
     *
     * @link \Files\Command\CheckFilesCommand::execute()
     * @return void
     */
    public function testBytesThatHaveOnlyJustArrivedAreLeftAlone(): void
    {
        $stray = $this->root . DS . 'ab' . DS . 'cd' . DS . 'abcdef';
        mkdir(dirname($stray), 0777, true);
        file_put_contents($stray, 'a row is on its way');

        $this->exec('check_files --fix');

        $this->assertExitSuccess();
        $this->assertFileExists($stray);
    }

    /**
     * The list takes a whole pass over the store to make, and a row may turn up in the meantime.
     *
     * @link \Files\Command\CheckFilesCommand::execute()
     * @return void
     */
    public function testBytesThatGetARowDuringTheRunAreLeftAlone(): void
    {
        $file = (new FileStorage())->store('a paper of some sort', 'application/pdf');
        $bytes = $this->root . DS . $file->path;
        touch($bytes, (int)strtotime('-2 days'));

        $files = $this->getTableLocator()->get('Files.Files');
        $files->deleteAll(['id' => $file->id]);

        // the first question is the snapshot of the rows, and the row arrives right after it
        $asked = 0;
        $files->getEventManager()->on(
            'Model.beforeFind',
            function () use (&$asked, $files, $file): void {
                $asked++;
                if ($asked === 2) {
                    $file->setNew(true);
                    $files->saveOrFail($file);
                }
            },
        );

        $this->exec('check_files --fix');

        $this->assertExitSuccess();
        $this->assertFileExists($bytes);
    }

    /**
     * Pictures of content that is gone go the way of bytes nobody claims. Whatever is still being
     * made is left alone, it belongs to whoever is making it.
     *
     * @link \Files\Service\Previews::leftBehind()
     * @return void
     */
    public function testPicturesOfContentThatIsGoneAreThrownAwayOnlyWhenAskedFor(): void
    {
        $file = $this->fileOnTheShelf();

        $kept = Previews::pathFor((string)$file->id, Previews::THUMBNAIL);
        $gone = Previews::pathFor('aabbccdd-1111-4222-8333-444444444444', Previews::PREVIEW);
        $unfinished = $gone . '.123.part';
        $scratch = $this->previews . DS . '.scratch' . DS . 'working';

        foreach ([$kept, $gone, $unfinished, $scratch] as $path) {
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }

            file_put_contents($path, 'a picture');
        }

        $this->exec('check_files');

        $this->assertExitSuccess();
        $this->assertErrorContains('Pictures of content nobody has a row for');
        $this->assertFileExists($gone);

        $this->exec('check_files --fix');

        $this->assertExitSuccess();
        $this->assertFileDoesNotExist($gone);
        $this->assertFileExists($kept);
        $this->assertFileExists($unfinished);
        $this->assertFileExists($scratch);
    }

    /**
     * What waits in the trash can still come back, so to the check it is simply on file.
     *
     * @link \Files\Model\Table\FilesTable::findUnused()
     * @return void
     */
    public function testWhatWaitsInTheTrashIsNeitherRubbishNorUnused(): void
    {
        $this->fileOnTheShelf();

        /** @var \Files\Model\Entity\FileLink $link */
        $link = $this->fetchTable('Files.FileLinks')->find()->firstOrFail();
        (new FileStorage())->unlink($link);

        $this->exec('check_files');

        $this->assertExitSuccess();
        $this->assertOutputContains('The store and the records agree.');
        $this->assertOutputNotContains('rows nothing points at');
    }

    /**
     * A torn backup can leave a file that was written only half way, and the row says how long it
     * was meant to be.
     *
     * @link \Files\Command\CheckFilesCommand::execute()
     * @return void
     */
    public function testBytesThatAreNotTheSizeTheRowSaysAreFound(): void
    {
        $file = $this->fileOnTheShelf();
        file_put_contents($this->root . DS . str_replace('/', DS, $file->path), 'cut short');

        $this->exec('check_files');

        $this->assertExitError();
        $this->assertErrorContains('not the size they say');
    }

    /**
     * Puts one paper in the store, filed against something.
     *
     * @return \Files\Model\Entity\File
     */
    private function fileOnTheShelf(): File
    {
        $storage = new FileStorage();
        $file = $storage->store('a paper of some sort', 'application/pdf');

        $storage->link(
            $file,
            'ContractProposals',
            '11111111-2222-4333-8444-555555555555',
            'contract-new',
            'generated',
            ['name' => 'contract.pdf'],
        );

        return $file;
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
