<?php
declare(strict_types=1);

namespace Bookkeeping\Command;

use App\Model\Table\AccountingProfilesTable;
use App\Model\Table\CustomersTable;
use App\Service\ErrorReport;
use Bookkeeping\Service\BookkeepingService;
use Bookkeeping\Service\InvoiceGenerationService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\Date;
use Cake\Log\Log;
use RuntimeException;
use Throwable;

/**
 * IssueInvoices command.
 *
 * Generates invoice drafts and sends them to accounting system.
 *
 * This command acts only as an orchestration layer:
 * - resolves invoiced month
 * - resolves accounting profile context
 * - loads invoice drafts
 * - delegates sending to the provider
 *
 * It does NOT:
 * - perform business validation
 * - calculate prices or VAT
 * - interpret accounting rules
 */
class IssueInvoicesCommand extends Command
{
    public const SCHEDULE_PREV_MONTH_ON_FIRST = 'prev-month-on-first';

    public const SCHEDULE_CURRENT_MONTH_ON_LAST = 'current-month-on-last';

    /**
     * The name of this command.
     */
    protected string $name = 'issue_invoices';

    /**
     * Get the default command name.
     *
     * @return string
     */
    public static function defaultName(): string
    {
        return 'issue_invoices';
    }

    /**
     * Get the command description.
     *
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Issue invoices and send them to the accounting system.';
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * Supported options:
     * - --month (-m): Invoiced month in YYYY-MM format
     * - --accounting-profile-id (-t): Accounting profile identifier (ID or code)
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription(static::getDescription())

            ->addOption('month', [
                'short' => 'm',
                'help' => __d(
                    'bookkeeping',
                    'Invoiced month (YYYY-MM). Overrides schedule and runs immediately.',
                ),
            ])

            ->addOption('schedule', [
                'help' => __d(
                    'bookkeeping',
                    'Run schedule when --month is not provided.',
                ),
                'choices' => [
                    self::SCHEDULE_PREV_MONTH_ON_FIRST,
                    self::SCHEDULE_CURRENT_MONTH_ON_LAST,
                ],
                'default' => self::SCHEDULE_PREV_MONTH_ON_FIRST,
            ])

            ->addOption('force', [
                'help' => __d(
                    'bookkeeping',
                    'Run even if today does not match the selected schedule (ignored when --month is provided).',
                ),
                'boolean' => true,
                'default' => false,
            ])

            ->addOption('accounting-profile-id', [
                'short' => 't',
                'help' => __d(
                    'bookkeeping',
                    'Accounting profile identifier (ID).',
                ),
            ])

            ->addOption('min-customer-number', [
                'help' => __d(
                    'bookkeeping',
                    'Send only customers with customer number greater than or equal to this value.',
                ),
            ])

            ->addOption('max-customer-number', [
                'help' => __d(
                    'bookkeeping',
                    'Send only customers with customer number less than or equal to this value.',
                ),
            ]);
    }

    /**
     * Execute the command.
     *
     * Workflow:
     * 1. Resolve invoiced month
     * 2. Resolve accounting profile context
     * 3. Load invoice drafts
     * 4. Delegate sending to AccountingProviderInterface
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int Exit code.
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        try {
            // Resolve invoiced month
            $monthOption = $args->getOption('month');
            $schedule = (string)$args->getOption('schedule');
            $force = (bool)$args->getOption('force');

            $today = Date::now();

            if ($monthOption !== null) {
                // Manual override: always run
                $invoicedMonth = new Date($monthOption . '-01');
            } else {
                // Scheduled mode
                if (!$force) {
                    if (
                        $schedule === self::SCHEDULE_PREV_MONTH_ON_FIRST
                        && !$today->equals($today->firstOfMonth())
                    ) {
                        $io->out(__d(
                            'bookkeeping',
                            'Not the first day of month, skipping.',
                        ));

                        return Command::CODE_SUCCESS;
                    }

                    if (
                        $schedule === self::SCHEDULE_CURRENT_MONTH_ON_LAST
                        && !$today->equals($today->lastOfMonth())
                    ) {
                        $io->out(__d(
                            'bookkeeping',
                            'Not the last day of month, skipping.',
                        ));

                        return Command::CODE_SUCCESS;
                    }
                }

                $invoicedMonth = match ($schedule) {
                    self::SCHEDULE_PREV_MONTH_ON_FIRST =>
                        $today->subMonths(1)->firstOfMonth(),

                    self::SCHEDULE_CURRENT_MONTH_ON_LAST =>
                        $today->firstOfMonth(),

                    default => throw new RuntimeException(__d(
                        'bookkeeping',
                        'Invalid schedule value: {0}',
                        $schedule,
                    )),
                };
            }

            $io->out(__d(
                'bookkeeping',
                'Sending invoices for invoiced month: {0}',
                $invoicedMonth->i18nFormat('yyyy-MM'),
            ));

            $io->out(__d(
                'bookkeeping',
                'Run mode: {0}',
                $monthOption !== null ? 'manual' : $schedule,
            ));

            // Resolve accounting profile option
            $accountingProfileOption = $args->getOption('accounting-profile-id');

            if ($accountingProfileOption !== null) {
                $io->out(__d(
                    'bookkeeping',
                    'Using accounting profile ID: {0}',
                    $accountingProfileOption,
                ));
            } else {
                $io->abort(__d(
                    'bookkeeping',
                    'No accounting profile ID provided. Use --accounting-profile-id option to specify it.',
                ));
            }

            // Resolve customer number filters
            $customerMinNumber = $args->getOption('min-customer-number');
            $customerMaxNumber = $args->getOption('max-customer-number');

            $customerMinNumber = is_numeric($customerMinNumber) ? (int)$customerMinNumber : null;
            $customerMaxNumber = is_numeric($customerMaxNumber) ? (int)$customerMaxNumber : null;

            if ($customerMinNumber !== null) {
                $io->out(__d(
                    'bookkeeping',
                    'Filtering customers with customer number greater than or equal to: {0}',
                    $customerMinNumber,
                ));
            }

            if ($customerMaxNumber !== null) {
                $io->out(__d(
                    'bookkeeping',
                    'Filtering customers with customer number less than or equal to: {0}',
                    $customerMaxNumber,
                ));
            }

            // Load accounting profile entity
            $accountingProfile = $this->fetchTable(AccountingProfilesTable::class)->get($accountingProfileOption);

            // Generate invoice drafts
            $invoiceGenerator = new InvoiceGenerationService(
                $this->fetchTable(CustomersTable::class),
            );

            $drafts = $invoiceGenerator->generate(
                $invoicedMonth,
                $accountingProfile,
                $customerMinNumber,
                $customerMaxNumber,
            );

            if ($drafts === []) {
                $io->warning(__d(
                    'bookkeeping',
                    'No invoices to send.',
                ));

                return Command::CODE_SUCCESS;
            }

            // Send invoices via provider
            $bookkeeping = new BookkeepingService();

            $bookkeeping->sendInvoices(
                invoices: $drafts,
                invoicedMonth: $invoicedMonth,
                accountingProfile: $accountingProfile,
            );

            $io->success(__d(
                'bookkeeping',
                'Invoices successfully sent to accounting system.',
            ));

            return Command::CODE_SUCCESS;
        } catch (Throwable $e) {
            Log::error('Error when issuing invoices: ' . $e->getMessage());

            $io->error(__d(
                'bookkeeping',
                'Error when issuing invoices: {0}',
                $e->getMessage(),
            ));

            ErrorReport::send(
                __d('bookkeeping', 'Error when issuing invoices'),
                __d('bookkeeping', 'Error when issuing invoices: {0}', $e->getMessage()),
            );

            return Command::CODE_ERROR;
        }
    }
}
