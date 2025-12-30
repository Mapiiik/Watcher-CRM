<?php
declare(strict_types=1);

namespace Bookkeeping\Command;

use App\Model\Table\CustomersTable;
use App\Model\Table\TaxRatesTable;
use Bookkeeping\Service\BookkeepingService;
use Bookkeeping\Service\InvoiceGenerationService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\Date;
use RuntimeException;

/**
 * GenerateInvoices command.
 *
 * Generates invoice drafts and sends them to Eurofaktura / E-racuni.
 *
 * This command acts only as an orchestration layer:
 * - resolves invoiced month
 * - resolves tax rate context
 * - loads invoice drafts
 * - delegates sending to the provider
 *
 * It does NOT:
 * - perform business validation
 * - calculate prices or VAT
 * - interpret accounting rules
 */
class GenerateInvoicesCommand extends Command
{
    /**
     * The name of this command.
     *
     * @var string
     */
    protected string $name = 'generate_invoices';

    /**
     * Get the default command name.
     *
     * @return string
     */
    public static function defaultName(): string
    {
        return 'generate_invoices';
    }

    /**
     * Get the command description.
     *
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Generate and send invoices to Eurofaktura / E-racuni.';
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * Supported options:
     * - --month (-m): Invoiced month in YYYY-MM format
     * - --tax-rate (-t): Tax rate identifier (ID or code)
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
                    'Invoiced month (YYYY-MM). Defaults to current month.',
                ),
            ])
            ->addOption('tax-rate-id', [
                'short' => 't',
                'help' => __d(
                    'bookkeeping',
                    'Tax rate identifier (ID).',
                ),
            ]);
    }

    /**
     * Execute the command.
     *
     * Workflow:
     * 1. Resolve invoiced month
     * 2. Resolve tax rate context
     * 3. Load invoice drafts
     * 4. Delegate sending to EurofakturaProvider
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

            // Resolve tax rate option
            $taxRateOption = $args->getOption('tax-rate-id');

            $io->out(__d(
                'bookkeeping',
                'Sending invoices for invoiced month: {0}',
                $invoicedMonth->i18nFormat('yyyy-MM'),
            ));

            if ($taxRateOption !== null) {
                $io->out(__d(
                    'bookkeeping',
                    'Using tax rate ID: {0}',
                    $taxRateOption,
                ));
            } else {
                $io->abort(__d(
                    'bookkeeping',
                    'No tax rate ID provided. Use --tax-rate-id option to specify it.',
                ));
            }

            // Load tax rate entity
            $taxRate = $this->fetchTable(TaxRatesTable::class)->get($taxRateOption);

            // Generate invoice drafts
            $invoiceGenerator = new InvoiceGenerationService(
                $this->fetchTable(CustomersTable::class),
            );

            $drafts = $invoiceGenerator->generate(
                $invoicedMonth,
                $taxRate,
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
                taxRate: $taxRate,
            );

            $io->success(__d(
                'bookkeeping',
                'Invoices successfully sent to Eurofaktura.',
            ));

            return Command::CODE_SUCCESS;
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::CODE_ERROR;
        }
    }
}
