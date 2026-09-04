<?php
declare(strict_types=1);

namespace App\Pdf;

use App\Model\Entity\Billing;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersion;
use App\Model\Enum\ContractPrintType;
use App\Model\Enum\IpAddressTypeOfUse;
use App\Pdf\Trait\ContractDurationTrait;
use App\Service\ContractPrint\ContractPrintData;
use Cake\I18n\Date;
use Cake\I18n\Number;
use InvalidArgumentException;
use PhpCollective\DecimalObject\Decimal;
use Settings\Utility\Settings;

/**
 * The papers a customer signs: the contract itself, an amendment to it, its termination, and
 * the handover protocol for the equipment that goes with it.
 *
 * Two documents live here rather than one because they are printed from the same data and
 * share most of their front matter - the same title block, the same two parties, the same
 * table of who the customer is. What differs is what follows, and each generator says so in
 * its own sequence of sections.
 */
class ContractPDF extends AppPDF
{
    use ContractDurationTrait;

    // constant for empty (null) serial numbers in the borrowed/sold equipment tables
    private const EMPTY_SERIAL = '';

    /**
     * These are papers that get read in order and signed at the end, so a clause split across
     * the fold is read twice and a heading stranded at the foot reads as a mistake. Blocks
     * take the break with them instead.
     */
    protected const KEEPS_BLOCKS_WHOLE = true;

    /**
     * How many rows the equipment tables are padded out to, so there is somewhere to write a
     * serial number by hand on a protocol that is filled in on site.
     */
    private const SOLD_EQUIPMENT_ROWS = 6;
    private const BORROWED_EQUIPMENT_ROWS = 5;

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

        $this->printSignatureSection('double', $data->signed);

