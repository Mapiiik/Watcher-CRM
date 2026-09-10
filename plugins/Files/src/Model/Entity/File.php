<?php
declare(strict_types=1);

namespace Files\Model\Entity;

use App\Model\Entity\AppEntity;

/**
 * Some content, once.
 *
 * A row here says what the bytes are - how many, of what kind, and the hash that is both their
 * name and the proof they are still what they were. It says nothing about what they are for;
 * that is what the links on it are.
 *
 * Nothing on it is ever written twice. Content that changed is different content, with a
 * different hash and a row of its own.
 *
 * @property string $id
 * @property string $sha256
 * @property int $byte_size
 * @property string $mime_type
 * @property string $path
 *
 * @property array<\Files\Model\Entity\FileLink> $file_links
 */
class File extends AppEntity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * The hash, the size and the path are set by the storage as it writes the bytes, never from
     * a form: a row whose hash does not answer for what is on the shelf is worse than no row.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'mime_type' => true,
    ];

    /**
     * Roughly what sort of thing it is, for a page that wants photographs and not papers.
     *
     * Worked out rather than stored: it can be had from the type at any time, and a column that
     * could be filled in afterwards from what is already in the row is a second place for the
     * same fact to be wrong.
     *
     * @return string
     */
    public function kind(): string
    {
        $type = (string)$this->mime_type;

        return match (true) {
            str_starts_with($type, 'image/') => 'image',
            str_starts_with($type, 'video/') => 'video',
            str_starts_with($type, 'audio/') => 'audio',
            $type === 'application/pdf' => 'document',
            default => 'other',
        };
    }
}
