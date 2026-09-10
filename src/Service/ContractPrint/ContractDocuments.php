<?php
declare(strict_types=1);

namespace App\Service\ContractPrint;

use App\Documents\PrintedDocument;
use App\Model\Entity\ContractProposal;
use App\Model\Enum\ContractPrintType;
use App\Model\Enum\DocumentVariant;
use App\Pdf\SignatureAnchors;
use App\Pdf\SignatureStampPDF;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Files\Model\Entity\FileLink;
use Files\Model\Table\FileLinksTable;
use Files\Service\FileStorage;
use Throwable;

/**
 * The papers a proposal has, handed over rather than drawn again.
 *
 * A document is drawn once. What comes out is kept, and every request for it afterwards is
 * answered with what was kept - so the paper somebody signed a year ago is the paper that comes
 * back today, whatever the records have done since.
 *
 * The copy with our signature on it is not drawn at all: the unsigned paper is obtained first,
 * kept, and ours is stamped onto that. Two papers on file from one drawing, and the signed one
 * is by construction the unsigned one with a signature - not a second setting that ought to look
 * like it.
 */
final class ContractDocuments
{
    use LocatorAwareTrait;

    /**
     * What the papers hang on. A proposal rather than a contract: the proposal is what the paper
     * was drawn from, and it is the thing that does not move afterwards.
     */
    public const MODEL = 'ContractProposals';

    /**
     * Where the papers are kept, and what draws one when there is none to hand over.
     */
    private FileStorage $storage;
    private ContractPrintPdfOutput $output;

    /**
     * @param \Files\Service\FileStorage|null $storage Where the papers are kept.
     * @param \App\Service\ContractPrint\ContractPrintPdfOutput|null $output What draws them.
     */
    public function __construct(?FileStorage $storage = null, ?ContractPrintPdfOutput $output = null)
    {
        $this->storage = $storage ?? new FileStorage();
        $this->output = $output ?? new ContractPrintPdfOutput();
    }

    /**
     * The paper that was asked for.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data What is wanted.
     * @return \App\Documents\PrintedDocument
     */
    public function for(ContractPrintData $data): PrintedDocument
    {
        $wanted = DocumentVariant::forPrinting($data->signed);

        // Nothing to file it against. Papers are drawn from a proposal and this is not one, so it
        // is handed over and forgotten rather than kept somewhere nothing can find it again.
        if ($data->proposal === null) {
            return $this->draw($data, $data->signed);
        }

        $onFile = $this->onFile($data, $wanted);
        if ($onFile instanceof PrintedDocument) {
            return $onFile;
        }

        $base = $this->onFile($data, DocumentVariant::Generated)
            ?? $this->keep($data, $this->draw($data, false), DocumentVariant::Generated);

        // A paper with nowhere to sign is handed over as it is. The summary is the one: it says
        // what is on offer before anybody is bound by it, so it carries no signature block and no
        // mark to put one in. Asked of the paper rather than of a list of which papers those are,
        // because the paper is what knows.
        if ($wanted === DocumentVariant::Generated || !$this->maySignIt($base)) {
            return $base;
        }

        return $this->keep($data, $this->stamp($data, $base), DocumentVariant::GeneratedSignedByUs);
    }

    /**
     * Whether there is anywhere on this paper for our signature to go.
     *
     * @param \App\Documents\PrintedDocument $base The paper as it stands.
     * @return bool
     */
    private function maySignIt(PrintedDocument $base): bool
    {
        return SignatureAnchors::fromPdf($base->bytes)->find(SignatureAnchors::PROVIDER_SIGNATURE) !== null;
    }

    /**
     * What papers a handful of proposals have, sorted into the shape a page reads them in.
     *
     * Three pages ask this - the papers on a proposal, the summary of them on the proposal
     * itself, and the shortcut on the contract's printing page - and they all ask it of the same
     * one asking, so that a contract with six proposals is one query rather than six.
     *
     * @param iterable<\App\Model\Entity\ContractProposal> $proposals Whose papers.
     * @return array<string, array<string, array<string, list<\Files\Model\Entity\FileLink>>>>
     *   By proposal, then by document, then by variant.
     */
    public function filedAgainst(iterable $proposals): array
    {
        $keys = [];
        foreach ($proposals as $proposal) {
            $keys[] = (string)$proposal->id;
        }

        /** @var \Files\Model\Table\FileLinksTable $links */
        $links = $this->fetchTable(FileLinksTable::class);

        /** @var iterable<\Files\Model\Entity\FileLink> $found */
        $found = $links->find('forAny', model: self::MODEL, foreign_keys: $keys)->contain(['Files'])->all();

        $filed = [];
        foreach ($found as $link) {
            $filed[$link->foreign_key][$link->document_type][$link->variant][] = $link;
        }

        return $filed;
    }

