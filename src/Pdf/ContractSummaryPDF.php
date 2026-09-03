<?php
declare(strict_types=1);

namespace App\Pdf;

use App\Model\Entity\ContractVersion;
use App\Model\Entity\Queue;
use App\Pdf\Trait\ContractDurationTrait;
use App\Service\ContractPrint\ContractPrintData;
use Cake\I18n\Date;
use Cake\I18n\Number;
use InvalidArgumentException;
use PhpCollective\DecimalObject\Decimal;
use Settings\Utility\Settings;

/**
 * The contract summary required by Regulation (EU) 2019/2243 and § 63 of the Czech
 * Electronic Communications Act.
 *
 * It is not a short contract. The template exists so a customer can lay this page beside
 * another provider's and compare them, which is why the section order, the opening bullets
 * and even where the service and provider names sit are prescribed.
 *
 * One A4 page is the length asked for, and three where services and equipment are bundled
 * into a single contract around an internet access service - which is what ours are. A plain
 * tariff on an indefinite contract fits the one page. A commitment does not, because the
 * clause on what leaving early costs has to be there too, so it runs to a second.
 *
 * It is laid out as a sibling of the contract - same logo, same title block, same headed
 * sections over a rule - so the two read as one set of papers. Its own text is set two
 * points larger than the contract's, because the regulation asks for at least ten.
 */
class ContractSummaryPDF extends AppPDF
{
    use ContractDurationTrait;

    /**
     * Ten points, which is the smallest allowed outright. Going under it takes a reason, and
     * then the summary has to be enlargeable on screen or available at ten points on request.
     * There is nothing to buy with the difference anyway: the length this document is allowed
     * leaves no reason to shrink the type to save a page.
     */
    protected const BODY_FONT_SIZE = 10;

    /**
     * Section headings, a step above the body as in the contract.
     */
    protected const HEADING_FONT_SIZE = 11;

    /**
     * The footnote to the first bullet, which is apparatus rather than summary text and is
     * therefore the one thing on the page allowed under ten points.
     */
    protected const NOTE_FONT_SIZE = 8;

    /**
     * Line height that goes with the body size.
     */
    protected const LINE_HEIGHT = 4.5;

    /**
     * Air between one block of prose and the next. Whatever is not a paragraph - a list, a row
     * of contacts, a statement set in bold - leaves the same gap behind it, or the paragraph
     * that follows reads as its continuation.
     */
    protected const PARAGRAPH_GAP = 1.5;

    /**
     * This document keeps its blocks whole: it is read to be compared, and a paragraph or a
     * table broken over the fold is the one thing that defeats that.
     */
    protected const KEEPS_BLOCKS_WHOLE = true;

    /**
     * The tables state the figures the offer is judged on, so they line up with the paragraphs
     * around them rather than indenting off them, and their rows carry the taller body text.
     */
    protected const TABLE_INDENT = 0.0;
    protected const TABLE_ROW_HEIGHT = 6.0;

    /**
     * Line height that goes with the footnote.
     */
    private const FOOTNOTE_LINE_HEIGHT = 3.2;

    /**
     * Sans-serif, which the Office's manual allows in as many words: commonly used sans-serif
     * faces may be used in the interest of legibility. It is the one place this document parts
     * company with the contract's serif, and it also reads lighter at the same size.
     */
    protected const FONT_FAMILY = 'dejavusans';

    /**
     * Foot of every page kept clear, because the footnote to the opening bullet is written
     * into it after the sections have been laid out and must not land on top of them.
     */
    private const FOOTNOTE_RESERVE = 14.0;

    /**
     * Kilobits in a megabit, the unit the speed table is stated in. Binary, because that is
     * how the tariffs hold their speeds - a ten megabit tariff is stored as 10240.
     */
    private const KBPS_PER_MBPS = 1024;

