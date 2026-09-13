<?php
declare(strict_types=1);

namespace Files\Service;

use Cake\ORM\Locator\LocatorAwareTrait;
use Files\Model\Entity\FileLink;
use Files\Model\Table\FileLinksTable;
use finfo;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

/**
 * Taking pages in, and keeping them in the order somebody says they go.
 *
 * Nothing here draws anything: it takes what arrived, keeps it as it arrived, and puts the pages
 * where they belong. What they are pages of is four strings the caller hands over, so the same
 * act serves a scanned contract and a folder of photographs from an installation.
 *
 * What may be filed is the caller's to say. A handful of kinds where the record is a paper and
 * has to be one, and anything at all where it is documentation - so the list is passed in rather
 * than written here, and nothing means everything.
 */
class FiledPages
{
    use LocatorAwareTrait;

    /**
     * Where the pages are kept.
     */
    private FileStorage $storage;

    /**
     * The kinds that may be filed, or null where any kind may.
     *
     * @var list<string>|null
     */
    private ?array $accepted;

    /**
     * @param \Files\Service\FileStorage|null $storage Where the pages are kept.
     * @param list<string>|null $accepted What may be filed, or null for anything.
     */
    public function __construct(?FileStorage $storage = null, ?array $accepted = null)
    {
        $this->storage = $storage ?? new FileStorage();
        $this->accepted = $accepted;
    }

    /**
     * Files what was uploaded, page after page, in the order it was handed over.
     *
     * The order the browser sends them in is the order they were picked, which for a set of scans
     * is usually their own numbering - so the common case needs no putting right afterwards.
     *
     * @param string $model What kind of record they hang on.
     * @param string $foreign_key Which one.
     * @param string $document_type Which document they are of.
     * @param string $variant Which variant of that document this is.
     * @param list<\Psr\Http\Message\UploadedFileInterface> $files What arrived.
     * @return int How many pages were filed.
     * @throws \RuntimeException When something arrived that will not be taken.
     */
    public function take(
        string $model,
        string $foreign_key,
        string $document_type,
        string $variant,
        array $files,
    ): int {
        $filed = 0;

        foreach ($files as $file) {
            $refused = $this->refusalOf($file);
            if ($refused !== null) {
                throw new RuntimeException($refused);
            }

            if ($file->getError() !== UPLOAD_ERR_OK || $file->getSize() === 0) {
                continue;
            }

            $path = $this->pathOf($file);
            $mime_type = $this->mimeTypeOf($path);

            $this->storage->link(
                $this->storage->storeFile($path, $mime_type),
                $model,
                $foreign_key,
                $document_type,
                $variant,
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
     * Whether the server threw part of what was sent away before the application saw it.
     *
     * PHP takes `max_file_uploads` files out of a request and drops the rest on the floor. Not
     * with an error on them - that is what the codes are for, and they are reported - but by
     * never putting them in the request at all. Nothing can be said about a file that was never
     * there, so what is left to notice is the count: a request carrying exactly the limit is a
     * batch that was cut, and a batch that happened to fit exactly is the rare case where this
     * says so when it need not have.
     *
     * @param array<mixed> $uploaded Everything the request carried, however deeply it is nested.
     * @return bool
     */
    public static function cutShort(array $uploaded): bool
    {
        $limit = self::atMostAtOnce();

        return $limit > 0 && self::howManyIn($uploaded) >= $limit;
    }

    /**
     * How many files the server will take out of one request.
     *
     * @return int
     */
    public static function atMostAtOnce(): int
    {
        return (int)ini_get('max_file_uploads');
    }

    /**
     * What to tell somebody whose batch was cut.
     *
     * @return string
     */
    public static function shortfall(): string
    {
        return __d(
            'files',
            'This server takes at most {0} files at once, so whatever was chosen beyond that'
            . ' never arrived. Send the rest as a second batch.',
            self::atMostAtOnce(),
        );
    }

    /**
     * How many files are in there, wherever they sit.
     *
     * @param array<mixed> $uploaded The files, however they are nested.
     * @return int
     */
    private static function howManyIn(array $uploaded): int
    {
        $found = 0;

        foreach ($uploaded as $one) {
            $found += is_array($one) ? self::howManyIn($one) : 1;
        }

        return $found;
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
     * Why the server would not take a file, when it would not.
     *
     * An empty slot is not a refusal: a form offering several documents at once has one for each
     * and most of them are left alone. Anything else is, and has to be said out loud - a page
     * turned away without a word is one the operator goes on believing is filed.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file What arrived.
     * @return string|null Why it was turned away, or null when it was not.
     */
    private function refusalOf(UploadedFileInterface $file): ?string
    {
        $name = (string)($file->getClientFilename() ?? __d('files', 'The file'));

        return match ($file->getError()) {
            UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE => null,
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => __d(
                'files',
                '{0} is larger than this server takes in one go.',
                $name,
            ),
            UPLOAD_ERR_PARTIAL => __d('files', '{0} only arrived in part.', $name),
            default => __d('files', '{0} could not be taken in.', $name),
        };
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
            throw new RuntimeException(__d('files', 'The uploaded file could not be read.'));
        }

        return $path;
    }

    /**
     * What kind of thing arrived, according to what is in it.
     *
     * Asked of the bytes rather than of the browser, which says whatever the machine at the other
     * end felt like saying.
     *
     * @param string $path Where it is.
     * @return string
     * @throws \RuntimeException When it is not something this caller files.
     */
    private function mimeTypeOf(string $path): string
    {
        $mime_type = (string)(new finfo(FILEINFO_MIME_TYPE))->file($path);

        if ($this->accepted !== null && !in_array($mime_type, $this->accepted, true)) {
            throw new RuntimeException(__d('files', 'A {0} is not a document that can be filed.', $mime_type));
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
