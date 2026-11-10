<?php
declare(strict_types=1);

namespace App\Pdf;

use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersion;
use App\Model\Enum\ContractDocumentType;
use App\Model\Enum\IpAddressTypeOfUse;
use App\Pdf\Trait\ContractDurationTrait;
use App\Service\ContractPrint\ContractPrintData;
use Cake\I18n\Number;
use InvalidArgumentException;
use PhpCollective\DecimalObject\Decimal;
use Settings\Utility\Settings;

/**
 * The protocols that go with a contract: installation, uninstallation and an exchange of equipment.
 */
class HandoverProtocolPDF extends ContractPaperPDF
{
    use ContractDurationTrait;

    // constant for empty (null) serial numbers in the borrowed/sold equipment tables
    private const EMPTY_SERIAL = '';

    /**
     * How many rows the equipment tables are padded out to, so there is somewhere to write a
     * serial number by hand on a protocol that is filled in on site.
     */
    private const SOLD_EQUIPMENT_ROWS = 6;
    private const BORROWED_EQUIPMENT_ROWS = 5;

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
            $this->handoverText(match ($data->type) {
                ContractDocumentType::HandoverInstallation => 'subtitle_installation',
                ContractDocumentType::HandoverUninstallation => 'subtitle_uninstallation',
                ContractDocumentType::HandoverExchange => 'subtitle_exchange',
                default => throw new InvalidArgumentException('Unsupported handover protocol: ' . $data->type->value),
            }),
        );

        $this->printHandoverIdentification($data);
        $this->printBetween($this->label('between'));

        $this->printParties($this->label('provider'), $contract);
        $this->Ln();
        $this->drawSeparator(lnAfter: 0.5);

        $this->printCustomerBlock($contract);
        $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X, lnAfter: 4.0);

        match ($data->type) {
            ContractDocumentType::HandoverInstallation => $this->printInstallationProtocol($data),
            ContractDocumentType::HandoverUninstallation => $this->printUninstallationProtocol($data),
            ContractDocumentType::HandoverExchange => $this->printExchangeProtocol($data),
            default => throw new InvalidArgumentException('Unsupported handover protocol: ' . $data->type->value),
        };

        $this->printSignatureSection('double');

        $this->Close();
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
            && $data->type->requiresContractVersionToBeExecuted()
        ) {
            throw new InvalidArgumentException(
                'The contract version to be executed must be provided in order to generate'
                    . ' a handover protocol for installation or exchange',
            );
        }

        if (
            $data->contractVersionToBeTerminated === null
            && $data->type === ContractDocumentType::HandoverUninstallation
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
     * Says which contract the handover protocol belongs to.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    private function printHandoverIdentification(ContractPrintData $data): void
    {
        $columns = match ($data->type) {
            ContractDocumentType::HandoverInstallation => [
                [$this->label('contract_number'), (string)$data->contract->number],
                [$this->label('start_date'), (string)$this->executedVersion($data)->valid_from],
            ],
            // The day of the exchange is written in by hand, because the papers are printed ahead.
            ContractDocumentType::HandoverUninstallation => [
                [$this->label('contract_number'), (string)$data->contractNumberToBeTerminated],
                [$this->label('end_date'), (string)$this->terminatedVersion($data)->valid_until],
            ],
            ContractDocumentType::HandoverExchange => [
                [$this->label('contract_number'), (string)$data->contract->number],
            ],
            default => throw new InvalidArgumentException('Unsupported handover protocol: ' . $data->type->value),
        };

        $this->printLabelledRow($columns, 90.0);
    }

    /**
     * The installation protocol: how the customer gets on the network, what was lent to them,
     * what the installation cost, and what they are confirming by signing.
     *
     * The day of the installation and who took the equipment over on the spot are written in by
     * hand, as on the exchange protocol: the papers are printed ahead and not always signed on
     * the day, and whoever signs for the customer is not always the one at the door.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    private function printInstallationProtocol(ContractPrintData $data): void
    {
        $contract = $data->contract;
        $executed = $this->executedVersion($data);

        $this->printAccessInformation($data);
        $this->printBorrowedEquipmentForInstallation($data->equipmentHandedOver());

        $this->drawCross();
        $this->AddPage();

        [$soldEquipmentsDiscount, $soldEquipmentsValue] = $this->printActivationFeeAndSoldEquipment(
            $contract,
            $executed,
            $data->activatesTheService(),
            count($data->equipmentLentAfterwards()) > 0,
        );

        $this->printHandoverSection(
            'connection_point_state',
            'connection_point_state_text',
            lnBeforeText: 3,
        );
        $this->printHandoverSection('general_statements', 'general_statements_text', lnBeforeText: 4);

        $this->printEarlyTerminationTerms($contract, $executed, $soldEquipmentsDiscount, $soldEquipmentsValue);

        // A line of air between the two, because both are written in by hand.
        $this->printParagraph($this->handoverText('texts.installation_date_line'), gap: 2.0 + self::LINE_HEIGHT);
        $this->printParagraph($this->handoverText('texts.installation_received_on_site'), gap: 4.0);

        $this->printSectionHeading($this->handoverText('sections.final_statements'));
        $this->printParagraph(strtr($this->handoverText('texts.final_statements_text'), [
            '{contract_number}' => (string)$contract->number,
        ]));
    }

    /**
     * The uninstallation protocol: what is being taken back, in what state, and what is
     * settled on the doorstep.
     *
     * The day of the uninstallation and who handed the equipment over on the spot are written in
     * by hand, for the same reasons as on the installation protocol.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    private function printUninstallationProtocol(ContractPrintData $data): void
    {
        $this->printBorrowedEquipmentForUninstallation($data->equipmentTakenBack());

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

        $this->printParagraph($this->handoverText('texts.uninstallation_date_line'), gap: 2.0 + self::LINE_HEIGHT);
        $this->printParagraph($this->handoverText('texts.uninstallation_handed_over_on_site'), gap: 4.0);

        $this->printSectionHeading($this->handoverText('sections.final_statements'));
        $this->printParagraph(strtr($this->handoverText('texts.uninstallation_final_statements_text'), [
            '{contract_number}' => (string)$data->contractNumberToBeTerminated,
        ]));
    }

    /**
     * The exchange protocol: what goes back and in what state, everything borrowed once the
     * exchange is done, and what the customer is confirming by signing.
     *
     * The installation protocol with the returned equipment of the uninstallation one in it, so
     * that a swapped box is one paper rather than two. The day of the exchange and who took the
     * equipment over on the spot are written in by hand, because the papers are printed ahead and
     * whoever signs for the customer is not always the one at the door.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data Prepared print data
     * @return void
     */
    private function printExchangeProtocol(ContractPrintData $data): void
    {
        $contract = $data->contract;
        $executed = $this->executedVersion($data);
        $moved = $data->projectedEquipment;

        $this->printAccessInformation($data);

        // What goes back and what comes in its place are read together, so they start a sheet
        // of their own.
        $this->drawCross();
        $this->AddPage();

        $this->printSectionHeading($this->handoverText('sections.exchange_returned'));
        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->printTableCaption($this->handoverText('texts.uninstallation_borrowed_equipment_intro'));
        $this->printBorrowedEquipmentTable($moved->returned ?? [], padTo: self::BORROWED_EQUIPMENT_ROWS);
        $this->Ln(2);

        // The access details come first here, so the checks rarely fit under the table, and the
        // line that introduces them goes over with them rather than staying behind on its own.
        $checks = $this->handoverText('texts.exchange_equipment_checks_text');
        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->keepTogether(
            ($this->getNumLines($checks . PHP_EOL, self::TEXT_WIDTH) + 1) * self::LINE_HEIGHT,
        );
        $this->SetFont(self::FONT_FAMILY, 'U', self::BODY_FONT_SIZE);
        $this->printTableCaption($this->handoverText('texts.uninstallation_equipment_state'));
        $this->printParagraph($checks, gap: 4.0);

        // Everything borrowed once the exchange is done, so the protocol is the whole list the
        // contract speaks of from that day, what stays included.
        $this->printSectionHeading($this->handoverText('sections.exchange_borrowed'));
        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->printTableCaption($this->handoverText('texts.borrowed_equipment_intro'));
        $this->printBorrowedEquipmentTable($data->equipmentLentAfterwards(), padTo: 0);
        $this->Ln(2);
        $this->printParagraph($this->handoverText('texts.borrowed_equipment_return'), gap: 2.0);

        $this->drawCross();
        $this->AddPage();

        [$soldEquipmentsDiscount, $soldEquipmentsValue] = $this->printActivationFeeAndSoldEquipment(
            $contract,
            $executed,
            $data->activatesTheService(),
            count($data->equipmentLentAfterwards()) > 0,
        );

        $this->printHandoverSection(
            'connection_point_state',
            'connection_point_state_text',
            lnBeforeText: 3,
        );
        $this->printHandoverSection('general_statements', 'general_statements_text', lnBeforeText: 4);

        $this->printEarlyTerminationTerms($contract, $executed, $soldEquipmentsDiscount, $soldEquipmentsValue);

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        // A line of air between the two, because both are written in by hand.
        $this->printParagraph($this->handoverText('texts.exchange_date_line'), gap: 2.0 + self::LINE_HEIGHT);
        $this->printParagraph($this->handoverText('texts.exchange_received_on_site'), gap: 4.0);

        $this->printSectionHeading($this->handoverText('sections.final_statements'));
        $this->printParagraph(strtr($this->handoverText('texts.exchange_final_statements_text'), [
            '{contract_number}' => (string)$contract->number,
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
        $this->printTableCaption($this->handoverText('texts.endpoint_auth'));

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
        $this->printTableCaption(__d('documents', 'Assigned IP Addresses') . ':');

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
        $this->printTableCaption(__d('documents', 'Assigned IP Networks') . ':');

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
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
        $this->printTableCaption($this->handoverText('texts.default_network_intro'));

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
     * @param array<\App\Model\Entity\BorrowedEquipment> $equipments What is listed
     * @return void
     */
    private function printBorrowedEquipmentForInstallation(array $equipments): void
    {
        if (count($equipments) === 0) {
            return;
        }

        $this->printSectionHeading($this->handoverText('sections.borrowed_equipment'));

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->printTableCaption($this->handoverText('texts.borrowed_equipment_intro'));

        $this->printBorrowedEquipmentTable($equipments, padTo: 0);

        $this->Ln(2);
        $this->printParagraph($this->handoverText('texts.borrowed_equipment_return'), gap: 2.0);
    }

    /**
     * The equipment being taken back, and the state it is being taken back in.
     *
     * @param array<\App\Model\Entity\BorrowedEquipment> $equipments What is listed
     * @return void
     */
    private function printBorrowedEquipmentForUninstallation(array $equipments): void
    {
        $this->printSectionHeading($this->handoverText('sections.uninstallation_borrowed_equipment'));

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->printTableCaption($this->handoverText('texts.uninstallation_borrowed_equipment_intro'));

        $this->printBorrowedEquipmentTable($equipments, padTo: self::BORROWED_EQUIPMENT_ROWS);

        $this->Ln(2);
        $this->SetFont(self::FONT_FAMILY, 'U', self::BODY_FONT_SIZE);
        $this->printTableCaption($this->handoverText('texts.uninstallation_equipment_state'));

        $this->printParagraph($this->handoverText('texts.uninstallation_equipment_checks_text'), gap: 0.0);
    }

    /**
     * The table of lent equipment, optionally padded out with blank rows to write into.
     *
     * @param array<\App\Model\Entity\BorrowedEquipment> $equipments What is listed
     * @param int $padTo Number of rows the table is filled out to, or zero to list only what there is
     * @return void
     */
    private function printBorrowedEquipmentTable(array $equipments, int $padTo): void
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
        foreach ($equipments as $borrowed_equipment) {
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

        $this->printBlankRows($padTo - min($padTo, count($equipments)), [130, 25, 25]);
    }

    /**
     * What installing the service cost, and the equipment sold as part of it.
     *
     * The price a piece of equipment goes on the protocol at depends on whether the customer
     * committed: the discount is the provider's, and leaving early gives it back.
     *
     * Where the papers do not set the connection up, there is no fee to charge and only what is
     * sold is added up.
     *
     * @param \App\Model\Entity\Contract $contract Contract being printed
     * @param \App\Model\Entity\ContractVersion $contract_version Version being executed
     * @param bool $activates Whether the papers charge for setting the connection up
     * @param bool $withEquipment Whether any equipment is lent once the papers take effect
     * @return array Discount given for the commitment, and the undiscounted value
     * @phpstan-return array{0: \PhpCollective\DecimalObject\Decimal, 1: \PhpCollective\DecimalObject\Decimal}
     */
    private function printActivationFeeAndSoldEquipment(
        Contract $contract,
        ContractVersion $contract_version,
        bool $activates,
        bool $withEquipment,
    ): array {
        $this->printSectionHeading($this->handoverText('sections.activation_fee'));

        $subtotal = Decimal::create(0, 2);

        if ($activates) {
            $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
            $this->printTableCaption(
                $this->handoverText(
                    $withEquipment
                        ? 'texts.activation_fee_intro_with_equipment'
                        : 'texts.activation_fee_intro',
                ),
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
        }

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->printTableCaption($this->handoverText('texts.activation_fee_items_intro'));

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

        $this->SetFont(self::FONT_FAMILY, 'B', self::BODY_FONT_SIZE);
        $this->printTableCaption($this->handoverText($activates
            ? 'texts.activation_fee_obligation'
            : 'texts.activation_fee_obligation_without_fee'));

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
     * @param \App\Model\Entity\Contract $contract Contract being printed
     * @param \App\Model\Entity\ContractVersion $contract_version Version being executed
     * @param \PhpCollective\DecimalObject\Decimal $discount Discount given for the commitment
     * @param \PhpCollective\DecimalObject\Decimal $value Undiscounted value of the equipment
     * @return void
     */
    private function printEarlyTerminationTerms(
        Contract $contract,
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
            strtr($this->textByLegalStatus(
                'core.documents.contracts.handover.texts.early_termination_clause',
                $contract->customer,
            ), [
                '{full_price}' => Number::currency($value->toFloat()),
                '{duration}' => $this->contractDurationBefore($contract_version->minimum_duration),
                '{discounted_price}' => Number::currency($value->subtract($discount)->toFloat()),
                '{remaining_payment}' => Number::currency($discount->toFloat()),
            ]),
            bold: true,
        );
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
}
