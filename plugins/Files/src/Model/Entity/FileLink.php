<?php
declare(strict_types=1);

namespace Files\Model\Entity;

use App\Model\Entity\AppEntity;

/**
 * One record's use of some content.
 *
 * `model` and `foreign_key` say who has it, `document_type` which document it is, `variant` whose
 * signatures it carries, and `position` which page it is where a document runs to several. The
 * last two are strings and a number here on purpose: what the values mean belongs to the
 * application, so that this plugin can be copied into another one without being taught the
 * first one's vocabulary.
 *
 * @property string $id
 * @property string $file_id
 * @property string $model
 * @property string $foreign_key
 * @property string $document_type
 * @property string $variant
 * @property int $position
 * @property string|null $name
 * @property array<string, mixed> $meta
 *
 * @property \Files\Model\Entity\File $file
 */
class FileLink extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * What the link points at is not among them. A link is made against content the storage has
     * just written, and pointing an existing one somewhere else would leave the bytes it used to
     * hold with nobody to answer for them.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'model' => true,
        'foreign_key' => true,
        'document_type' => true,
        'variant' => true,
        'position' => true,
        'name' => true,
        'meta' => true,
    ];

    /**
     * What to call it when it is handed over.
     *
     * The name it arrived under, where it came with one. A document the application made did
     * not, so it falls back to the hash, which is at least unmistakable.
     *
     * @return string
     */
    public function downloadName(): string
    {
        $name = trim((string)$this->name);

        return $name !== '' ? $name : ($this->file->hash ?? $this->file_id);
    }
}