    /**
     * Renders the contract summary.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     * @throws \InvalidArgumentException If the contract has no number or no version to summarise
     */
    public function generateContractSummary(ContractPrintData $data): void
    {
        $contract = $data->contract;
        $contractVersion = $data->contractVersionToBeExecuted;

        if ($contract->number === null) {
            throw new InvalidArgumentException('Contract number must be provided in order to generate a summary');
        }

        if (!$contractVersion instanceof ContractVersion) {
            throw new InvalidArgumentException('The contract version to be executed must be provided');
        }

        $this->SetAutoPageBreak(true, self::FOOTNOTE_RESERVE);

        // The template asks for the name of the service on offer directly above the title and
        // the provider's name directly below it, so that two summaries laid side by side name
        // what they are describing in the same place. The provider is named once, in the block
        // the template asks it for; naming the company again under the title only takes up the
        // room the subtitle needs.
        $this->printDocumentHeader(
            $this->summaryText('title'),
            $this->summaryText('subtitle'),
            $this->serviceNames($data),
        );

        $this->printContractIdentification($contract->number, $contractVersion);
        $this->printIntro();
        $this->printIntroFootnote();
        $this->printProviderContact();
        $this->printComplaintContact();

        $this->printServices($data);
        $this->printSpeeds($data);

        // What the offer is, then what it costs and how long it binds - and the fold between
        // them is put here on purpose. Nearly every summary runs to two pages anyway, so the
        // page is spent on keeping the commercial half whole rather than on filling the first.
        $this->AddPage();

        $this->printPrice($data, $contractVersion);
        $this->printDuration($data, $contractVersion);
        $this->printAccessibility();
        $this->printOtherInformation();
    }

    /**
     * Says which contract this summary belongs to.
     *
     * The issue date is the template's own, and it is the one that matters here: the duty is to
     * hand the summary over before the customer is bound, so it has to say when it was made.
     *
     * @param string $contractNumber Number of the contract being summarised
     * @param \App\Model\Entity\ContractVersion $contractVersion Version being summarised
     * @return void
     */
    private function printContractIdentification(string $contractNumber, ContractVersion $contractVersion): void
    {
        $this->printLabelledRow([
            [$this->summaryText('labels.issue_date'), (string)Date::now()],
            [$this->label('contract_number'), $contractNumber],
            [$this->label('start_date'), (string)$contractVersion->valid_from],
        ]);
    }

    /**
     * The three opening bullets the template prescribes.
     *
     * @return void
     */
    private function printIntro(): void
    {
        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);

        foreach ($this->summaryList('intro_bullets') as $bullet) {
            $this->keepTogether($this->getNumLines($bullet, self::TEXT_WIDTH - 4) * self::LINE_HEIGHT);
            $this->Cell(4, self::LINE_HEIGHT, '•');
            $this->MultiCell(self::TEXT_WIDTH - 4, self::LINE_HEIGHT, $bullet, align: 'L');
        }

