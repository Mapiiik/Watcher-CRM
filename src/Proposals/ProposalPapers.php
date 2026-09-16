<?php
declare(strict_types=1);

namespace App\Proposals;

use App\Model\Enum\DocumentVariant;
use Cake\ORM\Locator\LocatorAwareTrait;
use Files\Model\Entity\FileLink;
use Files\Service\FiledPages;
use Files\Service\FileStorage;

/**
 * The papers on a proposal that somebody hands over rather than prints: the scans that come back.
 *
 * Kept apart from the services that draw papers and never draw them twice. The taking in and the
 * ordering are the plugin's, because they are the same act wherever a record keeps files at all;
 * what this adds is the vocabulary of proposals - which documents there are, whose signatures a
 * variant carries, and that a paper is a paper and not anything somebody felt like uploading.
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
     * Wide on purpose: a phone, a scanner and a fax gateway all send something different and the
     * operator should not have to care which
     * ({@see dont-constrain-the-operators-needlessly}). Narrow all the same, because a proposal's
     * paper is a paper - a spreadsheet filed against one is a mistake, not a document.
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
     * The taking in, the letting go of and the ordering.
     */
    private FiledPages $pages;

    /**
     * @param \Files\Service\FileStorage|null $storage Where the papers are kept.
     */
    public function __construct(?FileStorage $storage = null)
    {
        $this->pages = new FiledPages($storage, self::ACCEPTED);
    }

    /**
     * Files what was uploaded, page after page, in the order it was handed over.
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
        return $this->pages->take($model, $foreign_key, $document_type, $variant->value, $files);
    }

    /**
     * Lets go of one page, and closes the gap it leaves.
     *
     * A paper we drew goes whole: the copy carrying our signature is the same document stamped,
     * so letting one go and keeping the other would leave a signed copy of something that is no
     * longer there.
     *
     * @param \Files\Model\Entity\FileLink $link The page.
     * @return int How many were let go of.
     */
    public function drop(FileLink $link): int
    {
        if (!(DocumentVariant::tryFrom((string)$link->variant)?->isGeneratedByUs() ?? false)) {
            $this->pages->drop($link);

            return 1;
        }

        $dropped = 0;

        foreach ($this->everyVariantWeDrewOf($link) as $one) {
            $this->pages->drop($one);
            $dropped++;
        }

        return $dropped;
    }

    /**
     * Every form of one paper we drew: the plain one and the copy carrying our signature.
     *
     * The stamped copy is not a paper of its own - it is made by stamping the one beside it, and
     * both say the same thing. What came back is not touched: a scan is somebody's own page and
     * stands whatever becomes of what we drew.
     *
     * @param \Files\Model\Entity\FileLink $link One of them.
     * @return list<\Files\Model\Entity\FileLink>
     */
    private function everyVariantWeDrewOf(FileLink $link): array
    {
        $ours = array_map(
            fn(DocumentVariant $variant): string => $variant->value,
            array_filter(
                DocumentVariant::cases(),
                fn(DocumentVariant $variant): bool => $variant->isGeneratedByUs(),
            ),
        );

        /** @var list<\Files\Model\Entity\FileLink> $found */
        $found = $this->fetchTable('Files.FileLinks')
            ->find()
            ->where([
                'FileLinks.model' => $link->model,
                'FileLinks.foreign_key' => $link->foreign_key,
                'FileLinks.document_type' => $link->document_type,
                'FileLinks.variant IN' => array_values($ours),
            ])
            ->all()
            ->toList();

        return $found;
    }

    /**
     * Moves a page past the one beside it.
     *
     * @param \Files\Model\Entity\FileLink $link The page.
     * @param bool $up Whether it goes before the one above it rather than after the one below.
     * @return void
     */
    public function move(FileLink $link, bool $up): void
    {
        $this->pages->move($link, $up);
    }

    /**
     * Whether the server threw part of what was sent away before the application saw it.
     *
     * Fifty-five photographs from an installation came in as twenty, and neither the person who
     * sent them nor the application was told anything at all.
     *
     * @param array<mixed> $uploaded Everything the request carried, however deeply it is nested.
     * @return bool
     */
    public static function cutShort(array $uploaded): bool
    {
        return FiledPages::cutShort($uploaded);
    }

    /**
     * How many files the server will take out of one request.
     *
     * @return int
     */
    public static function atMostAtOnce(): int
    {
        return FiledPages::atMostAtOnce();
    }

    /**
     * What to tell somebody whose batch was cut.
     *
     * @return string
     */
    public static function shortfall(): string
    {
        return FiledPages::shortfall();
    }
}
