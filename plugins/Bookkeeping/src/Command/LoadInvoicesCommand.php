<?php
declare(strict_types=1);

namespace Bookkeeping\Command;

use Bookkeeping\Service\BookkeepingService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Override;
use Throwable;

/**
 * LoadInvoices command.
 *
 * Synchronizes invoices from the configured accounting system
 * into the local CRM database.
 */
class LoadInvoicesCommand extends Command
{
    private BookkeepingService $bookkeeping;

    /**
     * Initializes bookkeeping service used for invoice-related operations.
     */
    public function __construct()
    {
        $this->bookkeeping = new BookkeepingService();
    }

    /**
     * Get the command description.
     *
     * @return string
     */
    #[Override]
    public static function getDescription(): string
    {
        return 'Synchronize invoices from the accounting system.';
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/5/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    #[Override]
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription(static::getDescription())
            ->addOption('last_changes', [
                'help' => __d(
                    'bookkeeping',
                    'Override the saved date and time of the last synchronisation.'
                        . ' Only invoices changed after this date and time of change will be retrieved.'
                        . ' Format: YYYY-MM-DD HH:MM:SS (e.g., 2024-10-28 15:45:00).'
                        . ' If not provided, the date from the last successful synchronization will be used.',
                ),
                'default' => null,
            ]);
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
        $lastChanges = $this->resolveLastChanges($args, $io);
        if ($lastChanges === null) {
            return static::CODE_ERROR;
        }

        $io->info(__d(
            'bookkeeping',
            'Using last changes time: {0}',
            [$lastChanges->format('Y-m-d H:i:s')],
        ));

        try {
            $result = $this->bookkeeping->syncInvoices($lastChanges);
        } catch (Throwable $e) {
            $io->error(__d(
                'bookkeeping',
                'Error during invoice synchronization: {0}',
                [$e->getMessage()],
            ));

            return static::CODE_ERROR;
        }

        $io->success(__d(
            'bookkeeping',
            'Successfully imported {0} invoices. Created {1}, modified {2}, skipped {3}.',
            [
                $result['imported'] ?? 0,
                $result['created'] ?? 0,
                $result['modified'] ?? 0,
                $result['skipped'] ?? 0,
            ],
        ));

        $this->saveLastSynchronizationTime(new DateTime());

        return static::CODE_SUCCESS;
    }

    /**
     * Resolve the timestamp used for synchronization.
     *
     * @param \Cake\Console\Arguments $args
     * @param \Cake\Console\ConsoleIo $io
     * @return \Cake\I18n\DateTime|null
     */
    private function resolveLastChanges(Arguments $args, ConsoleIo $io): ?DateTime
    {
        $value = $args->getOption('last_changes');

        if ($value !== null) {
            try {
                return DateTime::createFromFormat('Y-m-d H:i:s', $value);
            } catch (Throwable) {
                $io->error(__d(
                    'bookkeeping',
                    'Invalid date format. Use YYYY-MM-DD HH:MM:SS.',
                ));

                return null;
            }
        }

        $last = $this->loadLastSynchronizationTime();
        if ($last === null) {
            $io->warning(__d(
                'bookkeeping',
                'No previous synchronization found. Using default value.',
            ));

            return new DateTime('-3 months');
        }

        return $last;
    }

    /**
     * Load last synchronization timestamp from storage.
     */
    private function loadLastSynchronizationTime(): ?DateTime
    {
        $file = (string)env('DATA_ROOT', ROOT . DS . 'data')
            . DS . 'invoices' . DS . 'last_sync.txt';

        if (!file_exists($file)) {
            return null;
        }

        $value = file_get_contents($file);
        if ($value === false) {
            Log::error(__d(
                'bookkeeping',
                'Error reading last synchronization time from file: {0}',
                [$file],
            ));

            return null;
        }

        try {
            return DateTime::createFromFormat('Y-m-d H:i:s', trim($value));
        } catch (Throwable $e) {
            Log::error(__d(
                'bookkeeping',
                'Error parsing last synchronization time from file: {0}',
                [$e->getMessage()],
            ));

            return null;
        }
    }

    /**
     * Save last synchronization timestamp.
     */
    private function saveLastSynchronizationTime(DateTime $dateTime): void
    {
        $file = (string)env('DATA_ROOT', ROOT . DS . 'data')
            . DS . 'invoices' . DS . 'last_sync.txt';

        $result = file_put_contents($file, $dateTime->format('Y-m-d H:i:s'));
        if ($result === false) {
            Log::error(__d(
                'bookkeeping',
                'Error saving last synchronization time to file: {0}',
                [$file],
            ));
        }
    }
}
