<?php
declare(strict_types=1);

namespace Access\Model\Entity;

use Cake\ORM\Entity;

/**
 * A code that signs somebody in once without their phone.
 *
 * @property string $id
 * @property string $user_id
 * @property string $code_hash
 * @property \Cake\I18n\DateTime|null $used
 * @property \Cake\I18n\DateTime $created
 */
class RecoveryCode extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'user_id' => true,
        'code_hash' => true,
    ];

    /**
     * Fields left out wherever the code is written out whole.
     *
     * @var list<string>
     */
    protected array $_hidden = [
        'code_hash',
    ];
}
