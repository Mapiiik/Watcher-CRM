<?php
declare(strict_types=1);

namespace Bookkeeping\Debtors;

use App\Model\Entity\CustomerLabel;
use App\Model\Table\CustomerLabelsTable;
use App\Model\Table\CustomersTable;
use App\SledovaniTV\ApiClient;
use App\Utility\Strings;
use Bookkeeping\Model\Table\InvoicesTable;
use Cake\Collection\CollectionInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Exception;
use InvalidArgumentException;
use RouterOS\Client;
use RouterOS\Query;
use Settings\Utility\Settings;

class DebtorsProcessor
{
    use LocatorAwareTrait;

    /**
     * @var \Cake\Collection\CollectionInterface<string, \Bookkeeping\Debtors\Debtor>|null
     */
    private static ?CollectionInterface $debtors = null;

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
        self::$debtors = $this->fetchTable(InvoicesTable::class)
            ->find()
            ->contain([
                'Customers' => [
                    'strategy' => 'select',
                    'Contracts' => [
                        'ContractStates',
                    ],
                    'Emails',
                    'Phones',
                ],
            ])
            ->where([
                'Invoices.debt >' => 0,
                'Invoices.customer_id IS NOT NULL',
            ])
            ->orderBy([
                'Invoices.customer_id' => 'ASC',
                'Invoices.creation_date' => 'DESC',
                'Invoices.number' => 'DESC',
            ])
            ->all()
            ->groupBy('customer.id')
            ->map(
                function ($invoices, $_customer_id) {
                    return new Debtor($invoices);
                },
            )
            ->sortBy(
                function (Debtor $debtor) {
                    return $debtor->getTotalDebt();
                },
            );
    }

    /**
     * Get Debtors
     *
     * All debtors, even those who are not overdue.
     *
     * @return \Cake\Collection\CollectionInterface<string, \Bookkeeping\Debtors\Debtor>
     */
    public function getDebtors(): CollectionInterface
    {
        // Load debtors if not already loaded
        if (!isset(self::$debtors)) {
            $this->loadDebtorsFromDatabase();
        }

        // Return debtors
        if (isset(self::$debtors)) {
            return self::$debtors;
        } else {
            throw new Exception(__d('bookkeeping', 'Debtors data is not available.'));
        }
    }

    /**
     * Get Overdue Debtors
     *
     * Debtors who are overdue (ignoring exceptions).
     *
     * @return \Cake\Collection\CollectionInterface<string, \Bookkeeping\Debtors\Debtor>
     */
    public function getOverdueDebtors(): CollectionInterface
    {
        // Return filtered debtors
        return $this
            ->getDebtors()
            ->filter(
                function (Debtor $debtor) {
                    return $debtor->getDueDate() < Date::now()
                        && $debtor->getTotalOverdueDebt() > 0;
                },
            );
    }

    /**
     * Get Filtered Overdue Debtors
     *
     * Debtors who are overdue and do not even meet the set exceptions.
     *
     * @return \Cake\Collection\CollectionInterface<string, \Bookkeeping\Debtors\Debtor>
     */
    public function getFilteredOverdueDebtors(): CollectionInterface
    {
        // Return filtered debtors
        return $this
            ->getDebtors()
            ->filter(
                function (Debtor $debtor) {
                    // virtual day in the past to allow for payment delays
                    $date = Date::now()->subDays($this->allowed_payment_delay);

                    return $debtor->getDueDate() < $date
                        && $debtor->getTotalOverdueDebtForDate($date) > $this->allowed_total_overdue_debt;
                },
            );
    }

    /**
     * Determine whether automatic debtor blocking is enabled.
     *
     * This method checks the global debtor blocking configuration and,
     * optionally, the configuration for a specific blocking service.
     *
     * Behaviour:
     * - If global blocking is disabled, returns false regardless of service.
     * - If no service is specified and global blocking is enabled, returns true.
     * - If a service is specified, returns true only if that service is enabled.
     *
     * Configuration keys:
     * - bookkeeping.debtors.blocking.enabled
     * - bookkeeping.debtors.blocking.services.<service>.enabled
     *
     * @param string|null $service Optional blocking service identifier
     *                             (e.g. "sledovani_tv", "routers").
     *                             If null, only the global blocking flag is evaluated.
     * @return bool True if debtor blocking is enabled for the given context.
     */
    public function isDebtorBlockingEnabled(?string $service = null): bool
    {
        if (!(bool)Settings::get('bookkeeping.debtors.blocking.enabled', false)) {
            return false;
        }

        if ($service === null) {
            return true;
        }

        return (bool)Settings::get("bookkeeping.debtors.blocking.services.$service.enabled", false);
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
        if (!$this->isDebtorBlockingEnabled()) {
            return __d(
                'bookkeeping',
                'Debtor blocking is globally disabled by settings. No external systems were modified.',
            );
        }

        $customerIps = $this->getCustomerIps($id, 'MANUAL ENTRY - ', false);

        $this->addLabel($id);

        $result = '';

        if ($this->isDebtorBlockingEnabled('sledovani_tv')) {
            $result .= $this->updateSledovaniTV(
                ids: [$id],
                block: true,
                clear: false,
            );
        }

        if ($this->isDebtorBlockingEnabled('routers')) {
            $result .= $this->updateRouters(
                ips: $customerIps,
                block: true,
                clear: false,
            );
        }

        return $result;
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
        if (!$this->isDebtorBlockingEnabled()) {
            return __d(
                'bookkeeping',
                'Debtor blocking is globally disabled by settings. No external systems were modified.',
            );
        }

        $customerIps = $this->getCustomerIps($id, 'MANUAL ENTRY - ', false);

        $this->removeLabel($id);

        $result = '';

        if ($this->isDebtorBlockingEnabled('sledovani_tv')) {
            $result .= $this->updateSledovaniTV(
                ids: [$id],
                block: false,
                clear: false,
            );
        }

        if ($this->isDebtorBlockingEnabled('routers')) {
            $result .= $this->updateRouters(
                ips: $customerIps,
                block: false,
                clear: false,
            );
        }

        return $result;
    }

    /**
     * Block Many Debtors
     *
     * @param array<string> $ids Customer IDs.
     * @return string List of performed changes.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function blockMany(array $ids): string
    {
        if (!$this->isDebtorBlockingEnabled()) {
            return __d(
                'bookkeeping',
                'Debtor blocking is globally disabled by settings. No external systems were modified.',
            );
        }

        $customerIps = [];
        foreach ($ids as $id) {
            $customerIps = array_merge_recursive(
                $customerIps,
                $this->getCustomerIps($id, 'MANUAL ENTRY - ', false),
            );

            $this->addLabel($id);
        }

        $result = '';

        if ($this->isDebtorBlockingEnabled('sledovani_tv')) {
            $result .= $this->updateSledovaniTV(
                ids: $ids,
                block: true,
                clear: false,
            );
        }

        if ($this->isDebtorBlockingEnabled('routers')) {
            $result .= $this->updateRouters(
                ips: $customerIps,
                block: true,
                clear: false,
            );
        }

        return $result;
    }

    /**
     * Unblock Many Debtors
     *
     * @param array<string> $ids Customer IDs.
     * @return string List of performed changes.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function unblockMany(array $ids): string
    {
        if (!$this->isDebtorBlockingEnabled()) {
            return __d(
                'bookkeeping',
                'Debtor blocking is globally disabled by settings. No external systems were modified.',
            );
        }

        $customerIps = [];
        foreach ($ids as $id) {
            $customerIps = array_merge_recursive(
                $customerIps,
                $this->getCustomerIps($id, 'MANUAL ENTRY - ', false),
            );

            $this->removeLabel($id);
        }

        $result = '';

        if ($this->isDebtorBlockingEnabled('sledovani_tv')) {
            $result .= $this->updateSledovaniTV(
                ids: $ids,
                block: false,
                clear: false,
            );
        }

        if ($this->isDebtorBlockingEnabled('routers')) {
            $result .= $this->updateRouters(
                ips: $customerIps,
                block: false,
                clear: false,
            );
        }

        return $result;
    }

    /**
     * Automatic Update of Debtor Blocking
     *
     * @return string List of performed changes.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function blockingUpdate(): string
    {
        if (!$this->isDebtorBlockingEnabled()) {
            return __d(
                'bookkeeping',
                'Debtor blocking is globally disabled by settings. No external systems were modified.',
            );
        }

        $start_time = DateTime::now();

        $customerIps = [];
        $customerIds = [];

        foreach ($this->getFilteredOverdueDebtors() as $debtor) {
            $customerIds[] = $debtor->getCustomer()->id;

            $customerIps = array_merge_recursive(
                $customerIps,
                $this->getCustomerIps($debtor->getCustomer()->id),
            );

            $this->addLabel($debtor->getCustomer()->id);
        }

        $this->clearLabel($start_time);

        $result = '';

        if ($this->isDebtorBlockingEnabled('sledovani_tv')) {
            $result .= $this->updateSledovaniTV(
                ids: $customerIds,
                block: true,
                clear: true,
            );
        }

        if ($this->isDebtorBlockingEnabled('routers')) {
            $result .= $this->updateRouters(
                ips: $customerIps,
                block: true,
                clear: true,
            );
        }

        return $result;
    }

    /**
     * Adds a label for the customer
     *
     * @param string $id Customer ID.
     * @psalm-suppress UnusedReturnValue
     */
    private function addLabel(string $id): CustomerLabel|false
    {
        // check if label is configured
        $labelId = env('DEBTORS_BLOCKED_LABEL_ID');

        // check that the label is configured
        if (empty($labelId)) {
            return false;
        }
        /** @var \App\Model\Table\CustomerLabelsTable $customerLabelsTable */
        $customerLabelsTable = $this->fetchTable(CustomerLabelsTable::class);
        /** @var \App\Model\Entity\CustomerLabel $customerLabel */
        $customerLabel = $customerLabelsTable->findOrNewEntity([
            'label_id' => $labelId,
            'customer_id' => $id,
            'contract_id IS' => null,
            'note' => __d('bookkeeping', 'debtor'),
        ]);

        // update modification time
        $customerLabel->modified = DateTime::now();

        return $customerLabelsTable->saveOrFail($customerLabel);
    }

    /**
     * Removes the label for the customer
     *
     * @param string $id Customer ID.
     * @return iterable<\App\Model\Entity\CustomerLabel>|false Entities list
     *   on success, false on failure.
     * @psalm-suppress UnusedReturnValue
     */
    private function removeLabel(string $id): iterable|false
    {
        // check if label is configured
        $labelId = env('DEBTORS_BLOCKED_LABEL_ID');

        // check that the label is configured
        if (empty($labelId)) {
            return false;
        }
        /** @var \App\Model\Table\CustomerLabelsTable $customerLabelsTable */
        $customerLabelsTable = $this->fetchTable(CustomerLabelsTable::class);

        return $customerLabelsTable->deleteMany(
            $customerLabelsTable
                ->find()
                ->where([
                    'label_id' => $labelId,
                    'customer_id' => $id,
                ])
                ->all(),
        );
    }

    /**
     * Removes the label for all customers
     *
     * @param \Cake\I18n\DateTime $older_than Only labels with last modification older than this parameter.
     * @return iterable<\App\Model\Entity\CustomerLabel>|false Entities list
     *   on success, false on failure.
     * @psalm-suppress UnusedReturnValue
     */
    private function clearLabel(DateTime $older_than): iterable|false
    {
        // check if label is configured
        $labelId = env('DEBTORS_BLOCKED_LABEL_ID');

        // check that the label is configured
        if (empty($labelId)) {
            return false;
        }
        /** @var \App\Model\Table\CustomerLabelsTable $customerLabelsTable */
        $customerLabelsTable = $this->fetchTable(CustomerLabelsTable::class);

        return $customerLabelsTable->deleteMany(
            $customerLabelsTable
                ->find()
                ->where([
                    'label_id' => $labelId,
                    'modified <' => $older_than,
                ])
                ->all(),
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
     * @return array<string, mixed> List of IPv4 and IPv6 adresses/networks.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    private function getCustomerIps(?string $id, string $comment_prefix = '', bool $skip_vip = true): array
    {
        $customer = $this->fetchTable(CustomersTable::class)->get($id, contain: [
            'IpAddresses' => [
                'Contracts',
            ],
            'IpNetworks' => [
                'Contracts',
            ],
        ]);

        $ipv4s = [];
        $ipv6s = [];

        // IP addresses
        foreach ($customer->ip_addresses as $ipAddress) {
            // skip VIP contracts
            if ($skip_vip && $ipAddress->contract->vip === true) {
                continue;
            }
            // split address and mask
            [$address] = explode('/', $ipAddress->ip_address);
            // prepare comment
            $comment = $comment_prefix . ($ipAddress->contract->number ?? $customer->number) . ' - ' . $customer->name;
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
        foreach ($customer->ip_networks as $ipNetwork) {
            // skip VIP contracts
            if ($skip_vip && $ipNetwork->contract->vip === true) {
                continue;
            }
            // split address and mask
            [$address, $mask] = explode('/', $ipNetwork->ip_network);
            // prepare comment
            $comment = $comment_prefix . ($ipNetwork->contract->number ?? $customer->number) . ' - ' . $customer->name;
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
     * @param array<string, mixed> $ips List of IPv4 and IPv6 adresses/networks.
     * @param bool $block Defaults to unblock (false) / block (true)
     * @param bool $clear Before the operation, clear the address list on the router. Default (false).
     * @return string List of performed changes
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
                'user' => env('DEBTORS_ROUTERS_USERNAME', 'admin'),
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
                            'bookkeeping',
                            'Removed IPv4 record {0} from router {1}.',
                            $item['address'],
                            $router,
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
                                'bookkeeping',
                                'Removed IPv4 record {0} from router {1}.',
                                $ipv4,
                                $router,
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
                            'bookkeeping',
                            'Added IPv4 record {0} ({1}) to router {2}.',
                            $ipv4,
                            Strings::removeAccents($comment),
                            $router,
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
                            'bookkeeping',
                            'Removed IPv6 record {0} from router {1}.',
                            $item['address'],
                            $router,
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
                                'bookkeeping',
                                'Removed IPv6 record {0} from router {1}.',
                                $ipv6,
                                $router,
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
                            'bookkeeping',
                            'Added IPv6 record {0} ({1}) to router {2}.',
                            $ipv6,
                            Strings::removeAccents($comment),
                            $router,
                        ) . PHP_EOL;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Update SledovaniTV method
     *
     * Input example: ['ipv4' => ['0.0.0.0' => 'comment'], 'ipv6' => ['0::1/128' => 'comment']]
     *
     * @param array<string> $ids List of customer IDs.
     * @param bool $block Defaults to unblock (false) / block (true)
     * @param bool $clear Before the operation, clear the blocks. Default (false).
     * @return string List of performed changes
     */
    private function updateSledovaniTV(array $ids, bool $block = false, bool $clear = false): string
    {
        $tvUsers = ApiClient::getUsers();

        $customers = $this->fetchTable(CustomersTable::class)
            ->find(
                'list',
                keyField: 'id',
                valueField: 'number',
            )
            ->whereInList('id', $ids)
            ->toArray();

        $result = '';

        foreach ($tvUsers as $tvUser) {
            if (in_array($tvUser['partnerid'], $customers)) {
                // block = true and not suspended => block
                if ($block && $tvUser['active'] == 1 && $tvUser['suspended'] == 0) {
                    if (ApiClient::suspendUser($tvUser['id'])) {
                        $result .= __d(
                            'bookkeeping',
                            'SledovaniTV - Suspended user with ID: {0} (partner ID: {1}).',
                            $tvUser['id'],
                            $tvUser['partnerid'],
                        ) . PHP_EOL;
                    }
                }

                // block = false and suspended => unblock
                if (!$block && $tvUser['suspended'] == 1) {
                    if (ApiClient::unsuspendUser($tvUser['id'])) {
                        $result .= __d(
                            'bookkeeping',
                            'SledovaniTV - Unsuspended user with ID: {0} (partner ID: {1}).',
                            $tvUser['id'],
                            $tvUser['partnerid'],
                        ) . PHP_EOL;
                    }
                }
            } elseif ($clear) {
                // suspended and not on the list + clear called => unblock
                if ($tvUser['suspended'] == 1) {
                    if (ApiClient::unsuspendUser($tvUser['id'])) {
                        $result .= __d(
                            'bookkeeping',
                            'SledovaniTV - Unsuspended user with ID: {0} (partner ID: {1}).',
                            $tvUser['id'],
                            $tvUser['partnerid'],
                        ) . PHP_EOL;
                    }
                }
            }
        }

        return $result;
    }
}
