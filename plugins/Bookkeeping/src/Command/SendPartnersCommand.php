<?php
declare(strict_types=1);

namespace Bookkeeping\Command;

use App\Model\Entity\Customer;
use App\Model\Table\CustomersTable;
use Bookkeeping\Service\BookkeepingService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
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
     * 1. Load customers
     * 2. Delegate sending to provider
     *
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        try {
            $customerId = $args->getOption('customer-id');
            $customerMinNumber = $args->getOption('min-customer-number');
            $customerMaxNumber = $args->getOption('max-customer-number');

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

                /** @var \Cake\Collection\CollectionInterface<array-key, \App\Model\Entity\Customer> $customers */
                $customers = $customersTable->find()
                    ->contain([
                        'Addresses.Countries',
                        'Emails',
                        'Phones',
                    ])
                    ->orderBy(['Customers.nid' => 'ASC'])
                    ->all();

                if (is_numeric($customerMinNumber)) {
                    $io->out(__d(
                        'bookkeeping',
                        'Filtering customers with customer number greater than or equal to: {0}',
                        $customerMinNumber,
                    ));

                    $customers = $customers->filter(function (Customer $customer) use ($customerMinNumber): bool {
                        return (int)$customer->number >= (int)$customerMinNumber;
                    });
                }

                if (is_numeric($customerMaxNumber)) {
                    $io->out(__d(
                        'bookkeeping',
                        'Filtering customers with customer number less than or equal to: {0}',
                        $customerMaxNumber,
                    ));

                    $customers = $customers->filter(function (Customer $customer) use ($customerMaxNumber): bool {
                        return (int)$customer->number <= (int)$customerMaxNumber;
                    });
                }

                $customers = $customers->toList();
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
            $bookkeeping->sendPartners(array_values($customers));

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

            // try to send a notification of the problem to mail (if it fails it will crash)
            $errorMailer = new Mailer('default');

            foreach (explode(' ', (string)env('REPORT_EMAILS')) as $email) {
                $errorMailer->addTo($email);
            }

            $errorMailer->setSubject(__d(
                'bookkeeping',
                'Error when sending partners',
            ));

            $errorMailer->deliver(__d(
                'bookkeeping',
                'Error when sending partners: {0}',
                $e->getMessage(),
            ));

            unset($errorMailer);

            return Command::CODE_ERROR;
        }
    }
}
