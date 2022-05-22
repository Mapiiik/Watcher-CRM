<?php
declare(strict_types=1);

namespace BookkeepingPohoda\Controller;

use Exception;
use Mapiiik\RouterosAPI;

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
    public function block($id = null)
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
    public function unblock($id = null)
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
    public function updateRouters($id = null, $block = false)
    {
        $result = '';

        $customer = $this->fetchTable('Customers')->get($id, [
            'contain' => [
                'Ips' => ['Contracts'],
                'IpNetworks' => ['Contracts'],
            ],
        ]);

        $api = new RouterosAPI();
        $api->debug = false;

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
            $api->connect($router, env('DEBTORS_ROUTERS_USERNAME', ''), env('DEBTORS_ROUTERS_PASSWORD', ''));
            if (!$api->connected) {
                throw new Exception('Could not connect to ' . $router);
            }

            foreach ($ipv4s as $ipv4) {
                $api->write('/ip/firewall/address-list/print', false);
                $api->write('?address=' . $ipv4, false);
                $api->write('?list=' . env('DEBTORS_ADDRESS_LIST', ''), false);
                $api->write('=.proplist=.id');
                $reply = $api->read();

                foreach ($reply as $item) {
                    $api->write('/ip/firewall/address-list/remove', false);
                    $api->write('=.id=' . $item['.id']);
                    $reply = $api->read();
                    if (empty($reply)) {
                        $result .= __d(
                            'bookkeeping_pohoda',
                            'Removed IPv4 record {0} from {1}.',
                            $ipv4,
                            $router
                        ) . PHP_EOL;
                    }
                }

                if ($block) {
                    $api->write('/ip/firewall/address-list/add', false);
                    $api->write('=address=' . $ipv4, false);
                    $api->write('=list=' . env('DEBTORS_ADDRESS_LIST', ''), false);
                    $api->write('=comment=' . addslashes($this->removeAccents(
                        'MANUAL ENTRY - ' . $customer->number . ' - ' . $customer->name
                    )));
                    $reply = $api->read();
                    /* @phpstan-ignore-next-line */
                    if (is_string($reply)) {
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
                $api->write('/ipv6/firewall/address-list/print', false);
                $api->write('?address=' . $ipv6, false);
                $api->write('?list=' . env('DEBTORS_ADDRESS_LIST', ''), false);
                $api->write('=.proplist=.id');
                $reply = $api->read();

                foreach ($reply as $item) {
                    $api->write('/ipv6/firewall/address-list/remove', false);
                    $api->write('=.id=' . $item['.id']);
                    $reply = $api->read();
                    if (empty($reply)) {
                        $result .= __d(
                            'bookkeeping_pohoda',
                            'Removed IPv6 record {0} from {1}.',
                            $ipv6,
                            $router
                        ) . PHP_EOL;
                    }
                }

                if ($block) {
                    $api->write('/ipv6/firewall/address-list/add', false);
                    $api->write('=address=' . $ipv6, false);
                    $api->write('=list=' . env('DEBTORS_ADDRESS_LIST', ''), false);
                    $api->write('=comment=' . addslashes($this->removeAccents(
                        'MANUAL ENTRY - ' . $customer->number . ' - ' . $customer->name
                    )));
                    $reply = $api->read();
                    /* @phpstan-ignore-next-line */
                    if (is_string($reply)) {
                        $result .= __d(
                            'bookkeeping_pohoda',
                            'Added IPv6 record {0} to {1}.',
                            $ipv6,
                            $router
                        ) . PHP_EOL;
                    }
                }
            }
            $api->disconnect();
        }

        return $result;
    }
}
