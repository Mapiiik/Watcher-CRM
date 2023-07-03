<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Controller;

use RouterOS\Client;
use RouterOS\Query;

/**
 * Invoices Controller
 *
 * @property \BookkeepingPohoda\Model\Table\InvoicesTable $Invoices
 * @method \BookkeepingPohoda\Model\Entity\Invoice[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class DebtorsController extends AppController
{
    /**
     * Unblock method
     *
     * @param string|null $id Customer id.
     * @return \Cake\Http\Response|null|void Redirects always to customer view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function block(?string $id = null)
    {
        $this->request->allowMethod(['post']);

        $result = $this->updateRouters($id, true);

        $this->Flash->success(
            '<strong>' . __d('bookkeeping_pohoda', 'Routers updated.') . '</strong><br>'
                . ($result ? nl2br($result) : __d('bookkeeping_pohoda', 'Nothing has changed.')),
            ['escape' => false]
        );

        return $this->redirect($this->referer([
            'plugin' => null,
            'controller' => 'Customers',
            'action' => 'view',
            $id,
        ]));
    }

    /**
     * Unblock method
     *
     * @param string|null $id Customer id.
     * @return \Cake\Http\Response|null|void Redirects always to customer view.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function unblock(?string $id = null)
    {
        $this->request->allowMethod(['post']);

        $result = $this->updateRouters($id, false);

        $this->Flash->success(
            '<strong>' . __d('bookkeeping_pohoda', 'Routers updated.') . '</strong><br>'
                . ($result ? nl2br($result) : __d('bookkeeping_pohoda', 'Nothing has changed.')),
            ['escape' => false]
        );

        return $this->redirect($this->referer([
            'plugin' => null,
            'controller' => 'Customers',
            'action' => 'view',
            $id,
        ]));
    }

    /**
     * Update routers method
     *
     * @param string|null $id Customer id.
     * @param bool $block Defaults to unblock (false) / block (true)
     * @return string List of performed changes
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function updateRouters(?string $id = null, bool $block = false)
    {
        $result = '';

        /** @var \App\Model\Entity\Customer $customer */
        $customer = $this->fetchTable('Customers')->get($id, [
            'contain' => [
                'Ips' => ['Contracts'],
                'IpNetworks' => ['Contracts'],
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
                            addslashes($this->removeAccents(
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
                            addslashes($this->removeAccents(
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
