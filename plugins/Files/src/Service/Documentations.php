<?php
declare(strict_types=1);

namespace Files\Service;

use Cake\ORM\Locator\LocatorAwareTrait;
use Files\Model\Entity\Documentation;
use Files\Model\Entity\FileLink;
use Files\Model\Table\FileLinksTable;

/**
 * What is in a folder.
 *
 * The folder is the record and its contents are ordinary links to content, filed under the folder
 * the way pages are filed under a document. Which is why the viewer, the pictures and the sweep
 * that checks the store need nothing new: to all of them this is one more record with files.
 *
 * Nothing is refused by kind. Documentation is whatever the work produced, and a drawing, an
 * archive or a recording is as much of it as a photograph - what may be opened in place of being
 * kept is a separate question, and {@see \Files\Service\Viewable} answers it.
 */
class Documentations
{
    use LocatorAwareTrait;

    /**
     * The four words the store files a folder's contents under. Two of them say nothing, because
     * a folder has one kind of contents and they carry nobody's signature.
     */
    public const MODEL = 'Documentations';
    public const ATTACHMENT = 'attachment';
    public const UPLOADED = 'uploaded';

    /**
     * The taking in and the ordering, and the store underneath both.
     */
    private FiledPages $pages;
    private FileStorage $storage;

    /**
     * @param \Files\Service\FileStorage|null $storage Where the contents are kept.
     */
    public function __construct(?FileStorage $storage = null)
    {
        $this->storage = $storage ?? new FileStorage();
        $this->pages = new FiledPages($this->storage);
    }

    /**
     * Files what was uploaded into the folder.
     *
     * @param \Files\Model\Entity\Documentation $documentation The folder.
     * @param list<\Psr\Http\Message\UploadedFileInterface> $files What arrived.
     * @return int How many were filed.
     * @throws \RuntimeException When something arrived that will not be taken.
     */
    public function take(Documentation $documentation, array $files): int
    {
        return $this->pages->take(
            self::MODEL,
            (string)$documentation->id,
            self::ATTACHMENT,
            self::UPLOADED,
            $files,
        );
    }

    /**
     * Lets go of one of them, and closes the gap it leaves.
     *
     * @param \Files\Model\Entity\FileLink $link The one to let go of.
     * @return void
     */
    public function drop(FileLink $link): void
    {
        $this->pages->drop($link);
    }

    /**
     * Moves one past the one beside it.
     *
     * @param \Files\Model\Entity\FileLink $link The one to move.
     * @param bool $up Whether it goes before the one above it rather than after the one below.
     * @return void
     */
    public function move(FileLink $link, bool $up): void
    {
        $this->pages->move($link, $up);
    }

    /**
     * What is in the folder, in the order it reads.
     *
     * @param \Files\Model\Entity\Documentation $documentation The folder.
     * @return list<\Files\Model\Entity\FileLink>
     */
    public function contentsOf(Documentation $documentation): array
    {
        /** @var list<\Files\Model\Entity\FileLink> $found */
        $found = $this->fileLinks()->find(
            'group',
            model: self::MODEL,
            foreign_key: (string)$documentation->id,
            document_type: self::ATTACHMENT,
            variant: self::UPLOADED,
        )->contain(['Files'])->all()->toList();

        return $found;
    }

    /**
     * What is in a handful of folders, in one asking.
     *
     * A listing says how much each folder holds, and asking once per row would be one query per
     * row for a number that is on the same shelf as all the others.
     *
     * @param iterable<\Files\Model\Entity\Documentation> $documentations The folders.
     * @return array<string, list<\Files\Model\Entity\FileLink>> By folder.
     */
    public function contentsOfEach(iterable $documentations): array
    {
        $keys = [];
        foreach ($documentations as $documentation) {
            $keys[] = (string)$documentation->id;
        }

        $found = [];
        foreach ($keys as $key) {
            $found[$key] = [];
        }

        /** @var iterable<\Files\Model\Entity\FileLink> $links */
        $links = $this->fileLinks()
            ->find('forAny', model: self::MODEL, foreign_keys: $keys)
            ->contain(['Files'])
            ->all();

        foreach ($links as $link) {
            $found[$link->foreign_key][] = $link;
        }

        return $found;
    }

    /**
     * Lets go of everything in the folder.
     *
     * The positions are not put straight afterwards, because there is nothing left to put
     * straight - this is what a folder on its way out does with what it was holding.
     *
     * @param \Files\Model\Entity\Documentation $documentation The folder.
     * @return void
     */
    public function clear(Documentation $documentation): void
    {
        foreach ($this->contentsOf($documentation) as $link) {
            $this->storage->unlink($link);
        }
    }

    /**
     * @return \Files\Model\Table\FileLinksTable
     */
    private function fileLinks(): FileLinksTable
    {
        /** @var \Files\Model\Table\FileLinksTable $links */
        $links = $this->fetchTable(FileLinksTable::class);

        return $links;
    }
}
