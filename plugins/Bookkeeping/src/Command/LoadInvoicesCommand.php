<?php
declare(strict_types=1);

namespace Bookkeeping\Command;

use App\Service\OperatorReport;
use Bookkeeping\Model\Enum\InvoiceSyncMode;
use Bookkeeping\Service\BookkeepingService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Override;
use RuntimeException;
use Throwable;

/**
 * LoadInvoices command.
 *
 * Synchronizes invoices from the configured accounting system
 * into the local CRM database.
 */
class LoadInvoicesCommand extends Command
{
    private readonly BookkeepingService $bookkeeping;

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
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
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
            ])
            ->addOption('mode', [
                'help' => __d(
                    'bookkeeping',
                    'Synchronization mode',
                ),
                'default' => InvoiceSyncMode::DELTA->value,
                'choices' => array_keys(InvoiceSyncMode::options()),
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
        $mode = InvoiceSyncMode::from((string)$args->getOption('mode'));
        $now = new DateTime();
        $lastChanges = null;

        $io->info(__d(
            'bookkeeping',
            'Running invoice sync in mode: {0}',
            $mode->label(),
        ));

        try {
            $lastChanges = $this->resolveLastChanges($args, $io);
            if (!$lastChanges instanceof DateTime) {
                throw new RuntimeException(__d(
                    'bookkeeping',
                    'Unable to resolve last synchronization timestamp.',
                ));
            }

            $io->info(__d(
                'bookkeeping',
                'Using last changes time: {0}',
                [$lastChanges->format('Y-m-d H:i:s')],
            ));

            $result = $this->bookkeeping->syncInvoices(
                mode: $mode,
                lastChanges: $lastChanges,
            );

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

            $this->saveLastSynchronizationTime($now);

            return static::CODE_SUCCESS;
        } catch (Throwable $e) {
            Log::error('Error during invoice synchronization: ' . $e->getMessage());

            $io->error(__d(
                'bookkeeping',
                'Error during invoice synchronization: {0}',
                [$e->getMessage()],
            ));

            OperatorReport::send(
                __d('bookkeeping', 'Invoice synchronization failed'),
                __d(
                    'bookkeeping',
                    'Invoice synchronization failed.' . PHP_EOL
                    . PHP_EOL
                    . 'Mode: {0}' . PHP_EOL
                    . 'Last changes: {1}' . PHP_EOL
                    . 'Error: {2}',
                    [
                        $mode->value,
                        $lastChanges?->format('Y-m-d H:i:s') ?? 'N/A',
                        $e->getMessage(),
                    ],
                ),
            );

            return static::CODE_ERROR;
        }
    }

    /**
     * Resolve the timestamp used for synchronization.
     *
     * @return \Cake\I18n\DateTime|null
     */
    private function resolveLastChanges(Arguments $args, ConsoleIo $io): ?DateTime
    {
        $value = $args->getOption('last_changes');

        if ($value !== null && is_string($value)) {
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
        if (!$last instanceof DateTime) {
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
        $file = Configure::read('Data.root')
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
        $file = Configure::read('Data.root')
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
