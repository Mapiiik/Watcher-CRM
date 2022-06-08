<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\Utility\Security;

/**
 * Login Entity
 *
 * @property \Cake\I18n\FrozenTime|null $created
 * @property int|null $created_by
 * @property \CakeDC\Users\Model\Entity\User|null $creator
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property int|null $modified_by
 * @property \CakeDC\Users\Model\Entity\User|null $modifier
 * @property int $id
 * @property int $customer_id
 * @property string $login
 * @property string $password
 * @property int $rights
 * @property int $locked
 * @property \Cake\I18n\FrozenTime|null $last_granted
 * @property string|null $last_granted_ip
 * @property \Cake\I18n\FrozenTime|null $last_denied
 * @property string|null $last_denied_ip
 *
 * @property \App\Model\Entity\Customer $customer
 */
class Login extends Entity
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
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'customer_id' => true,
        'login' => true,
        'password' => true,
        'rights' => true,
        'locked' => true,
        'last_granted' => true,
        'last_granted_ip' => true,
        'last_denied' => true,
        'last_denied_ip' => true,
        'customer' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array<string>
     */
    protected $_hidden = [
        'password',
    ];

    /**
     * SHA1 for password if value is set
     *
     * @param string $password Plaintext password
     * @return string|null
     */
    protected function _setPassword(string $password): ?string
    {
        if (strlen($password) > 0) {
            return Security::hash($password, 'sha1');
        }

        return null;
    }
}
