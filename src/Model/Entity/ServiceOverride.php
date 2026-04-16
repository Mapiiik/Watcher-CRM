<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\Date;

/**
 * ServiceOverride Entity
 *
 * @property string $id
 * @property string $contract_id
 * @property string $service_id
 * @property \Cake\I18n\Date $valid_from
 * @property \Cake\I18n\Date $valid_until
 * @property string|null $reason
 * @property \Cake\I18n\DateTime|null $revoked
 * @property string|null $revoked_by
 * @property \App\Model\Entity\Contract $contract
 * @property \App\Model\Entity\Service $service
 * @property \App\Model\Entity\AppUser $revoker
 */
class ServiceOverride extends AppEntity
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

    /**
     * Check whether the service override is currently active.
     *
     * An override is considered active when:
     * - it is not revoked,
     * - the current date is within the valid_from / valid_until range.
     *
     * @param \Cake\I18n\Date|null $date Date to evaluate against, defaults to today.
     * @return bool True if the override is active, false otherwise.
     */
    public function isActive(?Date $date = null): bool
    {
        $date ??= Date::now();

        if ($this->revoked !== null) {
            return false;
        }

        if ($this->valid_from > $date) {
            return false;
        }

        if ($this->valid_until < $date) {
            return false;
        }

        return true;
    }

    /**
     * Check whether the service override is scheduled for the future.
     *
     * An override is considered future when:
     * - it is not revoked,
     * - its valid_from date is after the given date.
     *
     * @param \Cake\I18n\Date|null $date Date to evaluate against, defaults to today.
     * @return bool True if the override is scheduled for the future, false otherwise.
     */
    public function isFuture(?Date $date = null): bool
    {
        $date ??= Date::now();

        if ($this->revoked !== null) {
            return false;
        }

        if ($this->valid_from <= $date) {
            return false;
        }

        return true;
    }
}
