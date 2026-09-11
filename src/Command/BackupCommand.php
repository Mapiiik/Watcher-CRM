<?php
declare(strict_types=1);

namespace App\Command;

use App\Backup\DataArchive;
use App\Backup\DatabaseDump;
use App\Service\ErrorReport;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Override;
use RuntimeException;
use Throwable;

/**
 * Backup command.
 *
 * A dump of the database stopped being the whole backup the day the papers began to live outside
 * it. Both halves are taken here, into a directory of their own named after the moment, so that a
 * run never writes over what an earlier one made.
 *
 * The database is taken first and the files after it. That way anything created while the backup
 * runs ends up as bytes nobody has a row for, which is rubbish that `bin/cake check_files` finds
 * and throws away. The other order would leave a row pointing at bytes that were never taken,
 * which is a broken record and nothing can be done about it afterwards.
 */
class BackupCommand extends Command
{
    /**
     * The connection every deployment has. The RADIUS one is asked for, because on most of them
     * that database belongs to FreeRADIUS and is somebody else's to keep.
     */
    private const CONNECTION = 'default';
    private const RADIUS_CONNECTION = 'radius';

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

        $parser->setDescription(__(
            'Writes the database and everything under the data root into a directory of their own.',
        ));

        $parser->addArgument('directory', [
            'help' => __('Where the backup is put. A directory named after the moment is made inside it.'),
            'required' => true,
        ]);

        $parser->addOption('with-radius', [
            'help' => __('Take the RADIUS database as well, for a deployment where it is ours.'),
            'boolean' => true,
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null|void The exit code or null for success
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io)
    {
        $directory = null;

        try {
            $directory = $this->makeDirectory((string)$args->getArgument('directory'));

            $written = [[__('What'), __('Where'), __('Size')]];

            foreach ($this->connections($args->getOption('with-radius') === true) as $name) {
                $written[] = $this->dumpDatabase($name, $directory, $io);
            }

            $written[] = $this->packData($directory);

            $io->helper('Table')->output($written);
            $io->success(__('The backup is in {0}.', $directory));

            return static::CODE_SUCCESS;
        } catch (Throwable $e) {
            // What a failed run left behind goes with it. Half a backup that looks like a whole
            // one is worse than none, because it is the one somebody reaches for.
            if ($directory !== null) {
                $this->discard($directory);
            }

            Log::error('Error during the backup: ' . $e->getMessage());

            $io->error(__('Error during the backup: {0}', $e->getMessage()));

            // A backup runs from cron and nobody reads what cron said. A deployment that thinks
            // it is being backed up and is not is the worst way round of all.
            ErrorReport::send(
                __('Backup failed'),
                implode("\n", [
                    __('The backup did not run, and nothing was left behind.'),
                    '',
                    __('Error: {0}', $e->getMessage()),
                ]),
            );

            return static::CODE_ERROR;
        }
    }

    /**
     * Throws away what this run had written so far.
     *
     * @param string $directory The directory this run made.
     * @return void
     */
    private function discard(string $directory): void
    {
        foreach (array_diff((array)scandir($directory), ['.', '..']) as $entry) {
            $path = $directory . DS . $entry;
            if (is_file($path)) {
                unlink($path);
            }
        }

        rmdir($directory);
    }

    /**
     * The connections this run takes.
     *
     * @param bool $with_radius Whether the RADIUS database is ours to take.
     * @return list<string>
     */
    private function connections(bool $with_radius): array
    {
        return $with_radius ? [self::CONNECTION, self::RADIUS_CONNECTION] : [self::CONNECTION];
    }

    /**
     * Writes one database out.
     *
     * @param string $name The connection.
     * @param string $directory Where the backup is being made.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return list<string> What to say about it afterwards.
     * @throws \RuntimeException When the dump cannot be made.
     */
    private function dumpDatabase(string $name, string $directory, ConsoleIo $io): array
    {
        $dump = new DatabaseDump($name, ConnectionManager::get($name)->config());
        $file = $directory . DS . $dump->filename();

        try {
            $dump->dump($file);
        } catch (RuntimeException $e) {
            $this->sayWhereTheClientIs($dump, $file, $io);

            throw $e;
        }

        return [__('Database ({0})', $name), $dump->filename(), $this->sizeOf($file)];
    }

    /**
     * Packs the data root.
     *
     * @param string $directory Where the backup is being made.
     * @return list<string> What to say about it afterwards.
     */
    private function packData(string $directory): array
    {
        $archive = new DataArchive((string)Configure::read('Data.root'));
        $file = $directory . DS . DataArchive::FILENAME;

        $archive->pack($file);

        return [__('Data root'), DataArchive::FILENAME, $this->sizeOf($file)];
    }

    /**
     * Says where to run the same thing, for a deployment whose application carries no client.
     *
     * Under Docker that is the usual case rather than an exception, and the database container's
     * client matches its server by definition - which is what the version rule asks for.
     *
     * @param \App\Backup\DatabaseDump $dump The dump that could not be made.
     * @param string $file Where it was to go.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return void
     */
    private function sayWhereTheClientIs(DatabaseDump $dump, string $file, ConsoleIo $io): void
    {
        $io->warning(__('Run this where the client is, with the directory mounted into it:'));
        $io->out($dump->elsewhere(DatabaseDump::DUMP_PROGRAM, $dump->dumpArguments($file)));
    }

    /**
     * The directory this run writes into, made fresh so that nothing is ever written over.
     *
     * @param string $parent Where it goes.
     * @return string
     * @throws \RuntimeException When the parent is not usable or the directory cannot be made.
     */
    private function makeDirectory(string $parent): string
    {
        $parent = rtrim($parent, '/\\');

        if (!is_dir($parent) || !is_writable($parent)) {
            throw new RuntimeException(sprintf('%s is not a directory that can be written to.', $parent));
        }

        $directory = $parent . DS . DateTime::now()->format('Y-m-d-His');

        if (!mkdir($directory, 0770) && !is_dir($directory)) {
            throw new RuntimeException(sprintf('Could not make %s.', $directory));
        }

        return $directory;
    }

    /**
     * How big what was written is, for the operator to see at a glance that it is not empty.
     *
     * @param string $file The file.
     * @return string
     */
    private function sizeOf(string $file): string
    {
        $bytes = is_file($file) ? (int)filesize($file) : 0;

        return sprintf('%.1f MB', $bytes / 1024 / 1024);
    }
}
