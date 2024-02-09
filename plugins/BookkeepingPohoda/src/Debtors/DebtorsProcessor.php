<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Debtors;

use App\Strings;
use Cake\Collection\CollectionInterface;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use RouterOS\Client;
use RouterOS\Query;

class DebtorsProcessor
{
    use LocatorAwareTrait;

    private static CollectionInterface $debtors;

    private int $allowed_payment_delay;
    private float $allowed_total_overdue_debt;

    /**
     * Constructor
     */
    public function __construct(
        int $allowed_payment_delay = 0,
        float $allowed_total_overdue_debt = 0,
    ) {
        $this->allowed_payment_delay = $allowed_payment_delay;
        $this->allowed_total_overdue_debt = $allowed_total_overdue_debt;
    }

    /**
     * Load Deptors from Database
     *
     * @return void
     */
    private function loadDeptorsFromDatabase(): void
    {
        self::$debtors = $this->fetchTable('BookkeepingPohoda.Invoices')
            ->find()
            ->contain([
                'Customers' => [
                    'strategy' => 'select',
                    'Emails',
                    'Phones',
                ],
            ])
            ->where([
                'Invoices.debt >' => 0,
            ])
            ->orderBy([
                'Invoices.customer_id' => 'ASC',
                'Invoices.creation_date' => 'DESC',
                'Invoices.number' => 'DESC',
            ])
            ->all()
            ->groupBy('customer.id')
            ->map(
                function ($invoices, $customer_id) {
                    return new Debtor($invoices);
                }
            )
            ->sortBy(
                function (Debtor $debtor) {
                    return $debtor->getTotalDebt();
                }
            );
    }

    /**
     * Get Deptors
     *
     * @return \Cake\Collection\CollectionInterface|iterable<\BookkeepingPohoda\Debtors\Debtor>
     */
    public function getDeptors(): CollectionInterface|iterable
    {
        // Load deptors if not already loaded
        if (!isset(self::$debtors)) {
            $this->loadDeptorsFromDatabase();
        }

        // Return filtered deptors
        return self::$debtors
            ->filter(
                function (Debtor $debtor) {
                    return $debtor->getDueDate() < Date::now()->subDays($this->allowed_payment_delay)
                        && $debtor->getTotalOverdueDebt() > $this->allowed_total_overdue_debt;
                }
            );
    }

    /**
     * Block Deptor
     *
     * @param string|null $id Customer id.
     * @return string List of performed changes
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function block(?string $id): string
    {
        return $this->updateRouters($id, true);
    }

    /**
     * Unblock Deptor
     *
     * @param string|null $id Customer id.
     * @return string List of performed changes
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function unblock(?string $id): string
    {
        return $this->updateRouters($id, false);
    }

    /**
     * Update routers method
     *
     * @param string|null $id Customer id.
     * @param bool $block Defaults to unblock (false) / block (true)
     * @return string List of performed changes
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    private function updateRouters(?string $id = null, bool $block = false): string
    {
        $result = '';

        /** @var \App\Model\Entity\Customer $customer */
        $customer = $this->fetchTable('Customers')->get($id, contain: [
            'IpNetworks' => [
                'Contracts',
            ],
            'Ips' => [
                'Contracts',
            ],
        ]);

        $ipv4s = [];
        $ipv6s = [];

        foreach ($customer->ips as $ip) {
            [$address] = explode('/', $ip->ip);

            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ipv4s[] = $address;
            }
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $ipv6s[] = $address;
            }
        }
        foreach ($customer->ip_networks as $ip_network) {
            [$address, $mask] = explode('/', $ip_network->ip_network);

            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ipv4s[] = $address . '/' . $mask;
            }
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $ipv6s[] = $address . '/' . $mask;
            }
        }

        $routers = explode(' ', env('DEBTORS_ROUTERS_IP_ADDRESSES', ''));
        foreach ($routers as $router) {
            $client = new Client([
                'host' => $router,
                'user' => env('DEBTORS_ROUTERS_USERNAME', ''),
                'pass' => env('DEBTORS_ROUTERS_PASSWORD', ''),
            ]);

            foreach ($ipv4s as $ipv4) {
                $query = new Query('/ip/firewall/address-list/print');
                $query
                    ->where('address', $ipv4)
                    ->where('list', env('DEBTORS_ADDRESS_LIST', ''))
                    ->equal('.proplist', '.id');

                $response = $client->query($query)->read();

                foreach ($response as $item) {
                    $query = new Query('/ip/firewall/address-list/remove');
                    $query->equal('.id', $item['.id']);

                    $response = $client->query($query)->read();

                    // check if no error message
                    if (empty($response)) {
                        $result .= __d(
                            'bookkeeping_pohoda',
                            'Removed IPv4 record {0} from {1}.',
                            $ipv4,
                            $router
                        ) . PHP_EOL;
                    }
                }

                if ($block) {
                    $query = new Query('/ip/firewall/address-list/add');
                    $query
                        ->equal('address', $ipv4)
                        ->equal('list', env('DEBTORS_ADDRESS_LIST', ''))
                        ->equal(
                            'comment',
                            addslashes(Strings::removeAccents(
                                'MANUAL ENTRY - ' . $customer->number . ' - ' . $customer->name
                            ))
                        );

                    $response = $client->query($query)->read();

                    // check if added
                    if (isset($response['after']['ret'])) {
                        $result .= __d(
                            'bookkeeping_pohoda',
                            'Added IPv4 record {0} to {1}.',
                            $ipv4,
                            $router
                        ) . PHP_EOL;
                    }
                }
            }
            foreach ($ipv6s as $ipv6) {
                $query = new Query('/ipv6/firewall/address-list/print');
                $query
                    ->where('address', $ipv6)
                    ->where('list', env('DEBTORS_ADDRESS_LIST', ''))
                    ->equal('.proplist', '.id');

                $response = $client->query($query)->read();

                foreach ($response as $item) {
                    $query = new Query('/ipv6/firewall/address-list/remove');
                    $query->equal('.id', $item['.id']);

                    $response = $client->query($query)->read();

                    // check if no error message
                    if (empty($response)) {
                        $result .= __d(
                            'bookkeeping_pohoda',
                            'Removed IPv6 record {0} from {1}.',
                            $ipv6,
                            $router
                        ) . PHP_EOL;
                    }
                }

                if ($block) {
                    $query = new Query('/ipv6/firewall/address-list/add');
                    $query
                        ->equal('address', $ipv6)
                        ->equal('list', env('DEBTORS_ADDRESS_LIST', ''))
                        ->equal(
                            'comment',
                            addslashes(Strings::removeAccents(
                                'MANUAL ENTRY - ' . $customer->number . ' - ' . $customer->name
                            ))
                        );

                    $response = $client->query($query)->read();

                    // check if added
                    if (isset($response['after']['ret'])) {
                        $result .= __d(
                            'bookkeeping_pohoda',
                            'Added IPv6 record {0} to {1}.',
                            $ipv6,
                            $router
                        ) . PHP_EOL;
                    }
                }
            }
        }

        return $result;
    }
}
