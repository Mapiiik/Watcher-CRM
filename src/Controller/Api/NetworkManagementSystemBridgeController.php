<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use App\NMS\ApiClient as NMSApiClient;
use App\View\AjaxView;
use Cake\View\JsonView;
use Override;

/**
 * Network Management System Bridge Controller
 */
class NetworkManagementSystemBridgeController extends AppController
{
    /**
     * Returns supported output types
     */
    #[Override]
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
        $routerosDevices = NMSApiClient::getRouterosDevicesForIp($ip_address);

        $this->set('routerosDevices', $routerosDevices);
        $this->viewBuilder()->setOption('serialize', ['routerosDevices']);
    }

    /**
     * Access Points method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function accessPoints()
    {
        $this->routerosDevices();
    }

    /**
     * IP Address Ranges method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function ipAddressRanges()
    {
        $ip_network = $this->getRequest()->getParam('ip_network');
        $ip_network = strtr($ip_network, ['-mask-' => '/']);
        $ipAddressRanges = NMSApiClient::getIpAddressRangesForIp($ip_network);

        $this->set('ipAddressRanges', $ipAddressRanges);
        $this->viewBuilder()->setOption('serialize', ['ipAddressRanges']);
    }
}
