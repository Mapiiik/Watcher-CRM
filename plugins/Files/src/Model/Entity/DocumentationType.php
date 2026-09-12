<?php
declare(strict_types=1);

namespace Files\Model\Entity;

use App\Model\Entity\AppEntity;

/**
 * What kind of folder this is.
 *
 * The rows are the operator's, not the code's, so nothing here knows what any particular one
 * means. The only thing a type says about its folders is whether they have to carry the day they
 * are about.
 *
 * @property string $id
 * @property string $name
 * @property int $position
 * @property bool $currently_offered
 * @property bool $date_required
 * @property string|null $note
 *
 * @property \Files\Model\Entity\Documentation[] $documentations
 */
class DocumentationType extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'position' => true,
        'currently_offered' => true,
        'date_required' => true,
        'note' => true,
    ];
}
