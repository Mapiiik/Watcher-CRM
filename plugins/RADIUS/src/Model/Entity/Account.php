<?php
declare(strict_types=1);

namespace RADIUS\Model\Entity;

use Cake\ORM\Entity;

/**
 * Account Entity
 *
 * @property int $id
 * @property string $username
 * @property string $password
 * @property int $type
 * @property bool $active
 * @property int $customer_id
 * @property int $contract_id
 * @property \Cake\I18n\FrozenTime $created
 * @property int $created_by
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $modified_by
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Contract $contract
 * @property \RADIUS\Model\Entity\Radcheck[] $radchecks
 * @property \RADIUS\Model\Entity\Radreply[] $radreplies
 * @property \RADIUS\Model\Entity\Radusergroup[] $radusergroups
 * @property \RADIUS\Model\Entity\Radpostauth[] $radpostauths
 * @property \RADIUS\Model\Entity\Radacct[] $radaccts
 */
class Account extends Entity
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
        'username' => true,
        'password' => true,
        'type' => true,
        'active' => true,
        'customer_id' => true,
        'contract_id' => true,
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'customer' => true,
        'contract' => true,
        'radcheck' => true,
        'radreply' => true,
        'radusergroup' => true,
        'radpostauth' => true,
        'radacct' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array<string>
     */
    protected $_hidden = [
        'password',
    ];
}
