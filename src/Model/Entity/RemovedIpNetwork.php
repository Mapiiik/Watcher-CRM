<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\FrozenDate;
use Cake\ORM\Entity;

/**
 * RemovedIpNetwork Entity
 *
 * @property int $id
 * @property int $customer_id
 * @property int $contract_id
 * @property string $ip_network
 * @property int $type_of_use
 * @property string|null $note
 * @property \Cake\I18n\FrozenTime $removed
 * @property int $removed_by
 * @property string $style
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Contract $contract
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
        'customer_id' => true,
        'contract_id' => true,
        'ip_network' => true,
        'type_of_use' => true,
        'note' => true,
        'removed' => true,
        'removed_by' => true,
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
        $style = 'background-color: #bbbbbb;';
        $now = new FrozenDate();

        if (isset($this->contract->valid_until) && $this->contract->valid_until < $now) {
            $style = 'background-color: #ffaaaa;';
        }

        return $style;
    }
}
