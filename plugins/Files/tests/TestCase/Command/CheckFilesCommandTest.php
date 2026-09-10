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
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Files\Command\CheckFilesCommand Test Case
 *
 * The seam between the shelf and the records is torn here on purpose, one way at a time, because
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
        'app.AppUsers',
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
    ];

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

        $this->root = TMP . 'check-files-' . uniqid();
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
        $this->restoreConfigure();
        $this->removeDirectory($this->root);

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
        $this->assertOutputContains('The shelf and the records agree.');
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
     * Puts one paper on the shelf, filed against something.
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
