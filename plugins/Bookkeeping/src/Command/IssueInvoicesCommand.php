<?php
declare(strict_types=1);

namespace Bookkeeping\Command;

use App\Model\Table\AccountingProfilesTable;
use App\Model\Table\CustomersTable;
use Bookkeeping\Service\BookkeepingService;
use Bookkeeping\Service\InvoiceGenerationService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\Date;
use RuntimeException;

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
    /**
     * The name of this command.
     *
     * @var string
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
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription(static::getDescription())
            ->addOption('month', [
                'short' => 'm',
                'help' => __d(
                    'bookkeeping',
                    'Invoiced month (YYYY-MM). Defaults to previous month.',
                ),
            ])
            ->addOption('accounting-profile-id', [
                'short' => 't',
                'help' => __d(
                    'bookkeeping',
                    'Accounting profile identifier (ID).',
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

            $invoicedMonth = $monthOption !== null
                ? new Date($monthOption . '-01')
                : Date::now()->subMonths(1)->firstOfMonth();

            // Resolve accounting profile option
            $accountingProfileOption = $args->getOption('accounting-profile-id');

            $io->out(__d(
                'bookkeeping',
                'Sending invoices for invoiced month: {0}',
                $invoicedMonth->i18nFormat('yyyy-MM'),
            ));

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

            // Load accounting profile entity
            $accountingProfile = $this->fetchTable(AccountingProfilesTable::class)->get($accountingProfileOption);

            // Generate invoice drafts
            $invoiceGenerator = new InvoiceGenerationService(
                $this->fetchTable(CustomersTable::class),
            );

            $drafts = $invoiceGenerator->generate(
                $invoicedMonth,
                $accountingProfile,
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
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::CODE_ERROR;
        }
    }
}
