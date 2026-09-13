<?php
declare(strict_types=1);

namespace Files\View\Cell;

use Cake\View\Cell;
use Files\Model\Entity\Documentation;
use Files\Service\Documentations;
use Files\Service\Previews;
use Override;

/**
 * The folders of a record, and what is in one of them.
 *
 * A cell rather than an element because of the one question a template must not ask row by row:
 * how much each folder holds. That is one query for the whole listing, and a template asking it
 * per row would be a query per row for a number that is on the same shelf as all the others.
 *
 * What the folders are hangs on a column this plugin does not know, so they are handed over
 * already found. Which record they belong to is the application's business, and drawing them is
 * not.
 */
class DocumentationsCell extends Cell
{
    /**
     * Initialization hook method.
     *
     * The viewer is the plugin's own, and a folder full of photographs is the reason it exists.
     *
     * @return void
     */
    #[Override]
    public function initialize(): void
    {
        parent::initialize();

        $this->viewBuilder()->addHelper('Files.Preview');
    }

    /**
     * The folders, one to a row, with how much each of them holds.
     *
     * @param iterable<\Files\Model\Entity\Documentation> $documentations The folders, in the
     *   order they are to read.
     * @return void
     */
    public function display(iterable $documentations): void
    {
        $folders = [];
        foreach ($documentations as $folder) {
            $folders[] = $folder;
        }

        $held = (new Documentations())->contentsOfEach($folders);

        $rows = [];
        foreach ($folders as $folder) {
            $contents = $held[(string)$folder->id] ?? [];

            $rows[] = [
                'documentation' => $folder,
                'contents' => $contents,
                'gallery' => $this->galleryOf($folder),
                'bytes' => $this->bytesOf($contents),
            ];
        }

        $this->set(compact('rows'));
    }

    /**
     * What is in one folder, in two parts: what can be seen, and what can only be listed.
     *
     * A folder of photographs from a roof is looked at rather than read, and thirty rows of
     * filenames say nothing about which one shows the mast - so those are a wall of tiles. A
     * firmware image and an archive have nothing to show, and standing in that wall they left
     * a hole in it and a name too small to read. Those are a table, the way documents are.
     *
     * @param \Files\Model\Entity\Documentation $documentation The folder.
     * @return void
     */
    public function contents(Documentation $documentation): void
    {
        $contents = (new Documentations())->contentsOf($documentation);
        $seen = [];

        foreach ($contents as $link) {
            if (Previews::generates($link->file->mime_type ?? null)) {
                $seen[] = $link;
            }
        }

        $this->set('documentation', $documentation);
        $this->set('contents', $contents);
        $this->set('seen', $seen);
        $this->set('gallery', $this->galleryOf($documentation));
        $this->set('bytes', $this->bytesOf($contents));
    }

    /**
     * What tells this folder's contents apart from another's on the same page.
     *
     * @param \Files\Model\Entity\Documentation $documentation The folder.
     * @return string
     */
    private function galleryOf(Documentation $documentation): string
    {
        return 'documentation-' . $documentation->id;
    }

    /**
     * How much a folder holds altogether.
     *
     * @param list<\Files\Model\Entity\FileLink> $contents What is in it.
     * @return int
     */
    private function bytesOf(array $contents): int
    {
        $bytes = 0;

        foreach ($contents as $link) {
            $bytes += (int)($link->file->byte_size ?? 0);
        }

        return $bytes;
    }
}
