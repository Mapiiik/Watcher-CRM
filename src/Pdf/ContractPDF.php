<?php
declare(strict_types=1);

namespace App\Pdf;

use App\Model\Entity\Billing;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersion;
use App\Model\Enum\ContractDocumentType;
use App\Model\Enum\CustomerLegalStatus;
use App\Pdf\Trait\BillingTableTrait;
use App\Pdf\Trait\ContractDurationTrait;
use App\Service\ContractPrint\ContractPrintData;
use Cake\I18n\Date;
use Cake\I18n\Number;
use InvalidArgumentException;
use PhpCollective\DecimalObject\Decimal;
use Settings\Utility\Settings;

/**
 * The papers a customer signs: the contract itself, an amendment to it and its termination.
 */
class ContractPDF extends ContractPaperPDF
{
    use BillingTableTrait;
    use ContractDurationTrait;

    /**
     * Generate PDF document - contract
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    public function generateContract(ContractPrintData $data): void
    {
        $this->assertContractData($data);

        $contract = $data->contract;

        [$title, $subtitle] = $this->contractTitle($data->type);
        $this->printDocumentHeader($title, $subtitle);

        $this->printContractIdentification($data);

        $this->printParties($this->label('provider'), $contract);
        $this->printSubscriberVerificationCode($contract);
        $this->Ln();
        $this->drawSeparator(lnAfter: 0.5);

        $this->printCustomerBlock($contract);
        $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X, lnAfter: 3.0);

        $this->printContractIntro($data);
        $this->printBillingAndPayment($data);
        $this->printAmendmentClosing($data->type);
        $this->printEquipmentAndFinalStatements($data);
        $this->printConsumerStatements($data);

        $this->printSignatureSection('double');

        $this->printWithdrawalAttachment($data);

        $this->Close();
    }

    /**
     * Whether the paper is one a consumer may withdraw from, and the customer is one.
     *
     * Consumer law gives that right to a natural person outside their trade, so a sole trader and
     * a legal entity are left out. A contract is made or changed by the paper; an ending is not.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return bool
     */
    private function speaksToAConsumer(ContractPrintData $data): bool
    {
        if (
            !in_array($data->type, [
                ContractDocumentType::ContractNew,
                ContractDocumentType::ContractNewX,
                ContractDocumentType::ContractAmendment,
            ], true)
        ) {
            return false;
        }

        return in_array($data->contract->customer->legal_status, [
            CustomerLegalStatus::NaturalPerson,
            null,
        ], true);
    }

