<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Ip Entity
 *
 * @property string $ip
 * @property int $customer_id
 * @property int $queue_id
 * @property int $device_id
 * @property string|null $mac
 * @property string|null $comment
 * @property int|null $cost
 * @property int|null $dealer_id
 * @property \Cake\I18n\FrozenDate|null $installation_date
 * @property int|null $brokerage_id
 * @property \Cake\I18n\FrozenDate|null $billing_from
 * @property string|null $note
 * @property bool $vip
 * @property \Cake\I18n\FrozenDate|null $bond
 * @property \Cake\I18n\FrozenDate|null $active_until
 * @property int|null $router_id
 * @property string|null $access_description
 * @property int $contract_id
 * @property int $id
 * @property \Cake\I18n\FrozenTime|null $created
 * @property int|null $created_by
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $modified_by
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Queue $queue
 * @property \App\Model\Entity\Device $device
 * @property \App\Model\Entity\Dealer $dealer
 * @property \App\Model\Entity\Brokerage $brokerage
 * @property \App\Model\Entity\Router $router
 * @property \App\Model\Entity\Contract $contract
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
     * @var array
     */
    protected $_accessible = [
        'customer_id' => true,
        'queue_id' => true,
        'device_id' => true,
        'mac' => true,
        'comment' => true,
        'cost' => true,
        'dealer_id' => true,
        'installation_date' => true,
        'brokerage_id' => true,
        'billing_from' => true,
        'note' => true,
        'vip' => true,
        'bond' => true,
        'active_until' => true,
        'router_id' => true,
        'access_description' => true,
        'contract_id' => true,
        'id' => true,
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'customer' => true,
        'queue' => true,
        'device' => true,
        'dealer' => true,
        'brokerage' => true,
        'router' => true,
        'contract' => true,
    ];
}
