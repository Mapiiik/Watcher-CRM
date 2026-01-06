<?php
declare(strict_types=1);

namespace Bookkeeping\Command;

use App\Model\Table\CustomersTable;
use Bookkeeping\Service\BookkeepingService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use RuntimeException;

/**
 * SendPartners command.
 *
 * Sends customers (partners) to the accounting system
 * (Eurofaktura / E-racuni).
 *
 * This command acts only as an orchestration layer:
 * - loads customers
 * - delegates sending to the provider
 *
 * It does NOT:
 * - perform business validation
 * - interpret customer data
 * - resolve duplicates or conflicts
 */
class SendPartnersCommand extends Command
{
    /**
     * The name of this command.
     *
     * @var string
     */
    protected string $name = 'send_partners';

    /**
     * Get the default command name.
     *
     * @return string
     */
    public static function defaultName(): string
    {
        return 'send_partners';
    }

    /**
     * Get the command description.
     *
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Send customers (partners) to Eurofaktura / E-racuni.';
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * Supported options:
     * - --customer-id (-c): Send only a single customer by ID
     *
     * @param \Cake\Console\ConsoleOptionParser $parser
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription(static::getDescription())
            ->addOption('customer-id', [
                'short' => 'c',
                'help' => __d(
                    'bookkeeping',
                    'Send only a single customer by ID.',
                ),
            ]);
    }

    /**
     * Execute the command.
     *
     * Workflow:
     * 1. Load customers
     * 2. Delegate sending to provider
     *
     * @param \Cake\Console\Arguments $args
     * @param \Cake\Console\ConsoleIo $io
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        try {
            $customerId = $args->getOption('customer-id');

            /** @var \App\Model\Table\CustomersTable $customersTable */
            $customersTable = $this->fetchTable(CustomersTable::class);

            if ($customerId !== null) {
                $io->out(__d(
                    'bookkeeping',
                    'Sending customer ID: {0}',
                    $customerId,
                ));

                $customers = [
                    $customersTable->get(
                        $customerId,
                        contain: [
                            'Addresses.Countries',
                            'Emails',
                            'Phones',
                        ],
                    ),
                ];
            } else {
                $io->out(__d(
                    'bookkeeping',
                    'Sending all customers.',
                ));

                $customers = $customersTable->find()
                    ->contain([
                        'Addresses.Countries',
                        'Emails',
                        'Phones',
                    ])
                    ->orderBy(['Customers.nid' => 'ASC'])
                    ->all()
                    ->toList();
            }

            if ($customers === []) {
                $io->warning(__d(
                    'bookkeeping',
                    'No customers to send.',
                ));

                return Command::CODE_SUCCESS;
            }

            // Delegate sending to provider
            $bookkeeping = new BookkeepingService();
            $bookkeeping->sendPartners($customers);

            $io->success(__d(
                'bookkeeping',
                'Customers successfully sent to Eurofaktura / E-racuni.',
            ));

            return Command::CODE_SUCCESS;
        } catch (RuntimeException $e) {
            $io->error($e->getMessage());

            return Command::CODE_ERROR;
        }
    }
}
