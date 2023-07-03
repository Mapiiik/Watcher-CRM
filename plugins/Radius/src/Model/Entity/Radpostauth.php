<?php
declare(strict_types=1);

namespace Radius\Model\Entity;

use Cake\ORM\Entity;

/**
 * Radpostauth Entity
 *
 * @property int $id
 * @property string $username
 * @property string|null $pass
 * @property string|null $reply
 * @property string|null $calledstationid
 * @property string|null $callingstationid
 * @property \Cake\I18n\DateTime $authdate
 *
 * @property \Radius\Model\Entity\Account $account
 */
class Radpostauth extends Entity
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
        'username' => true,
        'pass' => true,
        'reply' => true,
        'calledstationid' => true,
        'callingstationid' => true,
        'authdate' => true,
        'account' => true,
    ];
}
