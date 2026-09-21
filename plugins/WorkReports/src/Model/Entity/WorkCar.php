<?php
declare(strict_types=1);

namespace WorkReports\Model\Entity;

use App\Model\Entity\AppEntity;

/**
 * WorkCar Entity
 *
 * A car without an owner belongs to the company, one with an owner is that person's own.
 *
 * @property string $name
 * @property string|null $license_plate
 * @property string|null $owner_id
 * @property bool $active
 * @property string|null $note
 * @property string $name_for_lists
 *
 * @property \App\Model\Entity\AppUser|null $owner
 */
class WorkCar extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'license_plate' => true,
        'owner_id' => true,
        'active' => true,
        'note' => true,
    ];

    /**
     * The name, and the plate where there is one.
     *
     * @return string
     */
    protected function _getNameForLists(): string
    {
        return $this->license_plate === null || $this->license_plate === ''
            ? $this->name
            : $this->name . ' (' . $this->license_plate . ')';
    }
}
