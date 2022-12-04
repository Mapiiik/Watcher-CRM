<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\ApiClient;
use Cake\Collection\CollectionInterface;
use Cake\I18n\FrozenDate;
use Cake\ORM\Entity;

/**
 * RemovedIpNetwork Entity
 *
 * @property \Cake\I18n\FrozenTime|null $created
 * @property int|null $created_by
 * @property \CakeDC\Users\Model\Entity\User|null $creator
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $modified_by
 * @property \CakeDC\Users\Model\Entity\User|null $modifier
 * @property \Cake\I18n\FrozenTime|null $removed
 * @property \CakeDC\Users\Model\Entity\User|null $remover
 * @property int|null $removed_by
 * @property int $id
 * @property int $customer_id
 * @property int $contract_id
 * @property string $ip_network
 * @property int $type_of_use
 * @property string|null $note
 * @property string $style
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Contract $contract
 * @property \Cake\Collection\CollectionInterface|null $ip_address_ranges
 */
class RemovedIpNetwork extends Entity
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
    protected $_accessible = [
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'removed' => true,
        'removed_by' => true,
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
        $style = 'color: darkgray; text-decoration: line-through;';

        if (isset($this->contract->style)) {
            $style .= ' ' . $this->contract->style;
        }

        return $style;
    }

    /**
     * getter for IP address ranges (try to load via ApiClient)
     *
     * @return \Cake\Collection\CollectionInterface|null
     */
    protected function _getIpAddressRanges(): ?CollectionInterface
    {
        $ipAddressRanges = ApiClient::getIpAddressRangesForIp($this->ip_network);

        if ($ipAddressRanges) {
            return $ipAddressRanges;
        }

        return null;
    }
}
