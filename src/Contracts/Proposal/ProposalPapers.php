<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Model\Entity\ContractVersionProposal;
use App\Model\Enum\DocumentVariant;
use App\Service\ContractPrint\ContractDocuments;
use Cake\ORM\Locator\LocatorAwareTrait;
use Files\Model\Entity\FileLink;
use Files\Model\Table\FileLinksTable;
use Files\Service\FileStorage;
use finfo;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/**
 * The papers on a proposal that somebody hands over rather than prints: the scans that come back.
 *
 * Kept apart from {@see \App\Service\ContractPrint\ContractDocuments}, which is about drawing a
 * paper and never drawing it twice. Nothing here draws anything - it takes what arrived, keeps it
 * as it arrived, and puts the pages in the order somebody says they go.
 */
final class ProposalPapers
{
    use LocatorAwareTrait;

    /**
     * What a scan may be.
     *
     * Asked of the bytes rather than of the browser, which says whatever the machine at the other
     * end felt like saying. Wide on purpose: a phone, a scanner and a fax gateway all send
     * something different and the operator should not have to care which
     * ({@see dont-constrain-the-operators-needlessly}).
     */
    public const ACCEPTED = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/heic',
        'image/heif',
        'image/tiff',
    ];

    /**
     * Where the papers are kept.
     */
    private FileStorage $storage;

    /**
     * @param \Files\Service\FileStorage|null $storage Where the papers are kept.
     */
    public function __construct(?FileStorage $storage = null)
    {
        $this->storage = $storage ?? new FileStorage();
    }

    /**
     * Files what was uploaded, page after page, in the order it was handed over.
     *
     * The order the browser sends them in is the order they were picked, which for a set of scans
     * is usually their own numbering - so the common case needs no putting right afterwards.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal Whose papers.
     * @param string $document_type Which document they are of.
     * @param \App\Model\Enum\DocumentVariant $variant Whose signatures they carry.
     * @param list<\Psr\Http\Message\UploadedFileInterface> $files What arrived.
     * @return int How many pages were filed.
     * @throws \RuntimeException When something arrived that is not a paper.
     */
    public function take(
        ContractVersionProposal $proposal,
        string $document_type,
        DocumentVariant $variant,
        array $files,
    ): int {
        $filed = 0;

        foreach ($files as $file) {
            if ($file->getError() !== UPLOAD_ERR_OK || $file->getSize() === 0) {
                continue;
            }

            $path = $this->pathOf($file);
            $mime_type = $this->mimeTypeOf($path);

            $this->storage->link(
                $this->storage->storeFile($path, $mime_type),
                ContractDocuments::MODEL,
                (string)$proposal->id,
                $document_type,
                $variant->value,
                ['name' => $file->getClientFilename()],
            );

            $filed++;
        }

        return $filed;
    }

    /**
     * Lets go of one page, and closes the gap it leaves.
     *
     * @param \Files\Model\Entity\FileLink $link The page.
     * @return void
     */
    public function drop(FileLink $link): void
    {
        $group = $this->group($link);
        $this->storage->unlink($link);

        $this->renumber(array_values(array_filter(
            $group,
            fn(FileLink $one): bool => $one->id !== $link->id,
        )));
    }

    /**
     * Moves a page past the one beside it.
     *
     * The whole group is written out afterwards rather than the two of them swapped, so that
     * positions which had drifted - two pages on the same number, a gap left by something long
     * gone - come out straight either way.
     *
     * @param \Files\Model\Entity\FileLink $link The page.
     * @param bool $up Whether it goes before the one above it rather than after the one below.
     * @return void
     */
    public function move(FileLink $link, bool $up): void
    {
        $group = $this->group($link);

        $at = null;
        foreach ($group as $index => $one) {
            if ($one->id === $link->id) {
                $at = $index;
            }
        }

        $to = $at === null ? null : $at + ($up ? -1 : 1);
        if ($at === null || $to === null || $to < 0 || $to >= count($group)) {
            return;
        }

        [$group[$at], $group[$to]] = [$group[$to], $group[$at]];

        $this->renumber(array_values($group));
    }

    /**
     * The pages this one stands among, in the order they read.
     *
     * @param \Files\Model\Entity\FileLink $link One of them.
     * @return list<\Files\Model\Entity\FileLink>
     */
    private function group(FileLink $link): array
    {
        /** @var list<\Files\Model\Entity\FileLink> $group */
        $group = $this->fileLinks()->find(
            'group',
            model: $link->model,
            foreign_key: $link->foreign_key,
            document_type: $link->document_type,
            variant: $link->variant,
        )->all()->toList();

        return $group;
    }

    /**
     * Writes a group out as nought upwards.
     *
     * @param list<\Files\Model\Entity\FileLink> $group The pages, in the order they are to read.
     * @return void
     */
    private function renumber(array $group): void
    {
        $links = $this->fileLinks();

        $links->getConnection()->transactional(function () use ($links, $group): void {
            foreach ($group as $position => $link) {
                if ($link->position === $position) {
                    continue;
                }

                $link->set('position', $position);
                $links->saveOrFail($link);
            }
        });
    }

    /**
     * Where PHP put the upload while it waits to be taken.
     *
     * The file itself rather than its contents: a set of scans runs to hundreds of megabytes and
     * none of it has any business passing through memory.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file What arrived.
     * @return string
     * @throws \RuntimeException When there is nothing behind it.
     */
    private function pathOf(UploadedFileInterface $file): string
    {
        $path = $file->getStream()->getMetadata('uri');

        if (!is_string($path) || !is_file($path)) {
            throw new RuntimeException(__('The uploaded file could not be read.'));
        }

        return $path;
    }

    /**
     * What kind of thing arrived, according to what is in it.
     *
     * @param string $path Where it is.
     * @return string
     * @throws \RuntimeException When it is not a paper.
     */
    private function mimeTypeOf(string $path): string
    {
        $mime_type = (string)(new finfo(FILEINFO_MIME_TYPE))->file($path);

        if (!in_array($mime_type, self::ACCEPTED, true)) {
            throw new RuntimeException(__('A {0} is not a document that can be filed.', $mime_type));
        }

        return $mime_type;
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
