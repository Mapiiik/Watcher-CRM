<?php
declare(strict_types=1);

namespace Access\Model\Entity;

use Cake\ORM\Entity;

/**
 * An identity at another provider, linked to the account it signs in.
 *
 * @property string $id
 * @property string $user_id
 * @property string $provider
 * @property string $reference
 * @property string|null $username
 * @property bool $active
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class SocialAccount extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'user_id' => true,
        'provider' => true,
        'reference' => true,
        'username' => true,
        'active' => true,
    ];
}