    /**
     * What a consumer is told and asked above the signatures, so that signing covers it.
     *
     * The right to withdraw and the request to start the service before the period runs out, then
     * the consent to record a status that gives the customer rights. Each prints only when the
     * deployment has a text for it.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    private function printConsumerStatements(ContractPrintData $data): void
    {
        if (!$this->speaksToAConsumer($data)) {
            return;
        }

        $withdrawal = trim($this->contractText('texts.withdrawal_text'));
        $consent = trim($this->contractText('texts.special_status_consent_text'));

        if ($withdrawal === '' && $consent === '') {
            return;
        }

        $paragraphs = [];
        foreach ([$withdrawal, $consent] as $text) {
            foreach (preg_split('/\R\s*\R/', $text) ?: [] as $paragraph) {
                if (trim($paragraph) !== '') {
                    $paragraphs[] = trim($paragraph);
                }
            }
        }

        $heading = $withdrawal !== '' ? $this->contractText('sections.withdrawal') : '';

        // What the signatures cover goes over the fold with them, never leaving them alone on a page.
        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $height = 3 + ($heading !== '' ? self::SECTION_HEADING_HEIGHT : 0) + static::SIGNATURE_BLOCK;
        foreach ($paragraphs as $paragraph) {
            $height += $this->paragraphHeight($paragraph);
        }
        $this->keepTogether($height);

        $this->Ln(3);

        if ($heading !== '') {
            $this->printSectionHeading($heading);
        }

        foreach ($paragraphs as $paragraph) {
            $this->printParagraph($paragraph);
        }
    }

    /**
     * The notice on withdrawal and the form for it, on a page of their own after the signatures.
     *
     * A page of its own, because the form is meant to be cut off and sent back.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    private function printWithdrawalAttachment(ContractPrintData $data): void
    {
        $html = trim($this->contractText('texts.withdrawal_attachment_html'));

        if ($html === '' || !$this->speaksToAConsumer($data)) {
            return;
        }

        $this->AddPage();

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->setListIndentWidth(4);
        $this->writeHTML(strtr($html, [
            '{contract_number}' => (string)$data->contract->number,
        ]), true, false, false, true, '');
    }

    /**
     * Assert that everything a contract needs is present in the print data object, otherwise
     * throw with a message that says which piece is missing.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     * @throws \InvalidArgumentException When the data does not carry what the type requires
     */
    private function assertContractData(ContractPrintData $data): void
    {
        $type = $data->type;

        if ($data->contract->number === null) {
            throw new InvalidArgumentException('Contract number must be provided in order to generate a contract');
        }

        if (
            in_array(
                $type,
                [
                    ContractDocumentType::ContractNew,
                    ContractDocumentType::ContractNewX,
                    ContractDocumentType::ContractAmendment,
                ],
                true,
            )
            && $data->contractVersionToBeExecuted === null
        ) {
            throw new InvalidArgumentException(
                'The contract version to be executed must be provided'
                    . ' in order to generate a contract amendment or a new contract',
            );
        }

        if (
            (
                $type === ContractDocumentType::ContractNewX
                || $type === ContractDocumentType::ContractTermination
            )
            && $data->contractVersionToBeTerminated === null
        ) {
            throw new InvalidArgumentException(
                'The contract version to be terminated must be provided'
                    . ' in order to generate a contract termination or a new replacement contract',
            );
        }

        if (
            (
                $type === ContractDocumentType::ContractNewX
                || $type === ContractDocumentType::ContractTermination
            )
            && $data->contractVersionToBeTerminated->conclusion_date === null
        ) {
            throw new InvalidArgumentException(
                'The contract version to be terminated with valid conclusion date must be provided'
                    . ' in order to generate a contract termination or a new replacement contract',
            );
        }
    }

    /**
     * Assert that all required data for contract amendment generation is present in the print data object,
     * otherwise throw an exception with clear message indicating missing data
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     * @phpstan-assert !null $data->effectiveDateOfAmendment
     */
    private function assertAmendmentData(ContractPrintData $data): void
    {
        if (!$data->effectiveDateOfAmendment instanceof Date) {
            throw new InvalidArgumentException(
                'Effective date of amendment must be provided in order to generate a contract amendment',
            );
        }
    }

    /**
     * Title and subtitle the contract goes out under.
     *
     * A replacement contract is a new contract as far as the customer is concerned, so it is
     * headed like one; what it replaces is said in the body.
     *
     * @param \App\Model\Enum\ContractDocumentType $type Contract print type
     * @return array Title and subtitle
     * @throws \InvalidArgumentException When the type is not one this generator prints
     * @phpstan-return array{0: string, 1: string}
     */
    private function contractTitle(ContractDocumentType $type): array
    {
        return match ($type) {
            ContractDocumentType::ContractNew,
            ContractDocumentType::ContractNewX => [
                $this->contractText('title_new'),
                $this->contractText('subtitle_new'),
            ],
            ContractDocumentType::ContractAmendment => [
                $this->contractText('title_amendment'),
                $this->contractText('subtitle_amendment'),
            ],
            ContractDocumentType::ContractTermination => [
                $this->contractText('title_termination'),
                $this->contractText('subtitle_termination'),
            ],
            default => throw new InvalidArgumentException('Unsupported contract print type: ' . $type->value),
        };
    }

