<?php
declare(strict_types=1);

namespace Bookkeeping\Command;

use App\Model\Entity\Customer;
use App\Model\Table\CustomersTable;
use App\Service\ErrorReport;
use Bookkeeping\Service\BookkeepingService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;
use Throwable;

/**
 * SendPartners command.
 *
 * Sends customers (partners) to the accounting system
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
        return 'Send customers (partners) to accounting system.';
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * Supported options:
     * - --customer-id (-c): Send only a single customer by ID
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription(static::getDescription())
            ->addOption('customer-id', [
                'short' => 'c',
                'help' => __d(
                    'bookkeeping',
                    'Send only a single customer by ID.',
                ),
            ])
            ->addOption('min-customer-number', [
                'help' => __d(
                    'bookkeeping',
                    'Send only customers with customer number greater than or equal to this value. '
                    . 'The lowest number set for the accounting system still applies.',
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
     * 1. Load customers
     * 2. Delegate sending to provider
     *
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        try {
            $customerId = $args->getOption('customer-id');
            $customerMaxNumber = $args->getOption('max-customer-number');

            $bookkeeping = new BookkeepingService();

            // below the number the partner cards are ours from, a run would set a second card beside
            // the one made by hand - so the option may only raise it, for one customer as for all
            $customerMinNumber = $bookkeeping->minCustomerNumber();
            $askedMinNumber = $args->getOption('min-customer-number');
            if (is_numeric($askedMinNumber)) {
                $customerMinNumber = max($customerMinNumber, (int)$askedMinNumber);
            }

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
                        contain: BookkeepingService::PARTNER_ASSOCIATIONS,
                    ),
                ];
            } else {
                $io->out(__d(
                    'bookkeeping',
                    'Sending all customers.',
                ));

                /** @var list<\App\Model\Entity\Customer> $customers */
                $customers = $customersTable->find()
                    ->contain(BookkeepingService::PARTNER_ASSOCIATIONS)
                    ->orderBy(['Customers.nid' => 'ASC'])
                    ->all()
                    ->toList();
            }

            if ($customerMinNumber > 0) {
                $io->out(__d(
                    'bookkeeping',
                    'Filtering customers with customer number greater than or equal to: {0}',
                    $customerMinNumber,
                ));

                $customers = array_filter($customers, function (Customer $customer) use ($customerMinNumber): bool {
                    return (int)$customer->number >= $customerMinNumber;
                });
            }

            if (is_numeric($customerMaxNumber)) {
                $io->out(__d(
                    'bookkeeping',
                    'Filtering customers with customer number less than or equal to: {0}',
                    $customerMaxNumber,
                ));

                $customers = array_filter($customers, function (Customer $customer) use ($customerMaxNumber): bool {
                    return (int)$customer->number <= (int)$customerMaxNumber;
                });
            }

            // a customer somebody has unticked is left where they are: the accounting system holds
            // a partner card for them that is not ours to write, and the run has to walk past it
            // rather than die on it
            $customers = array_values(array_filter(
                $customers,
                function (Customer $customer) use ($io): bool {
                    if ($customer->sync_to_accounting) {
                        return true;
                    }

                    Log::info(
                        'Partner sync skipped for customer ' . $customer->number
                        . ': synchronization to the accounting system is turned off.',
                    );

                    $io->info(__d(
                        'bookkeeping',
                        'Skipping customer {0}: synchronization to the accounting system is turned off.',
                        $customer->number,
                    ));

                    return false;
                },
            ));

            if ($customers === []) {
                $io->warning(__d(
                    'bookkeeping',
                    'No customers to send.',
                ));

                return Command::CODE_SUCCESS;
            }

            // Delegate sending to provider
            $bookkeeping->sendPartners($customers);

            $io->success(__d(
                'bookkeeping',
                'Customers successfully sent to accounting system.',
            ));

            return Command::CODE_SUCCESS;
        } catch (Throwable $e) {
            Log::error('Error when sending partners: ' . $e->getMessage());

            $io->error(__d(
                'bookkeeping',
                'Error when sending partners: {0}',
                $e->getMessage(),
            ));

            ErrorReport::send(
                __d('bookkeeping', 'Error when sending partners'),
                __d('bookkeeping', 'Error when sending partners: {0}', $e->getMessage()),
            );

            return Command::CODE_ERROR;
        }
    }
}
