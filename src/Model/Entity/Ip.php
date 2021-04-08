<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Ip Entity
 *
 * @property string $ip
 * @property int $customer_id
 * @property string|null $note
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
        'note' => true,
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
