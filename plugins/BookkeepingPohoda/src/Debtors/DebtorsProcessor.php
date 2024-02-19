<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Debtors;

use App\Strings;
use Cake\Collection\CollectionInterface;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use InvalidArgumentException;
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
     * Load Debtors from Database
     *
     * @return void
     */
    private function loadDebtorsFromDatabase(): void
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
     * Get Debtors
     *
     * All debtors, even those who are not overdue.
     *
     * @return \Cake\Collection\CollectionInterface|iterable<\BookkeepingPohoda\Debtors\Debtor>
     */
    public function getDebtors(): CollectionInterface|iterable
    {
        // Load debtors if not already loaded
        if (!isset(self::$debtors)) {
            $this->loadDebtorsFromDatabase();
        }

        // Return debtors
        return self::$debtors;
    }

    /**
     * Get Overdue Debtors
     *
     * Debtors who are overdue and do not even meet the set exceptions.
     *
     * @return \Cake\Collection\CollectionInterface|iterable<\BookkeepingPohoda\Debtors\Debtor>
     */
    public function getOverdueDebtors(): CollectionInterface|iterable
    {
        // Load debtors if not already loaded
        if (!isset(self::$debtors)) {
            $this->loadDebtorsFromDatabase();
        }

        // Return filtered debtors
        return self::$debtors
            ->filter(
                function (Debtor $debtor) {
                    // virtual day in the past to allow for payment delays
                    $date = Date::now()->subDays($this->allowed_payment_delay);

                    return $debtor->getDueDate() < $date
                        && $debtor->getTotalOverdueDebtForDate($date) > $this->allowed_total_overdue_debt;
                }
            );
    }

    /**
     * Block Debtor
     *
     * @param string|null $id Customer ID.
     * @return string List of performed changes.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function block(?string $id): string
    {
        $customer_ips = $this->getCustomerIps($id, 'MANUAL ENTRY - ');

        return $this->updateRouters(
            ips: $customer_ips,
            block: true,
            clear: false,
        );
    }

    /**
     * Unblock Debtor
     *
     * @param string|null $id Customer ID.
     * @return string List of performed changes.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function unblock(?string $id): string
    {
        $customer_ips = $this->getCustomerIps($id, 'MANUAL ENTRY - ');

        return $this->updateRouters(
            ips: $customer_ips,
            block: false,
            clear: false,
        );
    }

    /**
     * Block Many Debtors
     *
     * @param array<string> $ids Customer IDs.
     * @return string List of performed changes.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function blockMany(array $ids): string
    {
        $customer_ips = [];
        foreach ($ids as $id) {
            $customer_ips = array_merge_recursive(
                $customer_ips,
                $this->getCustomerIps($id, 'MANUAL ENTRY - ')
            );
        }

        return $this->updateRouters(
            ips: $customer_ips,
            block: true,
            clear: false,
        );
    }

    /**
     * Unblock Many Debtors
     *
     * @param array<string> $ids Customer IDs.
     * @return string List of performed changes.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function unblockMany(array $ids): string
    {
        $customer_ips = [];
        foreach ($ids as $id) {
            $customer_ips = array_merge_recursive(
                $customer_ips,
                $this->getCustomerIps($id, 'MANUAL ENTRY - ')
            );
        }

        return $this->updateRouters(
            ips: $customer_ips,
            block: false,
            clear: false,
        );
    }

    /**
     * Automatic Update of Debtor Blocking
     *
     * @return string List of performed changes.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function blockingUpdate(): string
    {
        $customer_ips = [];
        foreach ($this->getOverdueDebtors() as $debtor) {
            $customer_ips = array_merge_recursive(
                $customer_ips,
                $this->getCustomerIps($debtor->getCustomer()->id)
            );
        }

        return $this->updateRouters(
            ips: $customer_ips,
            block: true,
            clear: true,
        );
    }

    /**
     * Get Customer IPs
     *
     * Return example: ['ipv4' => ['0.0.0.0' => 'comment'], 'ipv6' => ['0::1/128' => 'comment']]
     *
     * @param string|null $id Customer ID.
     * @param string $comment_prefix IP comment prefix.
     * @param bool $skip_vip Skip VIP contracts.
     * @return array List of IPv4 and IPv6 adresses/networks.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    private function getCustomerIps(?string $id, string $comment_prefix = '', bool $skip_vip = true): array
    {
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

        // IP addresses
        foreach ($customer->ips as $ip) {
            // skip VIP contracts
            if ($skip_vip && $ip->contract->vip === true) {
                continue;
            }
            // split address and mask
            [$address] = explode('/', $ip->ip);
            // prepare comment
            $comment = $comment_prefix . ($ip->contract->number ?? $customer->number) . ' - ' . $customer->name;
            // IPv4
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ipv4s[$address] = $comment;
            }
            // IPv6
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $ipv6s[$address] = $comment;
            }
        }
        // IP networks
        foreach ($customer->ip_networks as $ip_network) {
            // skip VIP contracts
            if ($skip_vip && $ip_network->contract->vip === true) {
                continue;
            }
            // split address and mask
            [$address, $mask] = explode('/', $ip_network->ip_network);
            // prepare comment
            $comment = $comment_prefix . ($ip->contract->number ?? $customer->number) . ' - ' . $customer->name;
            // IPv4
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $ipv4s[$address . '/' . $mask] = $comment;
            }
            // IPv6
            if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $ipv6s[$address . '/' . $mask] = $comment;
            }
        }

        // return resulting array
        return [
            'ipv4' => $ipv4s,
            'ipv6' => $ipv6s,
        ];
    }

    /**
     * Update routers method
     *
     * Input example: ['ipv4' => ['0.0.0.0' => 'comment'], 'ipv6' => ['0::1/128' => 'comment']]
     *
     * @param array $ips List of IPv4 and IPv6 adresses/networks.
     * @param bool $block Defaults to unblock (false) / block (true)
     * @param bool $clear Before the operation, clear the address list on the router. Default (false).
     * @return string List of performed changes
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     * @throws \InvalidArgumentException When incorrect IP addresses input format.
     */
    private function updateRouters(array $ips, bool $block = false, bool $clear = false): string
    {
        if (!isset($ips['ipv4']) || !isset($ips['ipv6']) || !is_array($ips['ipv4']) || !is_array($ips['ipv6'])) {
            throw new InvalidArgumentException('Incorrect IP addresses input format.');
        }
        $result = '';

        $routers = explode(' ', env('DEBTORS_ROUTERS_IP_ADDRESSES', ''));
        foreach ($routers as $router) {
            $client = new Client([
                'host' => $router,
                'user' => env('DEBTORS_ROUTERS_USERNAME', ''),
                'pass' => env('DEBTORS_ROUTERS_PASSWORD', ''),
            ]);

            // process IPv4 firewall address list
            if ($clear) {
                $query = new Query('/ip/firewall/address-list/print');
                $query
                    ->where('list', env('DEBTORS_ADDRESS_LIST', ''))
                    ->equal('.proplist', '.id,address');

                $response = $client->query($query)->read();

                foreach ($response as $item) {
                    $query = new Query('/ip/firewall/address-list/remove');
                    $query->equal('.id', $item['.id']);

                    $response = $client->query($query)->read();

                    // check if no error message
                    if (empty($response)) {
                        $result .= __d(
                            'bookkeeping_pohoda',
                            'Removed IPv4 record {0} from router {1}.',
                            $item['address'],
                            $router
                        ) . PHP_EOL;
                    }
                }
            }

            foreach ($ips['ipv4'] as $ipv4 => $comment) {
                if (!$clear) {
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
                                'Removed IPv4 record {0} from router {1}.',
                                $ipv4,
                                $router
                            ) . PHP_EOL;
                        }
                    }
                }

                if ($block) {
                    $query = new Query('/ip/firewall/address-list/add');
                    $query
                        ->equal('address', $ipv4)
                        ->equal('list', env('DEBTORS_ADDRESS_LIST', ''))
                        ->equal('comment', addslashes(Strings::removeAccents($comment)));

                    $response = $client->query($query)->read();

                    // check if added
                    if (isset($response['after']['ret'])) {
                        $result .= __d(
                            'bookkeeping_pohoda',
                            'Added IPv4 record {0} ({1}) to router {2}.',
                            $ipv4,
                            Strings::removeAccents($comment),
                            $router
                        ) . PHP_EOL;
                    }
                }
            }

            // process IPv6 firewall address list
            if ($clear) {
                $query = new Query('/ipv6/firewall/address-list/print');
                $query
                    ->where('list', env('DEBTORS_ADDRESS_LIST', ''))
                    ->equal('.proplist', '.id,address');

                $response = $client->query($query)->read();

                foreach ($response as $item) {
                    $query = new Query('/ipv6/firewall/address-list/remove');
                    $query->equal('.id', $item['.id']);

                    $response = $client->query($query)->read();

                    // check if no error message
                    if (empty($response)) {
                        $result .= __d(
                            'bookkeeping_pohoda',
                            'Removed IPv6 record {0} from router {1}.',
                            $item['address'],
                            $router
                        ) . PHP_EOL;
                    }
                }
            }

            foreach ($ips['ipv6'] as $ipv6 => $comment) {
                if (!$clear) {
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
                                'Removed IPv6 record {0} from router {1}.',
                                $ipv6,
                                $router
                            ) . PHP_EOL;
                        }
                    }
                }

                if ($block) {
                    $query = new Query('/ipv6/firewall/address-list/add');
                    $query
                        ->equal('address', $ipv6)
                        ->equal('list', env('DEBTORS_ADDRESS_LIST', ''))
                        ->equal('comment', addslashes(Strings::removeAccents($comment)));

                    $response = $client->query($query)->read();

                    // check if added
                    if (isset($response['after']['ret'])) {
                        $result .= __d(
                            'bookkeeping_pohoda',
                            'Added IPv6 record {0} ({1}) to router {2}.',
                            $ipv6,
                            Strings::removeAccents($comment),
                            $router
                        ) . PHP_EOL;
                    }
                }
            }
        }

        return $result;
    }
}
