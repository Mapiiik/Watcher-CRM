<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ServiceOverride Entity
 *
 * @property string $id
 * @property string $contract_id
 * @property string $service_id
 * @property \Cake\I18n\Date $valid_from
 * @property \Cake\I18n\Date $valid_until
 * @property string|null $reason
 * @property \Cake\I18n\DateTime|null $created
 * @property string|null $created_by
 * @property \Cake\I18n\DateTime|null $modified
 * @property string|null $modified_by
 * @property \Cake\I18n\DateTime|null $revoked
 * @property string|null $revoked_by
 * @property \App\Model\Entity\Contract $contract
 * @property \App\Model\Entity\Service $service
 * @property \App\Model\Entity\AppUser $creator
 * @property \App\Model\Entity\AppUser $modifier
 * @property \App\Model\Entity\AppUser $revoker
 */
class ServiceOverride extends Entity
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
        'contract_id' => true,
        'service_id' => true,
        'valid_from' => true,
        'valid_until' => true,
        'reason' => true,
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'revoked' => true,
        'revoked_by' => true,
        'creator' => true,
        'modifier' => true,
        'contract' => true,
        'service' => true,
        'revoker' => true,
    ];
}
