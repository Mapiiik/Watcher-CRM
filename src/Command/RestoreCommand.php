<?php
declare(strict_types=1);

namespace App\Command;

use App\Backup\DataArchive;
use App\Backup\DatabaseDump;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Override;
use RuntimeException;
use Throwable;

/**
 * Restore command.
 *
 * The other way round from {@see \App\Command\BackupCommand}, in both senses: the files go back
 * first and the database after them, so that there is never a row pointing at bytes that are not
 * there yet.
 *
 * It writes over what it finds, so it refuses a database that already holds something unless it is
 * told outright to go ahead.
 */
class RestoreCommand extends Command
{
    /**
     * The connection every deployment has, and the one that is asked for.
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
            'Puts a backup back: the data root first, then the database.',
        ));

        $parser->addArgument('directory', [
            'help' => __('The directory one run of the backup wrote.'),
            'required' => true,
        ]);

        $parser->addOption('force', [
            'help' => __('Go ahead even though the database already holds something, dropping it first.'),
            'boolean' => true,
        ]);

        $parser->addOption('with-radius', [
            'help' => __('Put the RADIUS database back as well.'),
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
        try {
            $directory = rtrim((string)$args->getArgument('directory'), '/\\');
            $force = $args->getOption('force') === true;

            if (!is_dir($directory)) {
                throw new RuntimeException(sprintf('There is no backup at %s.', $directory));
            }

            $connections = $args->getOption('with-radius') === true
                ? [self::CONNECTION, self::RADIUS_CONNECTION]
                : [self::CONNECTION];

            // Asked of every database before anything is written, so that a run which is going to
            // refuse does it before the files have been put back.
            foreach ($connections as $name) {
                $this->refuseUnlessEmpty($name, $force);
            }

            $this->unpackData($directory, $io);

            foreach ($connections as $name) {
                $this->restoreDatabase($name, $directory, $force, $io);
            }

            $io->success(__('The backup in {0} has been put back.', $directory));
            $this->sayWhatIsLeft($io);

            return static::CODE_SUCCESS;
        } catch (Throwable $e) {
            Log::error('Error during the restore: ' . $e->getMessage());

            $io->error(__('Error during the restore: {0}', $e->getMessage()));

            return static::CODE_ERROR;
        }
    }

    /**
     * Stops a restore that would write over a database somebody is using.
     *
     * @param string $name The connection.
     * @param bool $force Whether the operator has said to go ahead anyway.
     * @return void
     * @throws \RuntimeException When it holds something and nobody said to go ahead.
     */
    private function refuseUnlessEmpty(string $name, bool $force): void
    {
        if ($force) {
            return;
        }

        /** @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get($name);
        $tables = $connection->getSchemaCollection()->listTables();

        if ($tables !== []) {
            throw new RuntimeException(sprintf(
                'The %s database already holds %d tables. Pass --force to drop them and put the backup back.',
                $name,
                count($tables),
            ));
        }
    }

    /**
     * Puts the data root back.
     *
     * @param string $directory The backup.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return void
     * @throws \RuntimeException When the archive is missing or cannot be unpacked.
     */
    private function unpackData(string $directory, ConsoleIo $io): void
    {
        $file = $directory . DS . DataArchive::FILENAME;

        if (!is_file($file)) {
            throw new RuntimeException(sprintf('There is no %s in %s.', DataArchive::FILENAME, $directory));
        }

        (new DataArchive((string)Configure::read('Data.root')))->unpack($file);

        $io->out(__('The data root is back.'));
    }

    /**
     * Puts one database back.
     *
     * @param string $name The connection.
     * @param string $directory The backup.
     * @param bool $clean Whether what is already there is dropped first.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return void
     * @throws \RuntimeException When the dump is missing or cannot be read back.
     */
    private function restoreDatabase(string $name, string $directory, bool $clean, ConsoleIo $io): void
    {
        $dump = new DatabaseDump($name, ConnectionManager::get($name)->config());
        $file = $directory . DS . $dump->filename();

        if (!is_file($file)) {
            throw new RuntimeException(sprintf('There is no %s in %s.', $dump->filename(), $directory));
        }

        try {
            $dump->restore($file, $clean);
        } catch (RuntimeException $e) {
            $io->warning(__('Run this where the client is, with the backup mounted into it:'));
            $io->out($dump->elsewhere(DatabaseDump::RESTORE_PROGRAM, $dump->restoreArguments($file, $clean)));

            throw $e;
        }

        $io->out(__('The {0} database is back.', $name));
    }

    /**
     * What has to be done by hand afterwards.
     *
     * Not done here: the schema has just changed underneath this very process, so what it would
     * cache is what it read before the restore. The check comes last because it is the one thing
     * that reads both halves at once, and so the only one that says they were taken of the same
     * moment.
     *
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return void
     */
    private function sayWhatIsLeft(ConsoleIo $io): void
    {
        $io->out('');
        $io->info(__('Now run these, in this order:'));
        $io->out('  bin/cake cache clear_all');
        $io->out('  bin/cake schema_cache build');
        $io->out('  bin/cake check_files');
    }
}
