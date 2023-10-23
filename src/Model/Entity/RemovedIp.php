<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\ApiClient;
use Cake\Collection\CollectionInterface;
use Cake\ORM\Entity;

/**
 * RemovedIp Entity
 *
 * @property \Cake\I18n\DateTime|null $created
 * @property string|null $created_by
 * @property \App\Model\Entity\AppUser|null $creator
 * @property \Cake\I18n\DateTime|null $modified
 * @property string|null $modified_by
 * @property \App\Model\Entity\AppUser|null $modifier
 * @property \Cake\I18n\DateTime|null $removed
 * @property int|null $removed_by
 * @property \CakeDC\Users\Model\Entity\User|null $remover
 * @property string $id
 * @property int $nid
 * @property string $ip
 * @property string $customer_id
 * @property string|null $note
 * @property string $contract_id
 * @property int $type_of_use
 * @property string $style
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Contract $contract
 * @property \Cake\Collection\CollectionInterface|null $ip_address_ranges
 */
class RemovedIp extends Entity
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
        'removed' => true,
        'removed_by' => true,
        'ip' => true,
        'type_of_use' => true,
        'customer_id' => true,
        'note' => true,
        'contract_id' => true,
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
        $ipAddressRanges = ApiClient::getIpAddressRangesForIp($this->ip);

        if ($ipAddressRanges) {
            return $ipAddressRanges;
        }

        return null;
    }

    /**
     * Get IP address type of use options method
     *
     * @return array<int, string>
     */
    public function getTypeOfUseOptions(): array
    {
        return [
            00 => __('Customer address set via RADIUS'),
            10 => __('Customer address set manually'),
            20 => __('Technology address set manually'),
        ];
    }

    /**
     * Get IP address type of use name method
     *
     * @return string
     */
    public function getTypeOfUseName(): string
    {
        return $this->getTypeOfUseOptions()[$this->type_of_use] ?? (string)$this->type_of_use;
    }
}