    /**
     * Says which contract this is, and which dates bound it.
     *
     * Each type is identified by different figures - a new contract by when it starts, an
     * amendment by which one in the series it is, a termination by when it ends - so each
     * gets its own row rather than a shared one with blanks in it. The rule under the row is
     * indented like every other document's; the amendment and the termination used to draw
     * theirs flush left, which read as a different document rather than the same one.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     * @throws \InvalidArgumentException When the type is not one this generator prints
     */
    private function printContractIdentification(ContractPrintData $data): void
    {
        $contract = $data->contract;

        [$columns, $width, $separatorOffset, $between] = match ($data->type) {
            ContractDocumentType::ContractNew,
            ContractDocumentType::ContractNewX => [
                [
                    [$this->label('contract_number'), (string)$contract->number],
                    [$this->label('start_date'), (string)$this->executedVersion($data)->valid_from],
                ],
                90.0,
                AppPDF::SEPARATOR_OFFSET_X,
                'between_new',
            ],
            ContractDocumentType::ContractAmendment => [
                [
                    [$this->label('contract_number'), (string)$contract->number],
                    [$this->label('conclusion_date'), (string)$this->executedVersion($data)->conclusion_date],
                    [
                        $this->label('amendment_number'),
                        (string)($this->executedVersion($data)->number_of_amendments + 1),
                    ],
                    [$this->label('amendment_effective'), (string)$data->effectiveDateOfAmendment],
                ],
                45.0,
                AppPDF::SEPARATOR_OFFSET_X,
                'between_amendment',
            ],
            ContractDocumentType::ContractTermination => [
                [
                    [$this->label('contract_number'), (string)$data->contractNumberToBeTerminated],
                    [$this->label('conclusion_date'), (string)$this->terminatedVersion($data)->conclusion_date],
                    [$this->label('end_date'), (string)$this->terminatedVersion($data)->valid_until],
                ],
                60.0,
                AppPDF::SEPARATOR_OFFSET_X,
                'between_termination',
            ],
            default => throw new InvalidArgumentException('Unsupported contract print type: ' . $data->type->value),
        };

        $this->printLabelledRow($columns, $width, $separatorOffset);
        $this->printBetween($this->label($between));
    }

    /**
     * The code a subscriber quotes when they transfer their number to another provider.
     *
     * @param \App\Model\Entity\Contract $contract Contract being printed
     * @return void
     */
    private function printSubscriberVerificationCode(Contract $contract): void
    {
        if (empty($contract->subscriber_verification_code)) {
            return;
        }

        $this->Cell(60, 4, $this->label('subscriber_verification_code') . ':', align: 'R');
        $this->SetFont(self::FONT_FAMILY, 'B', self::BODY_FONT_SIZE);
        $this->Cell(60, 4, $contract->subscriber_verification_code);
    }