        $this->Ln(3);
    }

    /**
     * The provider, and how to reach them about a complaint.
     *
     * The contract's own company block says more than this - who signs for the company, which
     * register it sits in - but a summary is read to compare offers and to know where to
     * complain, so it carries the contact details and nothing else. Everything on this page
     * has to be set at ten points, which is the other reason the block is kept short.
     *
     * @return void
     */
    private function printProviderContact(): void
    {
        $label = fn(string $key): string => Settings::getString('core.documents.common.labels.' . $key);
        $company = fn(string $key): string => Settings::getString('core.company.' . $key);

        // Headed and ruled like the sections below it, which is also how the contract heads its
        // own company block - and it gives the details underneath the whole width to sit in.
        $this->SetFont(self::FONT_FAMILY, 'B', self::HEADING_FONT_SIZE);
        $this->Write(4, $this->summaryText('labels.provider'));
        $this->Ln();

        $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);

        $rows = [
            [$company('name'), $label('phone'), $company('phone'), true],
            [$company('address_line_1'), $label('mobile'), $company('mobile'), false],
            [$company('address_line_2'), $label('email'), $company('email'), false],
            [
                $label('identity_number') . ' ' . $company('identity_number')
                    . '   ' . $label('vat_number') . ' ' . $company('vat_number'),
                '',
                '',
                false,
            ],
        ];

        foreach ($rows as [$detail, $contactLabel, $contact, $emphasised]) {
            $this->SetFont(self::FONT_FAMILY, $emphasised ? 'B' : '', self::BODY_FONT_SIZE);
            $this->Cell(105, self::LINE_HEIGHT, $detail);
            $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
            $this->Cell(20, self::LINE_HEIGHT, $contactLabel);
            $this->Cell(55, self::LINE_HEIGHT, $contact);
            $this->Ln();
        }

        $this->Ln(self::PARAGRAPH_GAP);

        // The register entry is asked for beside the identification number, and it is too long
        // to sit in a column with the contacts.
        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->printParagraph($company('registry_clause'), 'L');

        $this->Ln(3);
    }

    /**
     * Where a complaint goes, when that is not where everything else goes.
     *
     * The template asks for it only when it differs from the contact details above, and here it
     * does - a fault goes to support, but a complaint about the service is contract business.
     *
     * @return void
     */
    private function printComplaintContact(): void
    {
        $label = fn(string $key): string => Settings::getString('core.documents.common.labels.' . $key);
        $company = fn(string $key): string => Settings::getString('core.company.' . $key);

        $email = $company('contracts.email');
        $phone = $company('contracts.phone');

        if ($email === $company('email') && $phone === $company('phone')) {
            return;
        }

        $this->SetFont(self::FONT_FAMILY, 'B', self::HEADING_FONT_SIZE);
        $this->Write(4, $this->summaryText('labels.complaints'));
        $this->Ln();

        $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);

        // The columns are the provider block's, so the telephone label starts in the same place
        // it does there rather than a centimetre to its left.
        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->Cell(20, self::LINE_HEIGHT, $label('email'));
        $this->SetFont(self::FONT_FAMILY, 'B', self::BODY_FONT_SIZE);
        $this->Cell(85, self::LINE_HEIGHT, $email);
        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->Cell(20, self::LINE_HEIGHT, $label('phone'));
        $this->SetFont(self::FONT_FAMILY, 'B', self::BODY_FONT_SIZE);
        $this->Cell(55, self::LINE_HEIGHT, $phone);
        $this->Ln();

        $this->Ln(3);
    }

    /**
     * The footnote the first bullet carries, in the foot of the page the marker is on.
     *
     * It is written straight after the bullet that carries the marker, while the document is
     * still demonstrably on the first page, and the cursor is put back where it was. Reaching
     * back for the page later does not work: the sections would already have moved on, and the
     * strip is only free in the first place because the page break keeps it clear.
     *
     * @return void
     */
    private function printIntroFootnote(): void
    {
        $footnote = $this->summaryText('intro_footnote');
        if ($footnote === '') {
            return;
        }

        $resume = $this->GetY();

        $this->SetAutoPageBreak(false);
        $this->SetY(-self::FOOTNOTE_RESERVE);
        $this->drawSeparator(width: 60.0, lnAfter: 0.8);
        $this->SetFont(self::FONT_FAMILY, '', self::NOTE_FONT_SIZE);
        $this->MultiCell(self::TEXT_WIDTH, self::FOOTNOTE_LINE_HEIGHT, $footnote, align: 'L');

        $this->SetAutoPageBreak(true, self::FOOTNOTE_RESERVE);
        $this->SetY($resume);
    }

    /**
     * Section one - what is being provided, and on what equipment.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    private function printServices(ContractPrintData $data): void
    {
        $this->printSummaryHeading('services');

        $this->SetFont(self::FONT_FAMILY, 'B', self::BODY_FONT_SIZE);
        foreach ($this->billings($data) as $billing) {
            $this->keepTogether($this->getNumLines($billing->name, self::TEXT_WIDTH) * self::LINE_HEIGHT);
            $this->MultiCell(self::TEXT_WIDTH, self::LINE_HEIGHT, $billing->name, align: 'L');
        }

        $this->Ln(self::PARAGRAPH_GAP);

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);

        // Not every contract carries a tariff - a standing charge can be all there is - and the
        // description of an internet access service does not belong on one that does not.
        if ($this->queue($data) instanceof Queue) {
            $this->printParagraph($this->summaryText('texts.service_note'));
        }

        // The template asks for the equipment beside the service, and the contract already
        // makes this distinction - what it lends it says so, what it does not it says so too.
        $this->printParagraph($this->summaryText('texts.' . match (true) {
            $this->lendsEquipment($data) => 'equipment_borrowed',
            $this->soldEquipment($data) !== [] => 'equipment_sold',
            default => 'equipment_own',
        }));

        if ($this->queue($data) instanceof Queue) {
            $this->printParagraph($this->dataLimitText($data));
        }

        $this->Ln(3);
    }

    /**
     * Section two - the three speeds in each direction, and what to do about a shortfall.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    private function printSpeeds(ContractPrintData $data): void
    {
        $this->printSummaryHeading('speeds');

        $queue = $this->queue($data);

        // Six dashes under this heading would read as a service that has no speed. Saying there
        // is no such service is the same fact, told the way a reader would take it.
        if (!$queue instanceof Queue) {
            $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
            $this->printParagraph($this->summaryText('texts.no_internet_service'));
            $this->Ln(2);

            return;
        }

        $label = fn(string $key): string => $this->summaryText('speed_labels.' . $key);

        // Ruled, because three numbers in each of two directions is a table and reads as one.
        // The widths add up to the text width exactly so the frame lines up with the paragraphs.
        $columns = [48.0, 44.0, 44.0, 44.0];

        // Headings wrap, so each gets a cell of the row's own height instead of one line -
        // squeezing the longest of them onto a single line is what pushed it out of its cell.
        $headings = [
            $label('direction'),
            $label('advertised'),
            $label('common'),
            $label('minimum'),
        ];
        $headingHeight = self::TABLE_ROW_HEIGHT * 2;
        // Heading plus the two directions - the table has no other shape.
        $this->keepTogether($headingHeight + 2 * self::TABLE_ROW_HEIGHT);
        $x = $this->GetX();
        $y = $this->GetY();

        $this->SetFont(self::FONT_FAMILY, 'B', self::BODY_FONT_SIZE);
        foreach ($headings as $index => $heading) {
            $this->MultiCell(
                $columns[$index],
                $headingHeight,
                $heading,
                border: 1,
                align: $index === 0 ? 'L' : 'C',
                ln: 0,
                maxh: $headingHeight,
                valign: 'M',
            );
        }

        $this->SetXY($x, $y + $headingHeight);

        $rows = [
            [
                $label('download'),
                $queue->getSpeedDown(),
                $queue->getSpeedDownCommon(),
                $queue->getSpeedDownMinimum(),
            ],
            [
                $label('upload'),
                $queue->getSpeedUp(),
                $queue->getSpeedUpCommon(),
                $queue->getSpeedUpMinimum(),
            ],
        ];

        foreach ($rows as [$direction, $advertised, $common, $minimum]) {
            $this->printFramedRow(
                [$direction, $this->mbps($advertised), $this->mbps($common), $this->mbps($minimum)],
                $columns,
                ['L', 'C', 'C', 'C'],
                [false, true, true, true],
            );
        }

        $this->Ln(1);
        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);

        // Prescribed wording, printed in black in the template. It says a slow line does not cut
        // the customer off from anything they have a right to reach - only makes it slower.
        $this->printParagraph($this->summaryText('texts.speed_rights_note'));

        $this->printParagraph($this->summaryText('texts.speed_remedies'));

        // A remedy refused is not the end of the road, and the template's example says so.
        $this->printParagraph($this->summaryText('texts.complaint_escalation'));
        $this->Ln(3);
    }

    /**
     * Section three - what it costs each month, and up front.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @param \App\Model\Entity\ContractVersion $contractVersion Version being summarised
     * @return void
     */
    private function printPrice(ContractPrintData $data, ContractVersion $contractVersion): void
    {
        $this->printSummaryHeading('price');

        $total = Decimal::create(0, 2);
        $undiscounted = Decimal::create(0, 2);
        $discount = Decimal::create(0, 2);
        $discountUntil = null;

        foreach ($this->billings($data) as $billing) {
            /** @psalm-suppress ImplicitToStringCast */
            $total = $total->add($billing->total_price);
            /** @psalm-suppress ImplicitToStringCast */
            $undiscounted = $undiscounted->add($billing->sum);
            /** @psalm-suppress ImplicitToStringCast */
            $discount = $discount->add($billing->discount);

            // The earliest day a discount runs out is the day the price the customer is comparing
            // stops applying, so that is the one the summary has to name.
            if (
                $billing->discount->isPositive()
                && $billing->billing_until !== null
                && ($discountUntil === null || $billing->billing_until < $discountUntil)
            ) {
                $discountUntil = $billing->billing_until;
            }
        }

        $amounts = [[$this->summaryText('labels.price_per_month'), $total]];

        // A promotional price compared against nothing is not a comparison. The template asks for
        // the undiscounted price and the period beside it, and this is the section it asks in.
        $discounted = $discount->isPositive();
        if ($discounted) {
            $amounts[] = [$this->summaryText('labels.price_without_discount'), $undiscounted];
        }

        // A commitment buys a cheaper activation fee, so the figure the customer actually pays
        // up front is the discounted one - the full fee only matters if they leave early. The
        // label has to say which of the two this is, or the comparison it invites is a false one.
        $committed = $contractVersion->minimum_duration > 0;
        $activationFee = $committed
            ? $data->contract->activation_fee_with_obligation_sum
            : $data->contract->activation_fee_sum;

        if ($activationFee->isPositive()) {
            $amounts[] = [
                $this->summaryText($committed
                    ? 'labels.activation_fee_with_obligation'
                    : 'labels.activation_fee'),
                $activationFee,
            ];
        }

        // Equipment the customer buys is a price the template asks for by name, and it is the
        // one figure on the page that the services section above can only point at.
        $equipment = $this->soldEquipmentTotal($data, $committed);
        if ($equipment->isPositive()) {
            $amounts[] = [$this->summaryText('labels.equipment_price'), $equipment];
        }

        $this->printAmounts($amounts);

        $this->Ln(1);

        if ($discounted) {
            $this->printParagraph(
                $discountUntil === null
                    ? $this->summaryText('texts.discount_open_ended')
                    : strtr($this->summaryText('texts.discount_period'), [
                        '{date}' => (string)$discountUntil,
                    ]),
                'L',
            );
        }

        $this->printParagraph($this->summaryText('texts.price_note'), 'L');
        $this->Ln(3);
    }

    /**
     * Section four - how long it runs, whether it renews, and how it ends.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @param \App\Model\Entity\ContractVersion $contractVersion Version being summarised
     * @return void
     */
    private function printDuration(ContractPrintData $data, ContractVersion $contractVersion): void
    {
        $this->printSummaryHeading('duration');

        $this->printParagraph(
            strtr(Settings::getString('core.documents.contracts.contract.texts.new_intro'), [
                '{minimum_duration}' => $this->contractDuration($contractVersion),
            ]),
            'L',
            true,
        );

        // A fixed term ends, an indefinite one does not, and what happens next differs enough
        // that one sentence cannot honestly cover both. The notice period belongs here either
        // way: the Act counts it among what the contract has to state.
        $this->printParagraph(strtr(
            $this->summaryText('texts.' . match (true) {
                $contractVersion->valid_until !== null => 'renewal_definite',
                $contractVersion->minimum_duration > 0 => 'renewal_indefinite_committed',
                default => 'renewal_indefinite',
            }),
            ['{notice_period}' => $this->summaryText('notice_period')],
        ));

        // What leaving early costs is a figure customers compare offers on, so the summary
        // repeats the contract's own clause rather than pointing at it - and repeats the same
        // one the contract picked, which turns on whether any equipment was lent with it.
        $contract = $data->contract;
        if ($contractVersion->minimum_duration > 0 && $contract->activation_fee_sum->isPositive()) {
            $clauseKey = $this->lendsEquipment($data)
                ? 'activation_fee_clause_with_installation'
                : 'activation_fee_clause';

            $this->printParagraph(strtr(
                Settings::getString('core.documents.contracts.contract.texts.' . $clauseKey),
                [
                    '{duration}' => $this->contractDurationBefore($contractVersion->minimum_duration),
                    '{difference}' => Number::currency(
                        $contract->activation_fee_sum
                            ->subtract($contract->activation_fee_with_obligation_sum)
                            ->toFloat(),
                    ),
                    '{full_fee}' => Number::currency($contract->activation_fee_sum->toFloat()),
                ],
            ));
        }

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->printParagraph($this->summaryText('texts.termination_other'));

        $this->Ln(3);
    }

    /**
     * Section five - the template keeps this heading even where nothing special is offered.
     *
     * @return void
     */
    private function printAccessibility(): void
    {
        $this->printSummaryHeading('accessibility');

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->printParagraph($this->summaryText('texts.accessibility'));
        $this->Ln(3);
    }

    /**
     * Section six - the documents the full terms actually live in.
     *
     * @return void
     */
    private function printOtherInformation(): void
    {
        $this->printSummaryHeading('other');

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        foreach ($this->summaryList('other_information') as $document) {
            $this->keepTogether($this->getNumLines($document, self::TEXT_WIDTH - 4) * self::LINE_HEIGHT);
            $this->Cell(4, self::LINE_HEIGHT, '•');
            $this->MultiCell(self::TEXT_WIDTH - 4, self::LINE_HEIGHT, $document, align: 'L');
        }

        $this->Ln(self::PARAGRAPH_GAP);

        $this->printParagraph($this->summaryText('other_information_note'), 'L');
    }

    /**
     * Prints one of the template's section headings, the way the contract heads its own.
     *
     * Ten services still fit on the page; beyond that the summary runs over, which the
     * regulation allows for a bundle.
     *
     * @param string $key Key under the summary's sections block
     * @return void
     */
    private function printSummaryHeading(string $key): void
    {
        $this->printSectionHeading($this->summaryText('sections.' . $key));
    }

    /**
     * Prints the price figures as a ruled table.
     *
     * @param array<int, array{0:string, 1:\PhpCollective\DecimalObject\Decimal}> $amounts Label and amount pairs
     * @return void
     */
    private function printAmounts(array $amounts): void
    {
        // Ruled like the speed table: these are the figures the offer is judged on, and a frame
        // is what tells the eye that at a glance.
        $this->keepTogether(count($amounts) * self::TABLE_ROW_HEIGHT);

        foreach ($amounts as [$label, $amount]) {
            $this->printFramedRow(
                [$label, Number::currency($amount->toFloat())],
                [130, 50],
                ['L', 'R'],
                [false, true],
            );
        }
    }

    /**
     * How the summary states the tariff's data limit.
     *
     * A limit here is not a throttle: the tariff carries a price for the excess, and saying
     * the speed drops instead would describe a service the customer is not buying.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return string
     */
    private function dataLimitText(ContractPrintData $data): string
    {
        $queue = $this->queue($data);
        $dataLimit = $queue?->data_limit;

        if ($dataLimit === null) {
            return $this->summaryText('texts.no_data_limit');
        }

        if ($queue?->overlimit_fragment === null || $queue->overlimit_cost === null) {
            return strtr($this->summaryText('texts.data_limit_plain'), [
                '{data_limit}' => Number::toReadableSize($dataLimit),
            ]);
        }

        return strtr($this->summaryText('texts.data_limit'), [
            '{data_limit}' => Number::toReadableSize($dataLimit),
            '{overlimit_fragment}' => Number::toReadableSize($queue->overlimit_fragment),
            '{overlimit_cost}' => Number::currency((float)$queue->overlimit_cost),
        ]);
    }

    /**
     * Equipment sold with the contract, if any.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return array<int, \App\Model\Entity\SoldEquipment>
     */
    private function soldEquipment(ContractPrintData $data): array
    {
        return $data->contract->sold_equipments ?? [];
    }

    /**
     * What that equipment costs the customer.
     *
     * A commitment buys the cheaper price where the equipment type carries one, which is the
     * rule the handover protocol already applies - the summary must not quote a different total
     * from the paperwork the customer signs beside it.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @param bool $committed Whether a minimum duration was agreed
     * @return \PhpCollective\DecimalObject\Decimal
     */
    private function soldEquipmentTotal(ContractPrintData $data, bool $committed): Decimal
    {
        $total = Decimal::create(0, 2);

        foreach ($this->soldEquipment($data) as $sold) {
            $type = $sold->equipment_type ?? null;
            $price = $committed && isset($type->price, $type->price_with_obligation)
                ? $type->price_with_obligation
                : $type->price ?? null;

            if ($price !== null) {
                /** @psalm-suppress ImplicitToStringCast */
                $total = $total->add($price);
            }
        }

        return $total;
    }

    /**
     * Whether any equipment is lent with the contract.
     *
     * The contract decides on the same question which wording its equipment and activation
     * fee clauses take, so the summary asks it the same way rather than guessing.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return bool
     */
    private function lendsEquipment(ContractPrintData $data): bool
    {
        return count($data->contract->borrowed_equipments ?? []) > 0;
    }

    /**
     * Name of the service on offer, for the line the template puts above the title.
     *
     * The template wants the offer named, not itemised - the itemising is what the services
     * section below is for. It is the tariff the customer chose between providers on, so a
     * billing that carries one is what gets named, and the surcharges riding along with it
     * stay out of the heading.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return string
     */
    private function serviceNames(ContractPrintData $data): string
    {
        $names = [];

        foreach ($this->billings($data) as $billing) {
            if (!$billing->service?->queue instanceof Queue) {
                continue;
            }

            if ($billing->name !== '' && !in_array($billing->name, $names, true)) {
                $names[] = $billing->name;
            }
        }

        return implode(', ', $names);
    }

    /**
     * Billings the summary describes - the ones running at the start of the version.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return array<int, \App\Model\Entity\Billing>
     */
    private function billings(ContractPrintData $data): array
    {
        return $data->activeBillings->toList();
    }

    /**
     * Tariff whose speeds the summary states.
     *
     * A connection can carry more than one billing, but the speed is a property of the internet
     * access service, so the fastest one is what the customer is being offered.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return \App\Model\Entity\Queue|null
     */
    private function queue(ContractPrintData $data): ?Queue
    {
        $fastest = null;

        foreach ($this->billings($data) as $billing) {
            $queue = $billing->service?->queue;
            if (!$queue instanceof Queue) {
                continue;
            }

            if ($fastest === null || (int)$queue->getSpeedDown() > (int)$fastest->getSpeedDown()) {
                $fastest = $queue;
            }
        }

        return $fastest;
    }

    /**
     * States a speed in the unit the template asks for.
     *
     * @param int|null $kbps Speed in kbps, as the tariff holds it
     * @return string
     */
    private function mbps(?int $kbps): string
    {
        if ($kbps === null) {
            return '-';
        }

        return Number::format($kbps / self::KBPS_PER_MBPS, ['places' => 0])
            . ' ' . $this->summaryText('speed_labels.unit');
    }

    /**
     * Reads one of the summary's texts.
     *
     * @param string $key Key under the summary block
     * @return string
     */
    private function summaryText(string $key): string
    {
        return Settings::getString('core.documents.contracts.summary.' . $key);
    }

    /**
     * Reads one of the summary's lists.
     *
     * @param string $key Key under the summary block
     * @return array<int, string>
     */
    private function summaryList(string $key): array
    {
        /** @var array<int, string> $values */
        $values = (array)Settings::get('core.documents.contracts.summary.' . $key, []);

        return $values;
    }
}