    /**
     * The documents a proposal has actually been drawn up as.
     *
     * Only those, because nothing else can have come back. That is what makes offering them on
     * the signature page short enough to be worth having there at all.
     *
     * @param \App\Model\Entity\ContractProposal $proposal Whose papers.
     * @return array<string, string> The document type, and how it reads.
     */
    public function printedTypes(ContractProposal $proposal): array
    {
        $printed = [];

        foreach ($this->filedAgainst([$proposal])[(string)$proposal->id] ?? [] as $document_type => $byVariant) {
            $type = ContractPrintType::tryFrom((string)$document_type);
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
     * Draws the paper.
     *
     * The enrichment happens here rather than before, so that handing over a paper already on
     * file does not go asking the network what it looks like today. What the paper says was
     * settled when it was drawn.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data What to draw.
     * @param bool $signed Only what to call it - nothing is signed by drawing.
     * @return \App\Documents\PrintedDocument
     */
    private function draw(ContractPrintData $data, bool $signed): PrintedDocument
    {
        (new ContractPrintDataEnricher())->enrich($data);

        $drawn = $this->output->document($data);

        return $signed
            ? new PrintedDocument($drawn->bytes, $this->output->filename($data, true))
            : $drawn;
    }

    /**
     * Puts our signature onto a paper that is already finished.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data What the paper is.
     * @param \App\Documents\PrintedDocument $base The paper as it stands.
     * @return \App\Documents\PrintedDocument
     */
    private function stamp(ContractPrintData $data, PrintedDocument $base): PrintedDocument
    {
        return new PrintedDocument(
            (new SignatureStampPDF())->stamp(
                $base->bytes,
                SignatureAnchors::fromPdf($base->bytes),
                Date::now(),
            ),
            $this->output->filename($data, true),
        );
    }

    /**
     * The paper in this hand, where the proposal already has one.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data What is wanted.
     * @param \App\Model\Enum\DocumentVariant $variant Which variant of the document.
     * @return \App\Documents\PrintedDocument|null
     */
    private function onFile(ContractPrintData $data, DocumentVariant $variant): ?PrintedDocument
    {
        $link = $this->link($data, $variant);

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
     * @param \App\Service\ContractPrint\ContractPrintData $data What the paper is.
     * @param \App\Documents\PrintedDocument $document The paper.
     * @param \App\Model\Enum\DocumentVariant $variant Which variant of the document.
     * @return \App\Documents\PrintedDocument
     */
    private function keep(
        ContractPrintData $data,
        PrintedDocument $document,
        DocumentVariant $variant,
    ): PrintedDocument {
        $file = $this->storage->store($document->bytes, $document->mimeType);

        try {
            $this->storage->link(
                $file,
                self::MODEL,
                (string)$data->proposal?->id,
                $data->type->value,
                $variant->value,
                ['name' => $document->filename],
            );
        } catch (Throwable $e) {
            // Two people asking for the same paper at the same moment: one of them files it and
            // the other finds it here, which is the answer it wanted anyway.
            $raced = $this->onFile($data, $variant);
            if (!$raced instanceof PrintedDocument) {
                throw $e;
            }

            return $raced;
        }

        return $document;
    }

    /**
     * @param \App\Service\ContractPrint\ContractPrintData $data What is wanted.
     * @param \App\Model\Enum\DocumentVariant $variant Which variant of the document.
     * @return \Files\Model\Entity\FileLink|null
     */
    private function link(ContractPrintData $data, DocumentVariant $variant): ?FileLink
    {
        /** @var \Files\Model\Table\FileLinksTable $links */
        $links = $this->fetchTable(FileLinksTable::class);

        /** @var \Files\Model\Entity\FileLink|null $link */
        $link = $links->find(
            'group',
            model: self::MODEL,
            foreign_key: (string)$data->proposal?->id,
            document_type: $data->type->value,
            variant: $variant->value,
        )
            ->contain(['Files'])
            ->first();

        return $link;
    }
}