    /**
     * What this document is doing to the contract, said before the figures.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    private function printContractIntro(ContractPrintData $data): void
    {
        $type = $data->type;

        if ($type === ContractDocumentType::ContractTermination) {
            $terminated = $this->terminatedVersion($data);

            $this->SetFont(self::FONT_FAMILY, 'B', self::BODY_FONT_SIZE);
            $this->Write(4, strtr($this->contractText('texts.termination_intro'), [
                '{contract_number}' => (string)$data->contractNumberToBeTerminated,
                '{conclusion_date}' => (string)$terminated->conclusion_date,
                '{valid_until}' => (string)$terminated->valid_until,
            ]));
            $this->Ln();
            $this->Ln();
            $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
            $this->Write(4, $this->contractText('texts.termination_final'));
            $this->Ln();
        }

        if ($type === ContractDocumentType::ContractNew || $type === ContractDocumentType::ContractNewX) {
            $executed = $this->executedVersion($data);

            $this->SetFont(self::FONT_FAMILY, 'B', self::BODY_FONT_SIZE);
            $this->Write(4, strtr($this->contractText('texts.new_intro'), [
                '{minimum_duration}' => $this->contractDuration($executed),
            ]));
            $this->Ln();

            if ($executed->valid_until !== null) {
                $this->Write(4, $this->contractText('texts.new_definite_note'));
                $this->Ln();
            }

            $this->Write(4, strtr($this->contractText('texts.new_start_date'), [
                '{valid_from}' => (string)$executed->valid_from,
            ]));
            $this->Ln();
            $this->Ln();

            if ($type === ContractDocumentType::ContractNewX) {
                $this->Write(4, strtr($this->contractText('texts.new_x_intro'), [
                    '{contract_number}' => (string)$data->contractNumberToBeTerminated,
                    '{old_conclusion_date}' => (string)$this->terminatedVersion($data)->conclusion_date,
                    '{termination_date}' => (string)$executed->valid_from->subDays(1),
                ]));
                $this->Ln();
                $this->Ln();
            }
        }

        if ($type === ContractDocumentType::ContractAmendment) {
            $this->SetFont(self::FONT_FAMILY, 'B', self::BODY_FONT_SIZE);
            $this->Write(4, strtr($this->contractText('texts.amendment_intro'), [
                '{valid_from}' => $data->effectiveDateOfAmendment,
            ]));
            $this->Ln();
            $this->Ln();
        }
    }

    /**
     * What the customer pays, what it is for, and how it is paid.
     *
     * A termination has none of this - there is nothing left to bill - so it prints nothing.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    private function printBillingAndPayment(ContractPrintData $data): void
    {
        if (
            !in_array(
                $data->type,
                [
                    ContractDocumentType::ContractNew,
                    ContractDocumentType::ContractNewX,
                    ContractDocumentType::ContractAmendment,
                ],
                true,
            )
        ) {
            return;
        }

        if ($data->type === ContractDocumentType::ContractAmendment) {
            // For amendments use the effective date of amendment as reference date for billing relevance
            $this->assertAmendmentData($data);
            $billingReferenceDate = $data->effectiveDateOfAmendment;
            // Italic sets the whole block apart from the contract version it is amending
            $format = 'I';
        } else {
            // For new contracts use the contract start date as reference date for billing relevance
            $billingReferenceDate = $this->executedVersion($data)->valid_from;
            $format = '';
        }

        // The total is what the first invoice will say.
        $totalCost = $this->printServices(
            $data->getActiveStandardBillings(),
            $data->getActiveIndividualBillings(),
            $data->getFutureStandardBillings(),
            $data->getFutureIndividualBillings(),
            $billingReferenceDate,
            $format,
        );

        $this->printPaymentInformation($data, $billingReferenceDate, $totalCost, $format);
    }

    /**
     * The note that hangs off a table of individually priced items.
     *
     * @param string $format Additional font format
     * @return void
     */
    protected function afterIndividualPrices(string $format): void
    {
        $this->SetFont(self::FONT_FAMILY, $format, self::NOTE_FONT_SIZE);
        $this->MultiCell(
            self::TEXT_WIDTH,
            self::LINE_HEIGHT,
            $this->contractText('texts.individual_clause'),
            align: 'L',
        );
        $this->Ln();
    }

