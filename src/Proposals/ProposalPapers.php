<?php
declare(strict_types=1);

namespace App\Proposals;

use App\Model\Enum\DocumentVariant;
use Cake\ORM\Locator\LocatorAwareTrait;
use Files\Model\Entity\FileLink;
use Files\Model\Table\FileLinksTable;
use Files\Service\FileStorage;
use finfo;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Throwable;

/**
 * The papers on a proposal that somebody hands over rather than prints: the scans that come back.
 *
 * Kept apart from the services that draw papers and never draw them twice. Nothing here draws
 * anything - it takes what arrived, keeps it as it arrived, and puts the pages in the order
 * somebody says they go.
 *
 * It is told which record the pages hang on rather than being handed one, because a scan of a
 * contract's paper and a scan of a consent are the same act on two different agendas, and what
 * differs between them is only the two words the store files them under.
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
     * @param string $model What kind of record they hang on.
     * @param string $foreign_key Which one.
     * @param string $document_type Which document they are of.
     * @param \App\Model\Enum\DocumentVariant $variant Whose signatures they carry.
     * @param list<\Psr\Http\Message\UploadedFileInterface> $files What arrived.
     * @return int How many pages were filed.
     * @throws \RuntimeException When something arrived that is not a paper.
     */
    public function take(
        string $model,
        string $foreign_key,
        string $document_type,
        DocumentVariant $variant,
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
                $variant->value,
                ['name' => $file->getClientFilename()],
            );

            $filed++;
        }

        return $filed;
    }

    /**
     * Files what came back for several documents at once, the way a form offering all of them does.
     *
     * One document refusing its pages does not stop the others: they are separate papers, and what
     * can be filed is filed. What went wrong is handed back rather than thrown, because the caller
     * is recording something else at the same time and that must not fall over with it.
     *
     * @param string $model What kind of record they hang on.
     * @param string $foreign_key Which one.
     * @param array<string, mixed> $uploaded What arrived, by document type.
     * @param array<string, mixed> $variants Whose signatures each of them carries, by document type.
     * @return array<string, mixed> How many pages were filed, and what would not be stored.
     * @phpstan-return array{filed: int, problems: list<string>}
     */
    public function takeEach(string $model, string $foreign_key, array $uploaded, array $variants): array
    {
        $filed = 0;
        $problems = [];

        foreach ($uploaded as $document_type => $files) {
            $variant = DocumentVariant::tryFrom((string)($variants[$document_type] ?? ''))
                ?? DocumentVariant::ReceivedSignedByCustomer;

            try {
                $filed += $this->take(
                    $model,
                    $foreign_key,
                    (string)$document_type,
                    $variant,
                    array_values((array)$files),
                );
            } catch (Throwable $e) {
                $problems[] = $e->getMessage();
            }
        }

        return ['filed' => $filed, 'problems' => $problems];
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
     * Whether the server threw part of what was sent away before the application saw it.
     *
     * PHP takes `max_file_uploads` files out of a request and drops the rest on the floor. Not
     * with an error on them - that is what the codes are for, and they are reported - but by
     * never putting them in the request at all. Nothing can be said about a file that was never
     * there, so what is left to notice is the count: a request carrying exactly the limit is a
     * batch that was cut, and a batch that happened to fit exactly is the rare case where this
     * says so when it need not have.
     *
     * Fifty-five photographs from an installation came in as twenty, and neither the person who
     * sent them nor the application was told anything at all.
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
        return __(
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
        $name = (string)($file->getClientFilename() ?? __('The file'));

        return match ($file->getError()) {
            UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE => null,
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => __(
                '{0} is larger than this server takes in one go.',
                $name,
            ),
            UPLOAD_ERR_PARTIAL => __('{0} only arrived in part.', $name),
            default => __('{0} could not be taken in.', $name),
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
