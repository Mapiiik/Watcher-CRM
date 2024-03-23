<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\ApiClient;
use App\Controller\AppController;
use App\View\AjaxView;
use Cake\View\JsonView;

/**
 * IpAddresses Controller
 *
 * @property \App\Model\Table\IpAddressesTable $IpAddresses
 * @method \App\Model\Entity\Customer[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class IpAddressesController extends AppController
{
    /**
     * Returns supported output types
     */
    public function viewClasses(): array
    {
        return [JsonView::class, AjaxView::class];
    }

    /**
     * RouterOS Devices method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function routerosDevices()
    {
        $ip_address = $this->getRequest()->getParam('ip_address');
        $routerosDevices = ApiClient::getRouterosDevicesForIp($ip_address);

        $this->set('routerosDevices', $routerosDevices);
        $this->viewBuilder()->setOption('serialize', ['routerosDevices']);
    }

    /**
     * IP Address Ranges method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function ipAddressRanges()
    {
        $ip_address = $this->getRequest()->getParam('ip_address');
        $ipAddressRanges = ApiClient::getIpAddressRangesForIp($ip_address);

        $this->set('ipAddressRanges', $ipAddressRanges);
        $this->viewBuilder()->setOption('serialize', ['ipAddressRanges']);
    }
}
