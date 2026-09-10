<?php
declare(strict_types=1);

namespace Files\Command;

use App\Service\ErrorReport;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;
use Files\Model\Entity\File;
use Files\Service\FileStorage;
use League\Flysystem\StorageAttributes;
use Override;

/**
 * CheckFiles command.
 *
 * The shelf and the records are two halves of one thing, and nothing keeps them together but the
 * order they are written in. This asks what the two have to say about each other.
 *
 * Only one of the answers is repairable. Bytes nobody has a row for are rubbish - that is what a
 * backup taken while something was being written leaves behind - and `--fix` throws them away.
 * A row whose bytes are gone cannot be put right from here at all, so it is reported and the run
 * ends badly, which is what makes this worth running from cron.
 *
 * A document filed against content that is not there is not asked about: the foreign key keeps
 * that promise, and a check for what the database will not allow is a check that never fires.
 *
 * Nobody reads what a cron run said, so what cannot be put right is also sent to whoever is on
 * call. Bytes nobody has a row for are not: they are rubbish, the run is a success, and an email
 * a day about rubbish teaches the reader to ignore the ones that matter.
 */
class CheckFilesCommand extends Command
{
    /**
     * How many of a kind are named before the rest are only counted.
     *
     * @var int
     */
    private const SHOWN = 10;

    /**
     * The name of this command.
     *
     * @var string
     */
    protected string $name = 'check_files';

    /**
     * Get the default command name.
     *
     * @return string
     */
    #[Override]
    public static function defaultName(): string
    {
        return 'check_files';
    }

    /**
     * Get the command description.
     *
     * @return string
     */
    #[Override]
    public static function getDescription(): string
    {
        return 'Checks what is on the shelf against what is on file.';
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/5/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    #[Override]
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser->setDescription(__d(
            'files',
            'Checks what is on the shelf against what is on file, and says what does not match.',
        ));

        $parser->addOption('fix', [
            'help' => __d('files', 'Throw away the bytes nobody has a row for. Nothing else is touched.'),
            'boolean' => true,
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $storage = new FileStorage();
        $files = $this->fetchTable('Files.Files');

        /** @var iterable<\Files\Model\Entity\File> $rows */
        $rows = $files->find()->all();

        $claimed = [];
        $gone = [];
        $short = [];

        foreach ($rows as $file) {
            $claimed[$file->path] = true;

            if (!$storage->has($file)) {
                $gone[] = $file;

                continue;
            }

            $size = $storage->filesystem()->fileSize($file->path);
            if ($size !== $file->byte_size) {
                $short[] = $file;
            }
        }

        $stray = $this->stray($storage, $claimed);
        $unused = $files->find('unused')->count();

        $io->out(__d('files', '{0} rows, {1} of them on the shelf.', count($claimed), count($claimed) - count($gone)));

        $this->report($io, __d('files', 'Rows whose bytes are gone'), array_map(
            fn(File $file): string => $file->path . '  ' . $file->hash,
            $gone,
        ), true);

        $this->report($io, __d('files', 'Rows whose bytes are not the size they say'), array_map(
            fn(File $file): string => $file->path . '  ' . $file->byte_size,
            $short,
        ), true);

        $this->report($io, __d('files', 'Bytes nobody has a row for'), $stray, false);

        if ($unused > 0) {
            $io->out(__d(
                'files',
                '{0} rows nothing points at. They are safe to let go of one at a time from the storage page.',
                $unused,
            ));
        }

        if ($stray !== [] && $args->getOption('fix')) {
            foreach ($stray as $path) {
                $storage->filesystem()->delete($path);
            }

            $io->success(__d('files', '{0} thrown away.', count($stray)));
            $stray = [];
        }

        if ($gone === [] && $short === []) {
            $io->success($stray === []
                ? __d('files', 'The shelf and the records agree.')
                : __d('files', 'Nothing is missing. Run this again with --fix to throw the rest away.'));

            return static::CODE_SUCCESS;
        }

        $this->tell(count($gone), count($short));

        return static::CODE_ERROR;
    }

    /**
     * Tells whoever is on call that content is missing.
     *
     * @param int $gone How many rows have no bytes at all.
     * @param int $short How many have bytes that are not the length the row says.
     * @return void
     */
    private function tell(int $gone, int $short): void
    {
        $body = __d(
            'files',
            <<<'TEXT'
            The store no longer holds everything the records point at.

            Rows whose bytes are gone: {0}
            Rows whose bytes are not the size they say: {1}

            Run `bin/cake check_files` to see which. What is missing has to come back
            from a backup - neither can be put right from there.
            TEXT,
            $gone,
            $short,
        );

        Log::error('check_files: ' . $gone . ' rows without bytes, ' . $short . ' of the wrong size.');
        ErrorReport::send(__d('files', 'File store check failed'), $body);
    }

    /**
     * What is on the shelf that no row claims.
     *
     * @param \Files\Service\FileStorage $storage Where the bytes live.
     * @param array<string, bool> $claimed The paths the rows stand for.
     * @return list<string>
     */
    private function stray(FileStorage $storage, array $claimed): array
    {
        $stray = [];

        /** @var \League\Flysystem\StorageAttributes $entry */
        foreach ($storage->filesystem()->listContents('', true) as $entry) {
            if ($entry->type() !== StorageAttributes::TYPE_FILE || isset($claimed[$entry->path()])) {
                continue;
            }

            $stray[] = $entry->path();
        }

        return $stray;
    }

    /**
     * Says how many of a kind there are, and names the first few of them.
     *
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @param string $what What they are.
     * @param list<string> $found The ones found.
     * @param bool $serious Whether this is something that cannot be put right from here.
     * @return void
     */
    private function report(ConsoleIo $io, string $what, array $found, bool $serious): void
    {
        if ($found === []) {
            return;
        }

        $line = $what . ': ' . count($found);
        $serious ? $io->error($line) : $io->warning($line);

        foreach (array_slice($found, 0, self::SHOWN) as $one) {
            $io->out('  ' . $one);
        }

        if (count($found) > self::SHOWN) {
            $io->out(__d('files', '  ... and {0} more.', count($found) - self::SHOWN));
        }
    }
}
