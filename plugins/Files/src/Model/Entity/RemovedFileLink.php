<?php
declare(strict_types=1);

namespace Files\Model\Entity;

/**
 * A use of some content somebody removed, as it stood when it went.
 *
 * Still a link, so that whatever names a link or hands one over works on this as well. It only
 * says when it went, who did it, and what the folder was where the folder went with it.
 *
 * @property array<string, mixed>|null $documentation
 */
class RemovedFileLink extends FileLink
{
    /**
     * What the folder it left with was called, where the folder went too.
     *
     * @return string|null
     */
    public function folderName(): ?string
    {
        if ($this->documentation === null) {
            return null;
        }

        $name = trim((string)($this->documentation['name'] ?? ''));

        return $name !== '' ? $name : (string)($this->documentation['happened_on'] ?? '');
    }
}
