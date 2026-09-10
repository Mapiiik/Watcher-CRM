<?php
declare(strict_types=1);

namespace App\Service\CustomerPrint;

use App\Documents\PrintedDocument;
use App\Model\Entity\CustomerProposal;
use App\Model\Enum\CustomerPrintType;
use App\Model\Enum\DocumentVariant;
use Cake\ORM\Locator\LocatorAwareTrait;
use Files\Model\Entity\FileLink;
use Files\Model\Table\FileLinksTable;
use Files\Service\FileStorage;
use Throwable;

/**
 * The papers a round has, handed over rather than drawn again.
 *
 * The counterpart of {@see \App\Service\ContractPrint\ContractDocuments} and it keeps the same
 * promise: a document is drawn once, what comes out is kept, and every request for it afterwards
 * is answered with what was kept. That is what lets a consent signed a year ago come back today
 * saying exactly what the customer agreed to, whatever the records have done since - which matters
 * more here than anywhere, because these papers list what we hold about somebody.
 *
 * Simpler than the contract's in one way: a consent carries the customer's signature and not ours,
 * so there is no copy to stamp and no second variant to keep.
 */
final class CustomerDocuments
{
    use LocatorAwareTrait;

    /**
     * What the papers hang on. The round rather than the customer: a customer is asked more than
     * once over the years, and it is the round that tells this asking from the last one.
     */
    public const MODEL = 'CustomerProposals';

    /**
     * Where the papers are kept, and what draws one when there is none to hand over.
     */
    private FileStorage $storage;
    private CustomerPrintPdfOutput $output;

    /**
     * @param \Files\Service\FileStorage|null $storage Where the papers are kept.
     * @param \App\Service\CustomerPrint\CustomerPrintPdfOutput|null $output What draws them.
     */
    public function __construct(?FileStorage $storage = null, ?CustomerPrintPdfOutput $output = null)
    {
        $this->storage = $storage ?? new FileStorage();
        $this->output = $output ?? new CustomerPrintPdfOutput();
    }

    /**
     * The paper that was asked for.
     *
     * @param \App\Service\CustomerPrint\CustomerPrintData $data What is wanted.
     * @return \App\Documents\PrintedDocument
     */
    public function for(CustomerPrintData $data): PrintedDocument
    {
        // Nothing to file it against. Papers are drawn from a round and this is not one, so it is
        // handed over and forgotten rather than kept somewhere nothing can find it again.
        if ($data->proposal === null) {
            return $this->output->document($data);
        }

        return $this->onFile($data)
            ?? $this->keep($data, $this->output->document($data));
    }

    /**
     * What a handful of rounds have on file, sorted into the shape a page reads them in.
     *
     * @param iterable<\App\Model\Entity\CustomerProposal> $proposals Whose papers.
     * @return array<string, array<string, array<string, list<\Files\Model\Entity\FileLink>>>>
     *   By round, then by document, then by variant.
     */
    public function filedAgainst(iterable $proposals): array
    {
        $keys = [];
        foreach ($proposals as $proposal) {
            $keys[] = (string)$proposal->id;
        }

        /** @var iterable<\Files\Model\Entity\FileLink> $found */
        $found = $this->fileLinks()
            ->find('forAny', model: self::MODEL, foreign_keys: $keys)
            ->contain(['Files'])
            ->all();

        $filed = [];
        foreach ($found as $link) {
            $filed[$link->foreign_key][$link->document_type][$link->variant][] = $link;
        }

        return $filed;
    }

    /**
     * The documents a round has actually been drawn up as.
     *
     * Only those, because nothing else can have come back. That is what makes offering them on
     * the signature page short enough to be worth having there at all.
     *
     * @param \App\Model\Entity\CustomerProposal $proposal Whose papers.
     * @return array<string, string> The document type, and how it reads.
     */
    public function printedTypes(CustomerProposal $proposal): array
    {
        $printed = [];

        foreach ($this->filedAgainst([$proposal])[(string)$proposal->id] ?? [] as $document_type => $byVariant) {
            $type = CustomerPrintType::tryFrom((string)$document_type);
            if ($type === null) {
                continue;
            }

            foreach (array_keys($byVariant) as $variant) {
                if (DocumentVariant::tryFrom((string)$variant)?->isDrawnUpByUs() ?? false) {
                    $printed[$type->value] = $type->label();
                    break;
                }
            }
        }

        return $printed;
    }

    /**
     * The paper the round already has, where it has one.
     *
     * @param \App\Service\CustomerPrint\CustomerPrintData $data What is wanted.
     * @return \App\Documents\PrintedDocument|null
     */
    private function onFile(CustomerPrintData $data): ?PrintedDocument
    {
        /** @var \Files\Model\Entity\FileLink|null $link */
        $link = $this->fileLinks()->find(
            'group',
            model: self::MODEL,
            foreign_key: (string)$data->proposal?->id,
            document_type: $data->type->value,
            variant: DocumentVariant::Generated->value,
        )
            ->contain(['Files'])
            ->first();

        if (!$link instanceof FileLink) {
            return null;
        }

        return new PrintedDocument(
            $this->storage->read($link->file),
            $link->downloadName(),
            $link->file->mime_type,
        );
    }

    /**
     * Keeps a paper, and hands back what was kept.
     *
     * @param \App\Service\CustomerPrint\CustomerPrintData $data What the paper is.
     * @param \App\Documents\PrintedDocument $document The paper.
     * @return \App\Documents\PrintedDocument
     */
    private function keep(CustomerPrintData $data, PrintedDocument $document): PrintedDocument
    {
        $file = $this->storage->store($document->bytes, $document->mimeType);

        try {
            $this->storage->link(
                $file,
                self::MODEL,
                (string)$data->proposal?->id,
                $data->type->value,
                DocumentVariant::Generated->value,
                ['name' => $document->filename],
            );
        } catch (Throwable $e) {
            // Two people asking for the same paper at the same moment: one of them files it and
            // the other finds it here, which is the answer it wanted anyway.
            $raced = $this->onFile($data);
            if (!$raced instanceof PrintedDocument) {
                throw $e;
            }

            return $raced;
        }

        return $document;
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
