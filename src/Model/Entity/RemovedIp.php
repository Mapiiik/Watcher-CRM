<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\FrozenDate;
use Cake\ORM\Entity;

/**
 * RemovedIp Entity
 *
 * @property int $id
 * @property int $removed_by
 * @property \Cake\I18n\FrozenTime $removed
 * @property string $ip
 * @property int $customer_id
 * @property string|null $note
 * @property int|null $contract_id
 * @property string $style
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Contract $contract
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
     * @var array<bool>
     */
    protected $_accessible = [
        'removed_by' => true,
        'removed' => true,
        'ip' => true,
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
        $style = 'background-color: #bbbbbb;';
        $now = new FrozenDate();

        if (isset($this->contract->valid_until) && $this->contract->valid_until < $now) {
            $style = 'background-color: #ffaaaa;';
        }

        return $style;
    }
}
