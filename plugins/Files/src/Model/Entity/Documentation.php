<?php
declare(strict_types=1);

namespace Files\Model\Entity;

use App\Model\Entity\AppEntity;

/**
 * A folder, and everything about it but what is in it.
 *
 * What it hangs on is the application's column, added by the application's own migration, so it
 * is not among the properties here. Everything else is the same wherever this is copied.
 *
 * @property string $id
 * @property string $documentation_type_id
 * @property \Cake\I18n\Date|null $happened_on
 * @property string|null $name
 * @property string|null $note
 *
 * @property \Files\Model\Entity\DocumentationType $documentation_type
 * @property string $heading
 */
class Documentation extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'documentation_type_id' => true,
        'happened_on' => true,
        'name' => true,
        'note' => true,
    ];

    /**
     * Whether there is anything to tell this folder from the next one of its kind.
     *
     * A name, a day, or both. With neither, two folders of one type on one record read exactly
     * alike and nobody can say which is which.
     *
     * @return bool
     */
    public function hasIdentity(): bool
    {
        return trim((string)$this->name) !== '' || $this->happened_on !== null;
    }

    /**
     * What to call it wherever it is named.
     *
     * A name where somebody wrote one, and the day and the kind where nobody did - which between
     * them is why one of the two has to be there.
     *
     * @return string
     */
    protected function _getHeading(): string
    {
        $name = trim((string)$this->name);
        if ($name !== '') {
            return $name;
        }

        $kind = trim((string)($this->documentation_type->name ?? ''));

        return $this->happened_on === null ? $kind : trim($this->happened_on . ' - ' . $kind, ' -');
    }
}