        $this->Close();
    }

    /**
     * Generate PDF document - handover protocol
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    public function generateHandoverProtocol(ContractPrintData $data): void
    {
        $this->assertHandoverData($data);

        $contract = $data->contract;

        $this->printDocumentHeader(
            $this->handoverText('title'),
            $this->handoverText(
                $data->type === ContractPrintType::HandoverInstallation
                    ? 'subtitle_installation'
                    : 'subtitle_uninstallation',
            ),
        );

        $this->printHandoverIdentification($data);
        $this->printBetween($this->label('between'));

        $this->printParties($this->label('provider'), $contract);
        $this->Ln();
        $this->drawSeparator(lnAfter: 0.5);

        $this->printCustomerBlock($contract);
        $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X, lnAfter: 4.0);

        if ($data->type === ContractPrintType::HandoverInstallation) {
            $this->printInstallationProtocol($data);
        } else {
            $this->printUninstallationProtocol($data);
        }

        $this->printSignatureSection('double', $data->signed);

        $this->Close();
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
                    ContractPrintType::ContractNew,
                    ContractPrintType::ContractNewX,
                    ContractPrintType::ContractAmendment,
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
                $type === ContractPrintType::ContractNewX
                || $type === ContractPrintType::ContractTermination
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
                $type === ContractPrintType::ContractNewX
                || $type === ContractPrintType::ContractTermination
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
     * Assert that everything a handover protocol needs is present in the print data object.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     * @throws \InvalidArgumentException When the data does not carry what the type requires
     * @phpstan-assert !null $data->technicalDetails
     */
    private function assertHandoverData(ContractPrintData $data): void
    {
        if ($data->contract->number === null) {
            throw new InvalidArgumentException(
                'Contract number must be provided in order to generate a handover protocol',
            );
        }

        if (
            $data->contractVersionToBeExecuted === null
            && $data->type === ContractPrintType::HandoverInstallation
        ) {
            throw new InvalidArgumentException(
                'The contract version to be executed must be provided in order to generate'
                    . ' a handover protocol for installation',
            );
        }

        if (
            $data->contractVersionToBeTerminated === null
            && $data->type === ContractPrintType::HandoverUninstallation
        ) {
            throw new InvalidArgumentException(
                'The contract version to be terminated must be provided in order to generate'
                    . ' a handover protocol for uninstallation',
            );
        }

        if ($data->technicalDetails === null) {
            throw new InvalidArgumentException(
                'Technical details must be provided in order to generate a handover protocol',
            );
        }
    }

    /**
     * The contract version this document puts into effect.
     *
     * The guards above have already refused a document that needs one and has not got it. This
     * is where that promise becomes a type, so the sections below can rely on it.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return \App\Model\Entity\ContractVersion
     * @throws \InvalidArgumentException When there is no version to execute
     */
    private function executedVersion(ContractPrintData $data): ContractVersion
    {
        if ($data->contractVersionToBeExecuted === null) {
            throw new InvalidArgumentException('The contract version to be executed must be provided');
        }

        return $data->contractVersionToBeExecuted;
    }

    /**
     * The contract version this document brings to an end.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return \App\Model\Entity\ContractVersion
     * @throws \InvalidArgumentException When there is no version to terminate
     */
    private function terminatedVersion(ContractPrintData $data): ContractVersion
    {
        if ($data->contractVersionToBeTerminated === null) {
            throw new InvalidArgumentException('The contract version to be terminated must be provided');
        }

        return $data->contractVersionToBeTerminated;
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
     * @param \App\Model\Enum\ContractPrintType $type Contract print type
     * @return array Title and subtitle
     * @throws \InvalidArgumentException When the type is not one this generator prints
     * @phpstan-return array{0: string, 1: string}
     */
    private function contractTitle(ContractPrintType $type): array
    {
        return match ($type) {
            ContractPrintType::ContractNew,
            ContractPrintType::ContractNewX => [
                $this->contractText('title_new'),
                $this->contractText('subtitle_new'),
            ],
            ContractPrintType::ContractAmendment => [
                $this->contractText('title_amendment'),
                $this->contractText('subtitle_amendment'),
            ],
            ContractPrintType::ContractTermination => [
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
            ContractPrintType::ContractNew,
            ContractPrintType::ContractNewX => [
                [
                    [$this->label('contract_number'), (string)$contract->number],
                    [$this->label('start_date'), (string)$this->executedVersion($data)->valid_from],
                ],
                90.0,
                AppPDF::SEPARATOR_OFFSET_X,
                'between_new',
            ],
            ContractPrintType::ContractAmendment => [
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
            ContractPrintType::ContractTermination => [
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
     * Says which contract the handover protocol belongs to.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    private function printHandoverIdentification(ContractPrintData $data): void
    {
        $columns = $data->type === ContractPrintType::HandoverInstallation
            ? [
                [$this->label('contract_number'), (string)$data->contract->number],
                [$this->label('start_date'), (string)$this->executedVersion($data)->valid_from],
            ]
            : [
                [$this->label('contract_number'), (string)$data->contractNumberToBeTerminated],
                [$this->label('end_date'), (string)$this->terminatedVersion($data)->valid_until],
            ];

        $this->printLabelledRow($columns, 90.0);
    }

    /**
     * The line that introduces the two parties.
     *
     * @param string $text How this document names what it is between
     * @return void
     */
    private function printBetween(string $text): void
    {
        $this->SetFont(self::FONT_FAMILY, 'B', self::HEADING_FONT_SIZE);
        $this->Cell(self::PAGE_WIDTH, 2, $text, align: 'C');
        $this->Ln();
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
     * Who the customer is: their identity, their billing address, and wherever else the
     * service or the post has to reach them.
     *
     * The contract and its handover protocol state this identically, and they have to - the
     * protocol is signed on the doorstep against what the contract says.
     *
     * @param \App\Model\Entity\Contract $contract Contract being printed
     * @return void
     */
    private function printCustomerBlock(Contract $contract): void
    {
        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->printTable(
            [
                __d('documents', 'Billing Address') . ':',
                '',
            ],
            [
                [
                    [
                        'label' => $this->label('company'),
                        'value' => $this->strOrX($contract->billing_address->company, ''),
                    ],
                    [
                        'label' => $this->label('birth_date'),
                        'value' => (string)$contract->customer->date_of_birth,
                        'label_width' => 25,
                        'value_width' => 25,
                    ],
                    [
                        'label' => $this->label('identity_number'),
                        'value' => $this->strOrX($contract->customer->identity_number),
                        'label_width' => 10,
                        'value_width' => 30,
                    ],
                ],
                [
                    [
                        'label' => is_null($contract->billing_address->company)
                            ? $this->label('name')
                            : $this->label('represented'),
                        'value' => $contract->billing_address->full_name,
                    ],
                    [
                        'label' => $this->label('identity_card_number'),
                        'value' => (string)$contract->customer->identity_card_number,
                        'label_width' => 25,
                        'value_width' => 25,
                    ],
                    [
                        'label' => $this->label('vat_number'),
                        'value' => $this->strOrX($contract->customer->vat_number),
                        'label_width' => 10,
                        'value_width' => 30,
                    ],
                ],
                [
                    [
                        'label' => $this->label('street'),
                        'value' => $contract->billing_address->street_and_number_extra,
                    ],
                    [
                        'label' => $this->label('phone'),
                        'value' => $contract->customer->phone,
                        'label_width' => 25,
                        'value_width' => 65,
                    ],
                ],
                [
                    [
                        'label' => $this->label('zip_city'),
                        'value' => $contract->billing_address->zip_and_city,
                    ],
                    [
                        'label' => $this->label('email'),
                        'value' => $contract->customer->email,
                        'label_width' => 25,
                        'value_width' => 65,
                    ],
                ],
            ],
        );

        $this->printAddressBlock(
            __d('documents', 'Installation Address'),
            $contract->installation_address->full_address ?? null,
        );
        $this->printAddressBlock(
            __d('documents', 'Delivery Address'),
            $contract->delivery_address->full_address ?? null,
        );
        $this->printAddressBlock(
            __d('documents', 'Permanent Address'),
            $contract->permanent_address->full_address ?? null,
        );
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

        if ($type === ContractPrintType::ContractTermination) {
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

        if ($type === ContractPrintType::ContractNew || $type === ContractPrintType::ContractNewX) {
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

            if ($type === ContractPrintType::ContractNewX) {
                $this->Write(4, strtr($this->contractText('texts.new_x_intro'), [
                    '{contract_number}' => (string)$data->contractNumberToBeTerminated,
                    '{old_conclusion_date}' => (string)$this->terminatedVersion($data)->conclusion_date,
                    '{termination_date}' => (string)$executed->valid_from->subDays(1),
                ]));
                $this->Ln();
                $this->Ln();
            }
        }

        if ($type === ContractPrintType::ContractAmendment) {
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
                    ContractPrintType::ContractNew,
                    ContractPrintType::ContractNewX,
                    ContractPrintType::ContractAmendment,
                ],
                true,
            )
        ) {
            return;
        }

        if ($data->type === ContractPrintType::ContractAmendment) {
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

        // sum of all items
        $totalCost = Decimal::create(0, 2);

        if (count($data->getActiveStandardBillings()) > 0) {
            $this->printBillingHeading($this->contractText('sections.billing_pricelist'), $format);
            $totalCost = $totalCost->add(
                $this->billingTable($data->getActiveStandardBillings(), $billingReferenceDate, $format),
            );
            $this->Ln();
        }

        if (count($data->getActiveIndividualBillings()) > 0) {
            $this->printBillingHeading($this->contractText('sections.billing_individual'), $format);
            $totalCost = $totalCost->add(
                $this->billingTable($data->getActiveIndividualBillings(), $billingReferenceDate, $format),
            );
            $this->printIndividualClause($format);
        }

        // What is agreed now but starts later is shown, and left out of the total, because the
        // total is what the first invoice will say.
        if (count($data->getFutureStandardBillings()) > 0) {
            $this->printBillingHeading($this->contractText('sections.billing_future_pricelist'), $format);
            $this->billingTable($data->getFutureStandardBillings(), $billingReferenceDate, $format);
            $this->Ln();
        }

        if (count($data->getFutureIndividualBillings()) > 0) {
            $this->printBillingHeading($this->contractText('sections.billing_future_individual'), $format);
            $this->billingTable($data->getFutureIndividualBillings(), $billingReferenceDate, $format);
            $this->printIndividualClause($format);
        }

        $this->printPaymentInformation($data, $billingReferenceDate, $totalCost, $format);
    }

    /**
     * Heads one of the billing tables.
     *
     * These sit closer to their table than an ordinary section heading does, and they carry
     * the block's own font style, so they are set here rather than through the shared one.
     *
     * @param string $text The heading
     * @param string $format Additional font format
     * @return void
     */
    private function printBillingHeading(string $text, string $format): void
    {
        $this->keepTogether(self::HEADING_ORPHAN_GUARD);

        $this->SetFont(self::FONT_FAMILY, 'B' . $format, self::HEADING_FONT_SIZE);
        $this->Cell(self::PAGE_WIDTH, 3, $text);
        $this->Ln();

        $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);
    }

    /**
     * The note that hangs off a table of individually priced items.
     *
     * @param string $format Additional font format
     * @return void
     */
    private function printIndividualClause(string $format): void
    {
        $this->SetFont(self::FONT_FAMILY, $format, self::NOTE_FONT_SIZE);
        $this->Cell(self::TABLE_INDENT, self::LINE_HEIGHT);
        $this->MultiCell(
            self::TEXT_WIDTH,
            self::LINE_HEIGHT,
            $this->contractText('texts.individual_clause'),
            align: 'L',
        );
        $this->Ln();
    }

    /**
     * Prints billing table.
     *
     * @param iterable<\App\Model\Entity\Billing> $billings Billings
     * @param \Cake\I18n\Date $billingReferenceDate Reference date for billing relevance
     * @param string $format Additional font format
     * @return \PhpCollective\DecimalObject\Decimal Total cost
     */
    private function billingTable(iterable $billings, Date $billingReferenceDate, string $format): Decimal
    {
        $this->SetFont(self::FONT_FAMILY, '' . $format, self::BODY_FONT_SIZE);
        $this->Cell(self::TABLE_INDENT, self::LINE_HEIGHT);
        $this->Cell(140, self::LINE_HEIGHT, $this->billingText('service'));
        $this->Cell(35, self::LINE_HEIGHT, $this->billingText('price_per_month'), align: 'R');
        $this->Ln();

        $totalCost = Decimal::create(0, 2);

        foreach ($billings as $billing) {
            $this->SetFont(self::FONT_FAMILY, 'B' . $format, self::BODY_FONT_SIZE);
            $this->Cell(self::TABLE_INDENT, self::LINE_HEIGHT);
            $this->Cell(
                140,
                self::LINE_HEIGHT,
                $billing->name
                . ($billing->billing_from > $billingReferenceDate
                    ? ' ' . strtr($this->billingText('from'), [
                        '{date}' => (string)$billing->billing_from,
                    ])
                    : '')
                . ($billing->billing_until
                    ? ' ' . strtr($this->billingText('until'), [
                        '{date}' => (string)$billing->billing_until,
                    ])
                    : ''),
                align: 'L',
                stretch: 1,
            );
            $this->Cell(35, self::LINE_HEIGHT, Number::currency($billing->sum->toFloat()), align: 'R');
            $this->Ln();

            if ($billing->percentage_discount_sum->isPositive()) {
                $this->SetFont(self::FONT_FAMILY, '' . $format, self::BODY_FONT_SIZE);
                $this->Cell(self::TABLE_INDENT, self::LINE_HEIGHT);
                $this->Cell(
                    140,
                    self::LINE_HEIGHT,
                    strtr($this->billingText('percentage_discount'), [
                        '{percentage}' => (string)$billing->percentage_discount,
                    ]),
                );
                $this->Cell(
                    35,
                    self::LINE_HEIGHT,
                    Number::currency($billing->percentage_discount_sum->negate()->toFloat()),
                    align: 'R',
                );
                $this->Ln();
            }
            if ($billing->fixed_discount_sum->isPositive()) {
                $this->SetFont(self::FONT_FAMILY, '' . $format, self::BODY_FONT_SIZE);
                $this->Cell(self::TABLE_INDENT, self::LINE_HEIGHT);
                $this->Cell(140, self::LINE_HEIGHT, $this->billingText('fixed_discount'));
                $this->Cell(
                    35,
                    self::LINE_HEIGHT,
                    Number::currency($billing->fixed_discount_sum->negate()->toFloat()),
                    align: 'R',
                );
                $this->Ln();
            }

            /** @psalm-suppress ImplicitToStringCast */
            $totalCost = $totalCost->add($billing->total_price);
        }

        return $totalCost;
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

        $this->printBillingHeading($this->contractText('sections.payment_info'), $format);

        // The columns divide the width the rules above and below them span, so the row uses
        // the whole of it rather than stopping seven millimetres short and condensing the one
        // label that needs those millimetres.
        $column = self::PAGE_WIDTH / 4;

        $this->SetFont(self::FONT_FAMILY, $format, self::BODY_FONT_SIZE);
        $this->Cell($column, self::LINE_HEIGHT, $this->label('payment_period'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, $this->label('payment_method'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, $this->label('first_payment_date'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, $this->label('first_payment_total'), align: 'C');
        $this->Ln();

        $this->SetFont(self::FONT_FAMILY, 'B' . $format, self::BODY_FONT_SIZE);
        $this->Cell($column, self::LINE_HEIGHT, $this->label('monthly'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, $this->label('bank_transfer'), align: 'C');
        $this->Cell(
            $column,
            self::LINE_HEIGHT,
            'do ' . $billingReferenceDate->day(1)->addMonths(1)->addDays(9),
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
            $this->Cell(self::TABLE_INDENT, self::LINE_HEIGHT);
            $this->MultiCell(
                self::TEXT_WIDTH,
                self::LINE_HEIGHT,
                $this->contractText('texts.reverse_charge_clause') . PHP_EOL,
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
        $this->Cell(self::TABLE_INDENT, self::LINE_HEIGHT);
        $this->Cell(self::TEXT_WIDTH, self::LINE_HEIGHT, $this->contractText('texts.standing_order_note'));
        $this->Ln();
    }

    /**
     * What an amendment says once its figures have been stated: that the rest of the contract
     * stands as it was.
     *
     * @param \App\Model\Enum\ContractPrintType $type Contract print type
     * @return void
     */
    private function printAmendmentClosing(ContractPrintType $type): void
    {
        if ($type !== ContractPrintType::ContractAmendment) {
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

        if ($type !== ContractPrintType::ContractNew && $type !== ContractPrintType::ContractNewX) {
            return;
        }

        $contract = $data->contract;
        $executed = $this->executedVersion($data);

        $this->drawCross();
        $this->AddPage();

        $this->SetFont(self::FONT_FAMILY, 'B', self::HEADING_FONT_SIZE);
        $this->Ln();
        $this->Ln();
        $this->Write(4, $this->contractText('texts.new_equipment_intro'));
        $this->Ln();

        $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);

        if (count($contract->borrowed_equipments) > 0) {
            $this->printBorrowedEquipmentForContract($data);

            // activation fee with installation
            $this->printActivationFee($contract, $executed, $type, true);
        } else {
            $this->printParagraph($this->contractText('texts.user_equipment_installation_costs'));

            // activation fee without installation
            $this->printActivationFee($contract, $executed, $type, false);
        }

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
        $contract = $data->contract;

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        if ($data->type === ContractPrintType::ContractNew) {
            $this->Write(4, $this->contractText('texts.borrowed_equipment_intro_new'));
        } else {
            $this->Write(
                4,
                strtr($this->contractText('texts.borrowed_equipment_intro_old'), [
                    '{old_conclusion_date}' => $this->terminatedVersion($data)->conclusion_date,
                ]),
            );
        }
        $this->Ln(5);

        $this->SetFont(self::FONT_FAMILY, 'B', self::BODY_FONT_SIZE);
        $this->Cell(self::TABLE_INDENT, self::TABLE_ROW_HEIGHT);
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
        foreach ($contract->borrowed_equipments as $borrowed_equipment) {
            $this->Cell(self::TABLE_INDENT, self::TABLE_ROW_HEIGHT);
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

        if ($data->type === ContractPrintType::ContractNewX) {
            $this->printParagraph($this->contractText('texts.borrowed_equipment_continue'));
        }

        $this->printParagraph($this->contractText('texts.borrowed_equipment_return'));
        $this->printParagraph($this->contractText('texts.borrowed_equipment_installation_costs'));
    }

    /**
     * Prints the activation fee section of the contract PDF.
     *
     * This method centralizes the logic for rendering activation fee text blocks
     * depending on contract duration, contract type, and whether installation
     * of borrowed equipment is included.
     *
     * - If the contract has no minimum duration, prints the "no commitment" text.
     * - If the contract has a minimum duration, prints the "with commitment" text
     *   and the clause explaining the difference in activation fee.
     * - The parameter $withInstallation determines whether the installation
     *   wording is included (borrowed equipment) or excluded (user equipment).
     *
     * @param \App\Model\Entity\Contract        $contract         Current contract instance containing fee sums.
     * @param \App\Model\Entity\ContractVersion $contract_version Current contract version with duration info.
     * @param \App\Model\Enum\ContractPrintType $type             Contract print type.
     * @param bool                              $withInstallation Whether installation of borrowed equipment
     *                                                            should be mentioned in the activation fee text.
     * @return void
     */
    private function printActivationFee(
        Contract $contract,
        ContractVersion $contract_version,
        ContractPrintType $type,
        bool $withInstallation,
    ): void {
        if (!$contract->activation_fee_sum->isPositive()) {
            return;
        }

        if ($contract_version->minimum_duration <= 0) {
            if ($type === ContractPrintType::ContractNew) {
                $key = $withInstallation
                    ? 'activation_fee_no_commitment_with_installation'
                    : 'activation_fee_no_commitment';

                $this->printParagraph(
                    strtr($this->contractText('texts.' . $key), [
                        '{activation_fee}' => Number::currency($contract->activation_fee_sum->toFloat()),
                    ]),
                    bold: true,
                );
            }

            return;
        }

        if ($type === ContractPrintType::ContractNew) {
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
        }

        $clauseKey = $withInstallation
            ? 'activation_fee_clause_with_installation'
            : 'activation_fee_clause';

        $this->printParagraph(
            strtr($this->contractText('texts.' . $clauseKey), [
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
     * The installation protocol: how the customer gets on the network, what was lent to them,
     * what the installation cost, and what they are confirming by signing.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    private function printInstallationProtocol(ContractPrintData $data): void
    {
        $contract = $data->contract;
        $executed = $this->executedVersion($data);

        $this->printAccessInformation($data);
        $this->printBorrowedEquipmentForInstallation($contract);

        $this->drawCross();
        $this->AddPage();

        [$soldEquipmentsDiscount, $soldEquipmentsValue] = $this->printActivationFeeAndSoldEquipment(
            $contract,
            $executed,
        );

        $this->printHandoverSection(
            'connection_point_state',
            'connection_point_state_text',
            lnBeforeText: 3,
        );
        $this->printHandoverSection('general_statements', 'general_statements_text', lnBeforeText: 4);

        $this->printEarlyTerminationTerms($executed, $soldEquipmentsDiscount, $soldEquipmentsValue);

        $this->printSectionHeading($this->handoverText('sections.final_statements'));
        $this->printParagraph(strtr($this->handoverText('texts.final_statements_text'), [
            '{contract_number}' => (string)$contract->number,
        ]));
    }

    /**
     * The uninstallation protocol: what is being taken back, in what state, and what is
     * settled on the doorstep.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    private function printUninstallationProtocol(ContractPrintData $data): void
    {
        $this->printBorrowedEquipmentForUninstallation($data->contract);

        $this->drawCross();
        $this->AddPage();

        $this->printHandoverSection(
            'uninstallation_cash_payment',
            'uninstallation_cash_payment_text',
            lnBeforeText: 4,
        );
        $this->printHandoverSection(
            'general_statements',
            'uninstallation_general_statements_text',
            lnBeforeText: 4,
        );

        $this->printSectionHeading($this->handoverText('sections.final_statements'));
        $this->printParagraph(strtr($this->handoverText('texts.uninstallation_final_statements_text'), [
            '{contract_number}' => (string)$data->contractNumberToBeTerminated,
        ]));
    }

    /**
     * One of the handover protocol's plain sections: a heading over a single block of text.
     *
     * @param string $sectionKey Key under the handover sections block
     * @param string $textKey Key under the handover texts block
     * @param float $lnBeforeText Air between the heading's rule and the text
     * @return void
     */
    private function printHandoverSection(string $sectionKey, string $textKey, float $lnBeforeText = 0.0): void
    {
        $this->printSectionHeading($this->handoverText('sections.' . $sectionKey));

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        if ($lnBeforeText > 0.0) {
            $this->Ln($lnBeforeText);
        }

        $this->printParagraph($this->handoverText('texts.' . $textKey), gap: 6.0);
    }

    /**
     * How the customer's equipment authenticates, which addresses it was given, and what the
     * network looks like from their side.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    private function printAccessInformation(ContractPrintData $data): void
    {
        $contract = $data->contract;
        $technical_details = $data->technicalDetails;

        $this->printSectionHeading($this->handoverText('sections.access_info'));

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->Write(4, $this->handoverText('texts.endpoint_auth'));
        $this->Ln(5);

        $this->printFramedRow(
            [__d('documents', 'Username'), __d('documents', 'Password')],
            [90, 90],
            ['C', 'C'],
            bold: true,
        );
        $this->printFramedRow(
            [$technical_details->radiusUsername ?? '-', $technical_details->radiusPassword ?? '-'],
            [90, 90],
            ['C', 'C'],
        );
        $this->Ln(1);

        $this->printAssignedIpAddresses($contract);
        $this->printAssignedIpNetworks($contract);
        $this->printDefaultNetworkSettings();

        $this->writeHTML(
            $this->handoverText('texts.portal_access_html'),
            true,
            false,
            false,
            true,
            '',
        );

        $this->Ln(4);
    }

    /**
     * The addresses assigned to this connection, with the range each one belongs to.
     *
     * @param \App\Model\Entity\Contract $contract Contract being printed
     * @return void
     */
    private function printAssignedIpAddresses(Contract $contract): void
    {
        if (empty($contract->ip_addresses)) {
            return;
        }

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->Write(4, __d('documents', 'Assigned IP Addresses') . ':');
        $this->Ln(5);

        $this->printFramedRow(
            [
                __d('documents', 'IP Address'),
                __d('documents', 'IP Network'),
                __d('documents', 'IP Gateway'),
            ],
            [60, 60, 60],
            ['C', 'C', 'C'],
            bold: true,
        );

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        foreach ($contract->ip_addresses as $ipAddress) {
            // an address the range is not looked up for prints a dash rather than the
            // range of whichever address came before it
            $range = null;
            // load range for customer address set manually
            if ($ipAddress->type_of_use == IpAddressTypeOfUse::CustomerManually) {
                $range = $ipAddress->ip_address_ranges->data?->first();
            }
            // skip processing for technology address set manually
            if ($ipAddress->type_of_use == IpAddressTypeOfUse::TechnologyManually) {
                continue 1;
            }
            $this->printFramedRow(
                [$ipAddress->ip_address, $range->network ?? '-', $range->gateway ?? '-'],
                [60, 60, 60],
                ['C', 'C', 'C'],
            );

            unset($range);
        }

        $this->Ln(1);
    }

    /**
     * Whole networks routed to this connection, where there are any.
     *
     * @param \App\Model\Entity\Contract $contract Contract being printed
     * @return void
     */
    private function printAssignedIpNetworks(Contract $contract): void
    {
        if (empty($contract->ip_networks)) {
            return;
        }

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->Write(4, __d('documents', 'Assigned IP Networks') . ':');
        $this->Ln(5);

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->Cell(self::TABLE_INDENT, self::TABLE_ROW_HEIGHT);
        $this->MultiCell(
            self::TEXT_WIDTH,
            self::LINE_HEIGHT,
            implode(', ', array_column($contract->ip_networks, 'ip_network')),
            border: 1,
            align: 'L',
        );

        $this->Ln(1);
    }

    /**
     * What the customer's own router should be set to, and where their wifi details go.
     *
     * @return void
     */
    private function printDefaultNetworkSettings(): void
    {
        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->Write(4, $this->handoverText('texts.default_network_intro'));
        $this->Ln(5);

        $this->printFramedRow(
            [
                __d('documents', 'IP Network'),
                __d('documents', 'IP Gateway'),
                __d('documents', 'DNS Servers'),
            ],
            [60, 60, 60],
            ['C', 'C', 'C'],
            bold: true,
        );
        $this->printFramedRow(
            [
                $this->handoverText('defaults.ip_network'),
                $this->handoverText('defaults.ip_gateway'),
                $this->handoverText('defaults.dns_servers'),
            ],
            [60, 60, 60],
            ['C', 'C', 'C'],
        );
        $this->Ln(1);

        // Left blank on purpose: the wifi name and key are written in by hand on site.
        $this->printFramedRow(
            [__d('documents', 'WiFi - SSID'), __d('documents', 'WiFi - Password')],
            [90, 90],
            ['C', 'C'],
            bold: true,
        );
        $this->printFramedRow(['', ''], [90, 90], ['C', 'C']);
        $this->Ln(1);
    }

    /**
     * The equipment lent to the customer, listed on the installation protocol they sign for.
     *
     * @param \App\Model\Entity\Contract $contract Contract being printed
     * @return void
     */
    private function printBorrowedEquipmentForInstallation(Contract $contract): void
    {
        if (count($contract->borrowed_equipments) === 0) {
            return;
        }

        $this->printSectionHeading($this->handoverText('sections.borrowed_equipment'));

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->Write(4, $this->handoverText('texts.borrowed_equipment_intro'));
        $this->Ln(5);

        $this->printBorrowedEquipmentTable($contract, padTo: 0);

        $this->Ln(2);
        $this->printParagraph($this->handoverText('texts.borrowed_equipment_return'), gap: 2.0);
    }

    /**
     * The equipment being taken back, and the state it is being taken back in.
     *
     * @param \App\Model\Entity\Contract $contract Contract being printed
     * @return void
     */
    private function printBorrowedEquipmentForUninstallation(Contract $contract): void
    {
        $this->printSectionHeading($this->handoverText('sections.uninstallation_borrowed_equipment'));

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->Write(4, $this->handoverText('texts.uninstallation_borrowed_equipment_intro'));
        $this->Ln(5);

        $this->printBorrowedEquipmentTable($contract, padTo: self::BORROWED_EQUIPMENT_ROWS);

        $this->Ln(2);
        $this->SetFont(self::FONT_FAMILY, 'U', self::BODY_FONT_SIZE);
        $this->Write(4, $this->handoverText('texts.uninstallation_equipment_state'));
        $this->Ln(5);

        $this->printParagraph($this->handoverText('texts.uninstallation_equipment_checks_text'), gap: 0.0);
    }

    /**
     * The table of lent equipment, optionally padded out with blank rows to write into.
     *
     * @param \App\Model\Entity\Contract $contract Contract being printed
     * @param int $padTo Number of rows the table is filled out to, or zero to list only what there is
     * @return void
     */
    private function printBorrowedEquipmentTable(Contract $contract, int $padTo): void
    {
        $this->printFramedRow(
            [
                $this->handoverText('tables.borrowed_equipments.device'),
                $this->handoverText('tables.borrowed_equipments.serial'),
                $this->handoverText('tables.borrowed_equipments.value'),
            ],
            [130, 25, 25],
            ['L', 'C', 'R'],
            bold: true,
        );

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        foreach ($contract->borrowed_equipments as $borrowed_equipment) {
            $this->printFramedRow(
                [
                    $borrowed_equipment->equipment_type->name,
                    $borrowed_equipment->serial_number ?? self::EMPTY_SERIAL,
                    Number::currency($borrowed_equipment->equipment_type->price?->toFloat() ?? ''),
                ],
                [130, 25, 25],
                ['L', 'C', 'R'],
            );
        }

        $this->printBlankRows($padTo - min($padTo, count($contract->borrowed_equipments)), [130, 25, 25]);
    }

    /**
     * What installing the service cost, and the equipment sold as part of it.
     *
     * The price a piece of equipment goes on the protocol at depends on whether the customer
     * committed: the discount is the provider's, and leaving early gives it back.
     *
     * @param \App\Model\Entity\Contract $contract Contract being printed
     * @param \App\Model\Entity\ContractVersion $contract_version Version being executed
     * @return array Discount given for the commitment, and the undiscounted value
     * @phpstan-return array{0: \PhpCollective\DecimalObject\Decimal, 1: \PhpCollective\DecimalObject\Decimal}
     */
    private function printActivationFeeAndSoldEquipment(Contract $contract, ContractVersion $contract_version): array
    {
        $this->printSectionHeading($this->handoverText('sections.activation_fee'));

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->printParagraph(
            $this->handoverText(
                count($contract->borrowed_equipments) > 0
                    ? 'texts.activation_fee_intro_with_equipment'
                    : 'texts.activation_fee_intro',
            ),
            gap: 1.0,
        );

        $subtotal = $contract_version->minimum_duration <= 0
            ? $contract->activation_fee_sum
            : $contract->activation_fee_with_obligation_sum;

        $this->printFramedRow(
            [$this->handoverText('tables.sold_equipments.activation_fee'), Number::currency($subtotal->toFloat())],
            [155, 25],
            ['L', 'R'],
        );

        $this->Ln(2);

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->Write(4, $this->handoverText('texts.activation_fee_items_intro'));
        $this->Ln(5);

        $this->printFramedRow(
            [
                $this->handoverText('tables.sold_equipments.item'),
                $this->handoverText('tables.sold_equipments.serial'),
                $this->handoverText('tables.sold_equipments.price'),
            ],
            [130, 25, 25],
            ['L', 'C', 'R'],
            bold: true,
        );

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $sold_equipments_discount = Decimal::create(0, 2);
        $sold_equipments_value = Decimal::create(0, 2);
        foreach ($contract->sold_equipments as $sold_equipment) {
            // conditional discount sum
            if (
                $contract_version->minimum_duration > 0
                && isset($sold_equipment->equipment_type->price)
                && isset($sold_equipment->equipment_type->price_with_obligation)
            ) {
                /** @psalm-suppress ImplicitToStringCast */
                $sold_equipments_discount = $sold_equipments_discount->add(
                    $sold_equipment->equipment_type->price
                        ->subtract($sold_equipment->equipment_type->price_with_obligation),
                );

                $sold_equipment_price = $sold_equipment->equipment_type->price_with_obligation;
            } else {
                $sold_equipment_price = $sold_equipment->equipment_type->price;
            }

            /** @psalm-suppress ImplicitToStringCast */
            $sold_equipments_value = $sold_equipments_value->add(
                $sold_equipment->equipment_type->price ?? Decimal::create(0, 2),
            );

            /** @psalm-suppress ImplicitToStringCast */
            $subtotal = $subtotal->add($sold_equipment_price ?? Decimal::create(0, 2));

            $this->printFramedRow(
                [
                    $sold_equipment->equipment_type->name,
                    $sold_equipment->serial_number ?? self::EMPTY_SERIAL,
                    Number::currency($sold_equipment_price?->toFloat() ?? ''),
                ],
                [130, 25, 25],
                ['L', 'C', 'R'],
            );

            unset($sold_equipment_price);
        }

        $this->printBlankRows(
            self::SOLD_EQUIPMENT_ROWS - min(self::SOLD_EQUIPMENT_ROWS, count($contract->sold_equipments)),
            [130, 25, 25],
        );

        $this->Ln(2);

        $this->printParagraph($this->handoverText('texts.activation_fee_obligation'), bold: true, gap: 0.0);

        // The total carries the weight of the statement above it, and is set to match.
        $this->printFramedRow(
            [$this->handoverText('tables.sold_equipments.total'), Number::currency($subtotal->toFloat())],
            [155, 25],
            ['L', 'R'],
            bold: true,
        );

        $this->Ln(6);

        return [$sold_equipments_discount, $sold_equipments_value];
    }

    /**
     * What leaving early costs, printed only where a discount was actually given.
     *
     * @param \App\Model\Entity\ContractVersion $contract_version Version being executed
     * @param \PhpCollective\DecimalObject\Decimal $discount Discount given for the commitment
     * @param \PhpCollective\DecimalObject\Decimal $value Undiscounted value of the equipment
     * @return void
     */
    private function printEarlyTerminationTerms(
        ContractVersion $contract_version,
        Decimal $discount,
        Decimal $value,
    ): void {
        if (!$discount->isPositive()) {
            return;
        }

        $this->printSectionHeading($this->handoverText('sections.early_termination'));

        /** @psalm-suppress ImplicitToStringCast */
        $this->printParagraph(
            strtr($this->handoverText('texts.early_termination_clause'), [
                '{full_price}' => Number::currency($value->toFloat()),
                '{duration}' => $this->contractDurationBefore($contract_version->minimum_duration),
                '{discounted_price}' => Number::currency($value->subtract($discount)->toFloat()),
                '{remaining_payment}' => Number::currency($discount->toFloat()),
            ]),
            bold: true,
        );
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

    /**
     * Reads one of the handover protocol's own texts.
     *
     * @param string $key Key under the handover documents block
     * @return string
     */
    private function handoverText(string $key): string
    {
        return Settings::getString('core.documents.contracts.handover.' . $key);
    }

    /**
     * Reads one of the labels the billing tables share.
     *
     * @param string $key Key under the billing block
     * @return string
     */
    private function billingText(string $key): string
    {
        return Settings::getString('core.documents.contracts.billing.' . $key);
    }
}
