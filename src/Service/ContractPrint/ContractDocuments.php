<?php
declare(strict_types=1);

namespace App\Service\ContractPrint;

use App\Documents\PrintedDocument;
use App\Model\Entity\ContractProposal;
use App\Model\Enum\ContractDocumentType;
use App\Model\Enum\DocumentVariant;
use App\Model\Enum\ProposalLinesLock;
use App\Pdf\SignatureAnchors;
use App\Pdf\SignatureStampPDF;
use App\Proposals\FiledPapersTrait;
use Cake\I18n\Date;
use Cake\ORM\Query\SelectQuery;
use Files\Model\Entity\FileLink;
use Files\Service\FileStorage;
use RuntimeException;
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
     * Why the lines of the proposal may not be changed, where a paper drawn from it stands in the way.
     *
     * Only papers of ours count. What the other side wrote - a notice, a certificate - is not drawn
     * from the lines, so it says nothing a changed line could contradict.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal being asked about.
     * @return \App\Model\Enum\ProposalLinesLock|null
     */
    public function linesLock(ContractProposal $proposal): ?ProposalLinesLock
    {
        $variants = $this->filedOurs($proposal)
            ->select(['FileLinks.variant'])
            ->distinct(['FileLinks.variant'])
            ->all()
            ->extract('variant')
            ->toList();

        $lock = null;

        foreach ($variants as $variant) {
            $variant = DocumentVariant::tryFrom((string)$variant);

            if ($variant?->carriesTheCustomersSignature() ?? false) {
                return ProposalLinesLock::SignedByTheCustomer;
            }

            if ($variant?->isGeneratedByUs() ?? false) {
                $lock = ProposalLinesLock::PapersDrawn;
            }
        }

        return $lock;
    }

    /**
     * The documents we drew from the proposal ourselves, as they are on file.
     *
     * The copies that went out from here and nothing that came back: a signed copy is the customer's
     * answer, and that is not ours to throw away.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return array<\Files\Model\Entity\FileLink>
     */
    public function drawnFrom(ContractProposal $proposal): array
    {
        /** @var array<\Files\Model\Entity\FileLink> $drawn */
        $drawn = $this->filedOurs($proposal)
            ->where(['FileLinks.variant IN' => [
                DocumentVariant::Generated->value,
                DocumentVariant::GeneratedSignedByUs->value,
            ]])
            ->toArray();

        return $drawn;
    }

    /**
     * Whether any paper is filed against these papers.
     *
     * A document that was drawn or came back is the record of something that happened, and letting
     * the papers go would leave it pointing at nothing - reachable from nowhere, and holding on to
     * its file for ever.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The papers being asked about.
     * @return bool
     */
    public function anyFiledAgainst(ContractProposal $proposal): bool
    {
        return $this->fileLinks()->exists([
            'FileLinks.model' => self::MODEL,
            'FileLinks.foreign_key' => $proposal->id,
        ]);
    }

    /**
     * What is on file against the proposal of the kinds of paper we draw.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return \Cake\ORM\Query\SelectQuery<\Files\Model\Entity\FileLink>
     */
    private function filedOurs(ContractProposal $proposal): SelectQuery
    {
        $ours = [];

        foreach (ContractDocumentType::cases() as $type) {
            if ($type->canBeGenerated()) {
                $ours[] = $type->value;
            }
        }

        /** @var \Cake\ORM\Query\SelectQuery<\Files\Model\Entity\FileLink> $query */
        $query = $this->fileLinks()
            ->find()
            ->where([
                'FileLinks.model' => self::MODEL,
                'FileLinks.foreign_key' => $proposal->id,
                'FileLinks.document_type IN' => $ours,
            ]);

        return $query;
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

        // A paper with nowhere for our signature is handed over as it is. The summary is the one:
        // at most it has a line for the customer, and no mark for us. Asked of the paper rather
        // than of a list of which papers those are, because the paper is what knows.
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
        // The clauses on what a commitment costs follow who the customer is in law, so a paper is
        // not drawn without it. One already on file is handed over as it was, which is why this is
        // asked only here.
        if ($data->contract->customer->legal_status === null) {
            throw new RuntimeException(__(
                'The customer has no legal status, and the clauses of the papers follow it. Choose it'
                . ' on the customer and recreate the contract proposal.',
            ));
        }

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