    /**
     * When the first payment falls due, how much it is, and where it goes.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @param \Cake\I18n\Date $billingReferenceDate Reference date for billing relevance
     * @param \PhpCollective\DecimalObject\Decimal $totalCost Total of everything billed now
     * @param string $format Additional font format
     * @return void
     */
    private function printPaymentInformation(
        ContractPrintData $data,
        Date $billingReferenceDate,
        Decimal $totalCost,
        string $format,
    ): void {
        $contract = $data->contract;

        $this->printBillingHeading($this->paymentText('heading'), $format);

        // The columns divide the width the rules above and below them span, so the row uses
        // the whole of it rather than stopping seven millimetres short and condensing the one
        // label that needs those millimetres.
        $column = self::PAGE_WIDTH / 4;

        $this->SetFont(self::FONT_FAMILY, $format, self::BODY_FONT_SIZE);
        $this->frameLeft();
        $this->Cell($column, self::LINE_HEIGHT, $this->label('payment_period'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, $this->label('payment_method'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, $this->label('first_payment_date'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, $this->label('first_payment_total'), align: 'C');
        $this->Ln();

        $this->SetFont(self::FONT_FAMILY, 'B' . $format, self::BODY_FONT_SIZE);
        $this->frameLeft();
        $this->Cell($column, self::LINE_HEIGHT, $this->label('monthly'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, $this->label('bank_transfer'), align: 'C');
        $this->Cell(
            $column,
            self::LINE_HEIGHT,
            strtr($this->label('first_payment_due'), [
                '{date}' => (string)$billingReferenceDate->day(1)->addMonths(1)->addDays(9),
            ]),
            align: 'C',
        );

        // A customer the tax is reverse charged to is shown the base, and the clause that says
        // why the figure is not what they will pay.
        if ($contract->customer->accounting_profile->reverse_charge) {
            $this->Cell(
                $column,
                self::LINE_HEIGHT,
                Number::currency(
                    Billing::calcVatBaseFromTotal(
                        $totalCost,
                        $contract->customer->accounting_profile->vat_rate,
                    )->toFloat(),
                ) . ' *',
                align: 'C',
            );
            $this->Ln();

            $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X, lnAfter: 1.0);

            $this->SetFont(self::FONT_FAMILY, $format, self::NOTE_FONT_SIZE);
            $this->MultiCell(
                self::TEXT_WIDTH,
                self::LINE_HEIGHT,
                $this->paymentText('reverse_charge_clause') . PHP_EOL,
                align: 'J',
            );
        } else {
            $this->Cell($column, self::LINE_HEIGHT, Number::currency($totalCost->toFloat()), align: 'C');
            $this->Ln();
        }

        $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X, lnAfter: 1.0);

        $this->SetFont(self::FONT_FAMILY, $format, self::BODY_FONT_SIZE);
        $this->Cell($column * 2, self::LINE_HEIGHT, $this->label('provider_bank'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, $this->label('provider_account'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, $this->label('variable_symbol'), align: 'C');
        $this->Ln();

        $this->SetFont(self::FONT_FAMILY, 'B' . $format, self::BODY_FONT_SIZE);
        $this->Cell($column * 2, self::LINE_HEIGHT, Settings::getString('core.company.bank_name'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, Settings::getString('core.company.bank_account_number'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, $contract->customer->number . ' *', align: 'C');
        $this->Ln();

        $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X, lnAfter: 1.0);

        $this->SetFont(self::FONT_FAMILY, $format, self::NOTE_FONT_SIZE);
        $this->Cell(self::TEXT_WIDTH, self::LINE_HEIGHT, $this->paymentText('standing_order_note'));
        $this->Ln();
    }

    /**
     * What an amendment says once its figures have been stated: that the rest of the contract
     * stands as it was.
     *
     * @param \App\Model\Enum\ContractDocumentType $type Contract print type
     * @return void
     */
    private function printAmendmentClosing(ContractDocumentType $type): void
    {
        if ($type !== ContractDocumentType::ContractAmendment) {
            return;
        }

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->Ln();
        $this->Write(4, $this->contractText('texts.amendment_final_clause'));
        $this->Ln();
        $this->Ln();
        $this->Write(4, $this->contractText('texts.amendment_final_statement'));
        $this->Ln();
    }

    /**
     * The second page of a new contract: the equipment lent with the service, what installing
     * it costs, and the statements the customer signs under.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    private function printEquipmentAndFinalStatements(ContractPrintData $data): void
    {
        $type = $data->type;

        if ($type !== ContractDocumentType::ContractNew && $type !== ContractDocumentType::ContractNewX) {
            return;
        }

        $contract = $data->contract;
        $executed = $this->executedVersion($data);

        $this->drawCross();
        $this->AddPage();

        $this->SetFont(self::FONT_FAMILY, 'B', self::HEADING_FONT_SIZE);
        $this->Ln();
        $this->Ln();
        $this->printFrameHeading($this->contractText('texts.new_equipment_intro'), 4);
        $this->Ln();

        $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);

        if (count($data->equipmentLentAfterwards()) > 0) {
            $this->printBorrowedEquipmentForContract($data);

            // activation fee with installation
            $this->printActivationFee($data, $executed, true);
        } else {
            $this->printParagraph($this->contractText('texts.user_equipment_installation_costs'));

            // activation fee without installation
            $this->printActivationFee($data, $executed, false);
        }

        $this->printAgreedTerms($contract);
        $this->printContractFinalStatements($contract);
    }

    /**
     * The equipment the provider lends with the service, and what happens to it afterwards.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    private function printBorrowedEquipmentForContract(ContractPrintData $data): void
    {
        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);

        if ($data->type === ContractDocumentType::ContractNew) {
            $this->printTableCaption($this->contractText('texts.borrowed_equipment_intro_new'));
            $this->printContractEquipmentTable($data->equipmentLentAfterwards());
        } else {
            // A replacing contract says what the earlier one lent and goes on being lent, and then
            // what it lends itself.
            $continuing = $data->projectedEquipment->continuing ?? $data->contract->borrowed_equipments;
            $added = $data->projectedEquipment->lent ?? [];

            if ($continuing !== []) {
                $this->printTableCaption(
                    strtr($this->contractText('texts.borrowed_equipment_intro_old'), [
                        '{old_conclusion_date}' => $this->terminatedVersion($data)->conclusion_date,
                    ]),
                );
                $this->printContractEquipmentTable($continuing);
                $this->printParagraph($this->contractText('texts.borrowed_equipment_continue'));
            }

            if ($added !== []) {
                $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
                $this->printTableCaption($this->contractText('texts.borrowed_equipment_intro_new'));
                $this->printContractEquipmentTable($added);
            }
        }

        $this->printParagraph($this->contractText('texts.borrowed_equipment_return'));
        $this->printParagraph($this->contractText('texts.borrowed_equipment_installation_costs'));
    }

    /**
     * The table of equipment the contract lends, with the value of each piece.
     *
     * @param array<\App\Model\Entity\BorrowedEquipment> $equipments What is listed.
     * @return void
     */
    private function printContractEquipmentTable(array $equipments): void
    {
        $this->SetFont(self::FONT_FAMILY, 'B', self::BODY_FONT_SIZE);
        $this->Cell(
            130,
            self::TABLE_ROW_HEIGHT,
            $this->contractText('tables.borrowed_equipments.device'),
            1,
        );
        $this->Cell(
            30,
            self::TABLE_ROW_HEIGHT,
            $this->contractText('tables.borrowed_equipments.value'),
            border: 1,
            align: 'R',
        );
        $this->Ln();

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        foreach ($equipments as $borrowed_equipment) {
            $this->Cell(130, self::TABLE_ROW_HEIGHT, $borrowed_equipment->equipment_type->name, 1);
            $this->Cell(
                30,
                self::TABLE_ROW_HEIGHT,
                Number::currency($borrowed_equipment->equipment_type->price?->toFloat() ?? ''),
                border: 1,
                align: 'R',
            );
            $this->Ln();
        }
        $this->Ln();
    }

    /**
     * Prints the activation fee section of the contract PDF.
     *
     * This method centralizes the logic for rendering activation fee text blocks
     * depending on contract duration, contract type, and whether installation
     * of borrowed equipment is included.
     *
     * - Nothing where the papers do not set the connection up: a contract replacing an earlier
     *   one takes it over as it is unless the operator said it is set up again.
     * - If the contract has no minimum duration, prints the "no commitment" text.
     * - If the contract has a minimum duration, prints the "with commitment" text
     *   and the clause explaining the difference in activation fee.
     * - The parameter $withInstallation determines whether the installation
     *   wording is included (borrowed equipment) or excluded (user equipment).
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @param \App\Model\Entity\ContractVersion $contract_version Current contract version with duration info.
     * @param bool $withInstallation Whether installation of borrowed equipment should be mentioned
     *   in the activation fee text.
     * @return void
     */
    private function printActivationFee(
        ContractPrintData $data,
        ContractVersion $contract_version,
        bool $withInstallation,
    ): void {
        $contract = $data->contract;

        if (!$data->activatesTheService() || !$contract->activation_fee_sum->isPositive()) {
            return;
        }

        if ($contract_version->minimum_duration <= 0) {
            $key = $withInstallation
                ? 'activation_fee_no_commitment_with_installation'
                : 'activation_fee_no_commitment';

            $this->printParagraph(
                strtr($this->contractText('texts.' . $key), [
                    '{activation_fee}' => Number::currency($contract->activation_fee_sum->toFloat()),
                ]),
                bold: true,
            );

            return;
        }

        $key = $withInstallation
            ? 'activation_fee_with_commitment_with_installation'
            : 'activation_fee_with_commitment';

        $this->printParagraph(
            strtr($this->contractText('texts.' . $key), [
                '{activation_fee_obligation}' =>
                    Number::currency($contract->activation_fee_with_obligation_sum->toFloat()),
            ]),
            bold: true,
        );

        // what is owed for the fee is the same whatever it covers, which the line above has said
        $this->printParagraph(
            strtr($this->textByLegalStatus(
                'core.documents.contracts.contract.texts.activation_fee_clause',
                $contract->customer,
            ), [
                '{duration}' => $this->contractDurationBefore($contract_version->minimum_duration),
                '{difference}' =>
                    Number::currency(
                        $contract->activation_fee_sum
                            ->subtract($contract->activation_fee_with_obligation_sum)
                            ->toFloat(),
                    ),
                '{full_fee}' => Number::currency($contract->activation_fee_sum->toFloat()),
            ]),
            bold: true,
        );
    }

    /**
     * What was agreed beyond the standard terms, in the two places it can be agreed.
     *
     * The kind of service may carry terms of its own and this contract may have been given
     * something nobody else has, and the two are printed one under the other rather than merged:
     * read apart, one says what this service is and the other what this customer was given.
     *
     * It stands here, in the body of the contract and above the signatures, because a term nobody
     * could reasonably expect to find where it was put does not bind (§1753 obč. zák.) - and the
     * sentence that closes the article says out loud that what is agreed here comes before the
     * terms everybody gets.
     *
     * The text is printed as it was typed. Blank lines separate paragraphs, and nothing else is
     * read out of it: it is the office writing, not a template, so markup would only end up on
     * the paper.
     *
     * @param \App\Model\Entity\Contract $contract The contract being printed
     * @return void
     */
    private function printAgreedTerms(Contract $contract): void
    {
        $service_type = $contract->service_type ?? null;

        $blocks = [
            // General first, then what was agreed for this one contract.
            'service_terms' => $service_type !== null && $service_type->has('service_terms')
                ? trim((string)$service_type->service_terms)
                : '',
            'individual_terms' => $contract->has('individual_terms')
                ? trim((string)$contract->individual_terms)
                : '',
        ];

        if (implode('', $blocks) === '') {
            return;
        }

        foreach ($blocks as $section => $text) {
            if ($text === '') {
                continue;
            }

            $this->printSectionHeading($this->contractText('sections.' . $section));

            foreach (preg_split('/\R\s*\R/', $text) ?: [] as $paragraph) {
                $this->printParagraph(trim($paragraph));
            }
        }

        $this->printParagraph($this->contractText('texts.terms_precedence'));
    }

    /**
     * What the customer confirms by signing the contract.
     *
     * @param \App\Model\Entity\Contract $contract Contract being printed
     * @return void
     */
    private function printContractFinalStatements(Contract $contract): void
    {
        $this->printSectionHeading($this->contractText('sections.final_statements'));

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->setListIndentWidth(4);
        $this->writeHTML(
            $this->contractText('texts.final_statements_html'),
            true,
            false,
            false,
            true,
            '',
        );
        $this->Ln(3);

        $this->printParagraph($this->contractText('texts.final_prices'));
        $this->printParagraph(strtr($this->contractText('texts.final_copies'), [
            '{contract_number}' => (string)$contract->number,
        ]));
    }

    /**
     * Reads one of the contract's own texts.
     *
     * @param string $key Key under the contract documents block
     * @return string
     */
    private function contractText(string $key): string
    {
        return Settings::getString('core.documents.contracts.contract.' . $key);
    }
}
