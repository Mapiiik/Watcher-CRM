<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Http\Answer;
use App\NMS\ApiClient as NMSApiClient;

/**
 * IpAddress Entity
 *
 * @property string $ip_address
 * @property string $customer_id
 * @property string|null $note
 * @property string $contract_id
 * @property string $id
 * @property int $nid
 * @property \App\Model\Enum\IpAddressTypeOfUse $type_of_use
 * @property string $style
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Contract $contract
 * @property \App\Http\Answer<\Cake\Collection\CollectionInterface<int, \App\NMS\Dto\RouterosDevice>> $routeros_devices
 * @property \App\Http\Answer<\Cake\Collection\CollectionInterface<int, \App\NMS\Dto\IpAddressRange>> $ip_address_ranges
 */
class IpAddress extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'ip_address' => true,
        'customer_id' => true,
        'note' => true,
        'contract_id' => true,
        'type_of_use' => true,
        'customer' => true,
        'contract' => true,
    ];

    /**
     * getter for style
     *
     * @return string
     */
    protected function _getStyle(): string
    {
        $style = '';

        if (isset($this->contract->style)) {
            $style .= ' ' . $this->contract->style;
        }

        return $style;
    }

    /**
     * getter for RouterOS devices (try to load via ApiClient)
     *
     * @return \App\Http\Answer<\Cake\Collection\CollectionInterface<int, \App\NMS\Dto\RouterosDevice>>
     */
    protected function _getRouterosDevices(): Answer
    {
        return NMSApiClient::getRouterosDevicesForIp($this->ip_address);
    }

    /**
     * getter for IP address ranges (try to load via ApiClient)
     *
     * @return \App\Http\Answer<\Cake\Collection\CollectionInterface<int, \App\NMS\Dto\IpAddressRange>>
     */
    protected function _getIpAddressRanges(): Answer
    {
        return NMSApiClient::getIpAddressRangesForIp($this->ip_address);
    }
}
