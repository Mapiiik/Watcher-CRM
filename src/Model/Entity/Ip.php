<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\ApiClient;
use Cake\Collection\CollectionInterface;
use Cake\I18n\FrozenDate;
use Cake\ORM\Entity;

/**
 * Ip Entity
 *
 * @property \Cake\I18n\FrozenTime|null $created
 * @property int|null $created_by
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $modified_by
 * @property string $ip
 * @property int $customer_id
 * @property string|null $note
 * @property int $contract_id
 * @property int $id
 * @property int $type_of_use
 * @property string $style
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Contract $contract
 * @property \Cake\Collection\CollectionInterface|null $routeros_devices
 * @property \Cake\Collection\CollectionInterface|null $ip_address_ranges
 */
class Ip extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<bool>
     */
    protected $_accessible = [
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'ip' => true,
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
        $now = new FrozenDate();

        if (isset($this->contract->valid_until) && $this->contract->valid_until < $now) {
            $style = 'background-color: #ffaaaa;';
        }

        return $style;
    }

    /**
     * getter for RouterOS devices (try to load via ApiClient)
     *
     * @return \Cake\Collection\CollectionInterface|null
     */
    protected function _getRouterosDevices(): ?CollectionInterface
    {
        $routerosDevices = ApiClient::getRouterosDevicesForIp($this->ip);

        if ($routerosDevices) {
            return $routerosDevices;
        }

        return null;
    }

    /**
     * getter for IP address ranges (try to load via ApiClient)
     *
     * @return \Cake\Collection\CollectionInterface|null
     */
    protected function _getIpAddressRanges(): ?CollectionInterface
    {
        $ipAddressRanges = ApiClient::getIpAddressRangesForIp($this->ip);

        if ($ipAddressRanges) {
            return $ipAddressRanges;
        }

        return null;
    }
}
