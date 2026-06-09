<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\NMS\ApiClient as NMSApiClient;
use Cake\Collection\CollectionInterface;

/**
 * IpNetwork Entity
 *
 * @property string $id
 * @property int $nid
 * @property string $customer_id
 * @property string $contract_id
 * @property string $ip_network
 * @property \App\Model\Enum\IpNetworkTypeOfUse $type_of_use
 * @property string|null $note
 * @property string $style
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Contract $contract
 * @property \Cake\Collection\CollectionInterface<int, mixed>|null $ip_address_ranges
 */
class IpNetwork extends AppEntity
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
        'customer_id' => true,
        'contract_id' => true,
        'ip_network' => true,
        'type_of_use' => true,
        'note' => true,
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
     * getter for IP address ranges (try to load via ApiClient)
     *
     * @return \Cake\Collection\CollectionInterface<int, mixed>|null
     */
    protected function _getIpAddressRanges(): ?CollectionInterface
    {
        return NMSApiClient::getIpAddressRangesForIp($this->ip_network);
    }
}
