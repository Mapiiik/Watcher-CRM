<?php
declare(strict_types=1);

namespace App\Service\ContractPrint;

use App\Documents\PrintedDocument;
use App\Model\Enum\ContractDocumentType;
use App\Model\Enum\DocumentVariant;
use App\Pdf\SignatureAnchors;
use App\Pdf\SignatureStampPDF;
use App\Proposals\FiledPapersTrait;
use Cake\I18n\Date;
use Files\Model\Entity\FileLink;
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
    use FiledPapersTrait;

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
     * The documents a proposal may be drawn up as.
     *
     * @return array<string, string> By the value they are filed under.
     */
    public function documentLabels(): array
    {
        $labels = [];

        foreach (ContractDocumentType::cases() as $case) {
            $labels[$case->value] = $case->label();
        }

        return $labels;
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
        /** @var \Files\Model\Entity\FileLink|null $link */
        $link = $this->fileLinks()->find(
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
