<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\ApiClient;
use Cake\Collection\CollectionInterface;
use Cake\ORM\Entity;

/**
 * IpNetwork Entity
 *
 * @property \Cake\I18n\DateTime|null $created
 * @property int|null $created_by
 * @property \CakeDC\Users\Model\Entity\User|null $creator
 * @property \Cake\I18n\DateTime|null $modified
 * @property int|null $modified_by
 * @property \CakeDC\Users\Model\Entity\User|null $modifier
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
class IpNetwork extends Entity
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

    /**
     * Get IP network type of use options method
     *
     * @return array<int, string>
     */
    public function getTypeOfUseOptions(): array
    {
        return [
            00 => __('Customer network set via RADIUS'),
            10 => __('Customer network set manually'),
            20 => __('Technology network set manually'),
        ];
    }

    /**
     * Get IP network type of use name method
     *
     * @return string
     */
    public function getTypeOfUseName(): string
    {
        return $this->getTypeOfUseOptions()[$this->type_of_use] ?? (string)$this->type_of_use;
    }
}
