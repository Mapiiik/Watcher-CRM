<?php
declare(strict_types=1);

namespace App\Pdf;

use App\Model\Entity\Billing;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersion;
use App\Model\Enum\ContractPrintType;
use App\Model\Enum\IpAddressTypeOfUse;
use App\Service\ContractPrint\ContractPrintData;
use Cake\I18n\Number;
use InvalidArgumentException;
use PhpCollective\DecimalObject\Decimal;
use Settings\Utility\Settings;

class ContractPDF extends AppPDF
{
    /**
     * Getter for contract duration text - short.
     *
     * @param int|null $duration Duration in months
     * @return string
     * @throws \InvalidArgumentException If duration is null or <= 0
     */
    private function contractDurationBefore(?int $duration): string
    {
        if (is_null($duration) || $duration <= 0) {
            throw new InvalidArgumentException('Invalid contract duration');
        }

        if ($duration < 2) {
            return strtr(Settings::getString('core.documents.contracts.duration.short_month'), [
                '{duration}' => $duration,
            ]);
        }

        return strtr(Settings::getString('core.documents.contracts.duration.short_months'), [
            '{duration}' => $duration,
        ]);
    }

    /**
     * Getter for contract duration text - long.
     *
     * @param int|null $duration Duration in months
     * @return string
     */
    private function contractDuration(?int $duration): string
    {
        if ($duration <= 0) {
            return Settings::getString('core.documents.contracts.duration.indefinite');
        }

        if ($duration < 2) {
            return strtr(Settings::getString('core.documents.contracts.duration.indefinite_with_min_month'), [
                '{duration}' => $duration,
            ]);
        }

        return strtr(Settings::getString('core.documents.contracts.duration.indefinite_with_min_months'), [
            '{duration}' => $duration,
        ]);
    }

    /**
     * Prints billing table.
     *
     * @param iterable<\App\Model\Entity\Billing> $billings Billings
     * @param \App\Model\Entity\ContractVersion $contract_version Contract version
     * @param string $format Additional font format
     * @return \PhpCollective\DecimalObject\Decimal Total cost
     */
    private function billingTable(iterable $billings, ContractVersion $contract_version, string $format): Decimal
    {
        $this->SetFont('DejaVuSerif', '' . $format, 8);
        $this->Cell(4, 4);
        $this->Cell(140, 4, Settings::getString('core.documents.contracts.billing.service'));
        $this->Cell(35, 4, Settings::getString('core.documents.contracts.billing.price_per_month'), align: 'R');
        $this->Ln();

        $totalCost = Decimal::create(0, 2);

        foreach ($billings as $billing) {
            $this->SetFont('DejaVuSerif', 'B' . $format, 8);
            $this->Cell(4, 4);
            $this->Cell(
                140,
                4,
                $billing->name
                . ($billing->billing_from > $contract_version->valid_from
                    ? ' ' . strtr(Settings::getString('core.documents.contracts.billing.from'), [
                        '{date}' => $billing->billing_from->__toString(),
                    ])
                    : '')
                . ($billing->billing_until
                    ? ' ' . strtr(Settings::getString('core.documents.contracts.billing.until'), [
                        '{date}' => $billing->billing_until->__toString(),
                    ])
                    : ''),
                align: 'L',
                stretch: 1,
            );
            $this->Cell(35, 4, Number::currency($billing->sum->toFloat()), align: 'R');
            $this->Ln();

            if ($billing->percentage_discount_sum->isPositive()) {
                $this->SetFont('DejaVuSerif', '' . $format, 8);
                $this->Cell(4, 4);
                $this->Cell(
                    140,
                    4,
                    strtr(Settings::getString('core.documents.contracts.billing.percentage_discount'), [
                        '{percentage}' => (string)$billing->percentage_discount,
                    ]),
                );
                $this->Cell(
                    35,
                    4,
                    Number::currency($billing->percentage_discount_sum->negate()->toFloat()),
                    align: 'R',
                );
                $this->Ln();
            }
            if ($billing->fixed_discount_sum->isPositive()) {
                $this->SetFont('DejaVuSerif', '' . $format, 8);
                $this->Cell(4, 4);
                $this->Cell(140, 4, Settings::getString('core.documents.contracts.billing.fixed_discount'));
                $this->Cell(35, 4, Number::currency($billing->fixed_discount_sum->negate()->toFloat()), align: 'R');
                $this->Ln();
            }

            /** @psalm-suppress ImplicitToStringCast */
            $totalCost = $totalCost->add($billing->total_price);
        }

        return $totalCost;
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

        $this->SetFont('DejaVuSerif', 'B', 8);

        if ($contract_version->minimum_duration <= 0) {
            if ($type === ContractPrintType::ContractNew) {
                $key = $withInstallation
                    ? 'activation_fee_no_commitment_with_installation'
                    : 'activation_fee_no_commitment';

                $this->MultiCell(
                    180,
                    4,
                    strtr(Settings::getString("core.documents.contracts.contract.texts.$key"), [
                        '{activation_fee}' => Number::currency($contract->activation_fee_sum->toFloat()),
                    ]) . PHP_EOL,
                    align: 'J',
                );
                $this->Ln(3);
            }
        } else {
            if ($type === ContractPrintType::ContractNew) {
                $key = $withInstallation
                    ? 'activation_fee_with_commitment_with_installation'
                    : 'activation_fee_with_commitment';

                $this->MultiCell(
                    180,
                    4,
                    strtr(Settings::getString("core.documents.contracts.contract.texts.$key"), [
                        '{activation_fee_obligation}' =>
                            Number::currency($contract->activation_fee_with_obligation_sum->toFloat()),
                    ]) . PHP_EOL,
                    align: 'J',
                );
                $this->Ln(3);
            }

            $clauseKey = $withInstallation
                ? 'activation_fee_clause_with_installation'
                : 'activation_fee_clause';

            $this->MultiCell(
                180,
                4,
                strtr(Settings::getString("core.documents.contracts.contract.texts.$clauseKey"), [
                    '{duration}' => $this->contractDurationBefore($contract_version->minimum_duration),
                    '{difference}' =>
                        Number::currency(
                            $contract->activation_fee_sum
                                ->subtract($contract->activation_fee_with_obligation_sum)
                                ->toFloat(),
                        ),
                    '{full_fee}' => Number::currency($contract->activation_fee_sum->toFloat()),
                ]) . PHP_EOL,
                align: 'J',
            );
            $this->Ln(3);
        }
    }

    /**
     * Generate PDF document - handover protocol
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data
     * @return void
     */
    public function generateHandoverProtocol(ContractPrintData $data): void
    {
        // Load data from print data object
        $type = $data->type;
        $contract = $data->contract;
        $contract_version = $data->contractVersion;
        $technical_details = $data->technicalDetails;
        $signed = $data->signed;

        // Disable default header and footer
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);

        // Add first page
        $this->AddPage();

        // Company logo
        $this->Image(K_PATH_IMAGES . 'logo-contract.png', 10, 5, 28);

        // Document title
        $this->SetFont('DejaVuSerif', 'B', 18);
        $this->Cell(187, 6, Settings::getString('core.documents.contracts.handover.title'), align: 'C');
        $this->Ln();

        // Document subtitle
        $this->SetFont('DejaVuSerif', 'B', 12);
        if ($type === ContractPrintType::HandoverInstallation) {
            $this->Cell(
                187,
                2,
                Settings::getString('core.documents.contracts.handover.subtitle_installation'),
                align: 'C',
            );
        } else {
            $this->Cell(
                187,
                2,
                Settings::getString('core.documents.contracts.handover.subtitle_uninstallation'),
                align: 'C',
            );
        }
        $this->Ln(3);

        // Separator line
        $this->drawSeparator(lnBefore: 4, lnAfter: 0.5);

        // Contract number + date
        $this->SetFont('DejaVuSerif', '', 8);
        if ($type === ContractPrintType::HandoverInstallation) {
            $this->Cell(90, 4, Settings::getString('core.documents.common.labels.contract_number'), align: 'C');
            $this->Cell(90, 4, Settings::getString('core.documents.common.labels.start_date'), align: 'C');
            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(90, 4, $contract->number, align: 'C');
            $this->Cell(90, 4, (string)$contract_version->valid_from, align: 'C');
        } else {
            $this->Cell(90, 4, Settings::getString('core.documents.common.labels.contract_number'), align: 'C');
            $this->Cell(90, 4, Settings::getString('core.documents.common.labels.end_date'), align: 'C');
            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(90, 4, $data->numberOfContractToBeTerminated, align: 'C');
            $this->Cell(90, 4, (string)$contract_version->valid_until, align: 'C');
        }
        $this->Ln();

        $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X, lnAfter: 3.0);

        // BETWEEN
        $this->SetFont('DejaVuSerif', 'B', 9);
        $this->Cell(187, 2, Settings::getString('core.documents.common.labels.between'), align: 'C');
        $this->Ln();

        // Provider section - company details block
        $this->printCompanyDetails(Settings::getString('core.documents.common.labels.provider'));

        // User section
        $this->SetFont('DejaVuSerif', 'B', 9);
        $this->Cell(187, 4, Settings::getString('core.documents.common.labels.and'), align: 'C');
        $this->Ln();
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.user'));

        $this->SetFont('DejaVuSerif', '', 8);
        $this->printUserType($contract);
        $this->Ln();

        // Separator line
        $this->drawSeparator(lnAfter: 0.5);

        // Customer personal and business data
        $this->SetFont('DejaVuSerif', '', 8);
        $this->printTable(
            [
                __d('documents', 'Billing Address') . ':',
                '',
            ],
            [
                [
                    [
                        'label' => Settings::getString('core.documents.common.labels.company'),
                        'value' => $this->strOrX($contract->billing_address->company, ''),
                    ],
                    [
                        'label' => Settings::getString('core.documents.common.labels.birth_date'),
                        'value' => (string)$contract->customer->date_of_birth,
                        'label_width' => 25,
                        'value_width' => 25,
                    ],
                    [
                        'label' => Settings::getString('core.documents.common.labels.identity_number'),
                        'value' => $this->strOrX($contract->customer->identity_number),
                        'label_width' => 10,
                        'value_width' => 30,
                    ],
                ],
                [
                    [
                        'label' => is_null($contract->billing_address->company)
                            ? Settings::getString('core.documents.common.labels.name')
                            : Settings::getString('core.documents.common.labels.represented'),
                        'value' => $contract->billing_address->full_name,
                    ],
                    [
                        'label' => Settings::getString('core.documents.common.labels.identity_card_number'),
                        'value' => (string)$contract->customer->identity_card_number,
                        'label_width' => 25,
                        'value_width' => 25,
                    ],
                    [
                        'label' => Settings::getString('core.documents.common.labels.vat_number'),
                        'value' => $this->strOrX($contract->customer->vat_number),
                        'label_width' => 10,
                        'value_width' => 30,
                    ],
                ],
                [
                    [
                        'label' => Settings::getString('core.documents.common.labels.street'),
                        'value' => $contract->billing_address->street_and_number,
                    ],
                    [
                        'label' => Settings::getString('core.documents.common.labels.phone'),
                        'value' => $contract->customer->phone,
                        'label_width' => 25,
                        'value_width' => 65,
                    ],
                ],
                [
                    [
                        'label' => Settings::getString('core.documents.common.labels.zip_city'),
                        'value' => $contract->billing_address->zip_and_city,
                    ],
                    [
                        'label' => Settings::getString('core.documents.common.labels.email'),
                        'value' => $contract->customer->email,
                        'label_width' => 25,
                        'value_width' => 65,
                    ],
                ],
            ],
        );

        // Installation / Delivery / Permanent addresses
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

        $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X, lnAfter: 4.0);

        // BEGIN INSTALLATION
        if ($type == ContractPrintType::HandoverInstallation) :
            // ACCESS INFORMATION
            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(4, Settings::getString('core.documents.contracts.handover.sections.access_info'));
            $this->Ln();

            $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);

            $this->SetFont('DejaVuSerif', '', 8);
            $this->Write(4, Settings::getString('core.documents.contracts.handover.texts.endpoint_auth'));
            $this->Ln(5);

            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(4, 5);
            //$this->Cell(60, 5, __d('documents', 'Access Point') . ' / ' . __d('documents', 'SSID'), border: 1, align: 'C');
            $this->Cell(90, 5, __d('documents', 'Username'), border: 1, align: 'C');
            $this->Cell(90, 5, __d('documents', 'Password'), border: 1, align: 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', '', 8);
            $this->Cell(4, 5);
            //$this->Cell(60, 5, $technical_details->accessPoint ?? '-', border: 1, align: 'C');
            $this->Cell(90, 5, $technical_details->radiusUsername ?? '-', border: 1, align: 'C');
            $this->Cell(90, 5, $technical_details->radiusPassword ?? '-', border: 1, align: 'C');
            $this->Ln();

            $this->Ln(1);

            if (!empty($contract->ip_addresses)) {
                $this->SetFont('DejaVuSerif', '', 8);
                $this->Write(4, __d('documents', 'Assigned IP Addresses') . ':');
                $this->Ln(5);

                $this->SetFont('DejaVuSerif', 'B', 8);
                $this->Cell(4, 5);
                $this->Cell(60, 5, __d('documents', 'IP Address'), border: 1, align: 'C');
                $this->Cell(60, 5, __d('documents', 'IP Network'), border: 1, align: 'C');
                $this->Cell(60, 5, __d('documents', 'IP Gateway'), border: 1, align: 'C');
                $this->Ln();

                $this->SetFont('DejaVuSerif', '', 8);
                foreach ($contract->ip_addresses as $ipAddress) {
                    // load range for customer address set manually
                    if (
                        $ipAddress->type_of_use == IpAddressTypeOfUse::CustomerManually
                        && isset($ipAddress->ip_address_ranges)
                    ) {
                        $range = $ipAddress->ip_address_ranges->first();
                    }
                    // skip processing for technology address set manually
                    if ($ipAddress->type_of_use == IpAddressTypeOfUse::TechnologyManually) {
                        continue 1;
                    }
                    $this->Cell(4, 5);
                    $this->Cell(60, 5, $ipAddress->ip_address, border: 1, align: 'C');
                    $this->Cell(60, 5, $range['ip_network'] ?? '-', border: 1, align: 'C');
                    $this->Cell(60, 5, $range['ip_gateway'] ?? '-', border: 1, align: 'C');
                    $this->Ln();

                    unset($range);
                }

                $this->Ln(1);
            }

            if (!empty($contract->ip_networks)) {
                $this->SetFont('DejaVuSerif', '', 8);
                $this->Write(4, __d('documents', 'Assigned IP Networks') . ':');
                $this->Ln(5);

                $this->SetFont('DejaVuSerif', '', 8);
                $this->Cell(4, 5);
                $this->MultiCell(
                    180,
                    4,
                    implode(', ', array_column($contract->ip_networks, 'ip_network')),
                    border: 1,
                    align: 'L',
                );

                $this->Ln(1);
            }

            // Default IP settings
            $this->SetFont('DejaVuSerif', '', 8);
            $this->Write(4, Settings::getString('core.documents.contracts.handover.texts.default_network_intro'));
            $this->Ln(5);

            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(4, 5);
            $this->Cell(60, 5, __d('documents', 'IP Network'), border: 1, align: 'C');
            $this->Cell(60, 5, __d('documents', 'IP Gateway'), border: 1, align: 'C');
            $this->Cell(60, 5, __d('documents', 'DNS Servers'), border: 1, align: 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', '', 8);
            $this->Cell(4, 5);
            $this->Cell(
                60,
                5,
                Settings::getString('core.documents.contracts.handover.defaults.ip_network'),
                border: 1,
                align: 'C',
            );
            $this->Cell(
                60,
                5,
                Settings::getString('core.documents.contracts.handover.defaults.ip_gateway'),
                border: 1,
                align: 'C',
            );
            $this->Cell(
                60,
                5,
                Settings::getString('core.documents.contracts.handover.defaults.dns_servers'),
                border: 1,
                align: 'C',
            );
            $this->Ln();

            $this->Ln(1);

            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(4, 5);
            $this->Cell(90, 5, __d('documents', 'WiFi - SSID'), border: 1, align: 'C');
            $this->Cell(90, 5, __d('documents', 'WiFi - Password'), border: 1, align: 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', '', 8);
            $this->Cell(4, 5);
            $this->Cell(90, 5, '', border: 1, align: 'C');
            $this->Cell(90, 5, '', border: 1, align: 'C');
            $this->Ln();

            $this->Ln(1);

            $this->writeHTML(
                Settings::getString('core.documents.contracts.handover.texts.portal_access_html'),
                true,
                false,
                false,
                true,
                '',
            );

            $this->Ln(4);

            // BORROWED EQUIPMENTS
            if (count($contract->borrowed_equipments) > 0) {
                $this->SetFont('DejaVuSerif', 'B', 9);
                $this->Write(4, Settings::getString('core.documents.contracts.handover.sections.borrowed_equipment'));
                $this->Ln();

                $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);

                $this->SetFont('DejaVuSerif', '', 8);
                $this->Write(
                    4,
                    Settings::getString('core.documents.contracts.handover.texts.borrowed_equipment_intro'),
                );
                $this->Ln(5);

                $this->SetFont('DejaVuSerif', 'B', 8);
                $this->Cell(4, 5);
                $this->Cell(
                    130,
                    5,
                    Settings::getString('core.documents.contracts.handover.tables.borrowed_equipments.device'),
                    1,
                );
                $this->Cell(
                    25,
                    5,
                    Settings::getString('core.documents.contracts.handover.tables.borrowed_equipments.serial'),
                    border: 1,
                    align: 'C',
                );
                $this->Cell(
                    25,
                    5,
                    Settings::getString('core.documents.contracts.handover.tables.borrowed_equipments.value'),
                    border: 1,
                    align: 'R',
                );
                $this->Ln();

                $this->SetFont('DejaVuSerif', '', 8);
                foreach ($contract->borrowed_equipments as $borrowed_equipment) {
                    $this->Cell(4, 5);
                    $this->Cell(130, 5, $borrowed_equipment->equipment_type->name, 1);
                    $this->Cell(25, 5, $borrowed_equipment->serial_number, border: 1, align: 'C');
                    $this->Cell(
                        25,
                        5,
                        Number::currency($borrowed_equipment->equipment_type->price?->toFloat() ?? ''),
                        border: 1,
                        align: 'R',
                    );
                    $this->Ln();
                }

                $this->Ln(2);
                $this->SetFont('DejaVuSerif', '', 8);
                $this->MultiCell(
                    180,
                    4,
                    Settings::getString('core.documents.contracts.handover.texts.borrowed_equipment_return') . PHP_EOL,
                    align: 'J',
                );
                $this->Ln(2);
            }

            // CROSS
            $this->drawCross();

            // add a page
            $this->AddPage();

            // SOLD EQUIPMENTS
            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(4, Settings::getString('core.documents.contracts.handover.sections.activation_fee'));
            $this->Ln();

            $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);
            $this->SetFont('DejaVuSerif', '', 8);
            if (count($contract->borrowed_equipments) > 0) {
                $this->MultiCell(
                    180,
                    4,
                    Settings::getString(
                        'core.documents.contracts.handover.texts.activation_fee_intro_with_equipment',
                    ) . PHP_EOL,
                    align: 'J',
                );
            } else {
                $this->MultiCell(
                    180,
                    4,
                    Settings::getString('core.documents.contracts.handover.texts.activation_fee_intro') . PHP_EOL,
                    align: 'J',
                );
            }
            $this->Ln(1);

            $subtotal = $contract_version->minimum_duration <= 0
                ? $contract->activation_fee_sum
                : $contract->activation_fee_with_obligation_sum;

            $this->Cell(4, 5);
            $this->Cell(
                155,
                5,
                Settings::getString('core.documents.contracts.handover.tables.sold_equipments.activation_fee'),
                1,
            );
            $this->Cell(
                25,
                5,
                Number::currency($subtotal->toFloat()),
                border: 1,
                align: 'R',
            );
            $this->Ln();

            $this->Ln(2);

            $this->SetFont('DejaVuSerif', '', 8);
            $this->Write(4, Settings::getString('core.documents.contracts.handover.texts.activation_fee_items_intro'));
            $this->Ln(5);

            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(4, 5);
            $this->Cell(
                130,
                5,
                Settings::getString('core.documents.contracts.handover.tables.sold_equipments.item'),
                1,
            );
            $this->Cell(
                25,
                5,
                Settings::getString('core.documents.contracts.handover.tables.sold_equipments.serial'),
                border: 1,
                align: 'C',
            );
            $this->Cell(
                25,
                5,
                Settings::getString('core.documents.contracts.handover.tables.sold_equipments.price'),
                border: 1,
                align: 'R',
            );
            $this->Ln();

            $this->SetFont('DejaVuSerif', '', 8);
            $sold_equipments_discount = Decimal::create(0, 2);
            $sold_equipments_value = Decimal::create(0, 2);
            foreach ($contract->sold_equipments as $sold_equipment) {
                // conditional discount sum
                if (
                    $contract_version->minimum_duration > 0
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
                $this->Cell(4, 5);
                $this->Cell(130, 5, $sold_equipment->equipment_type->name, 1);
                $this->Cell(25, 5, $sold_equipment->serial_number, border: 1, align: 'C');
                $this->Cell(25, 5, Number::currency($sold_equipment_price?->toFloat() ?? ''), border: 1, align: 'R');
                $this->Ln();

                unset($sold_equipment_price);
            }
            $count = 6 - min(6, count($contract->sold_equipments));
            for ($i = 1; $i <= $count; $i++) {
                $this->Cell(4, 5);
                $this->Cell(130, 5, '', 1);
                $this->Cell(25, 5, '', border: 1, align: 'C');
                $this->Cell(25, 5, '', border: 1, align: 'C');
                $this->Ln();
            }
            unset($count);

            $this->Ln(2);

            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->MultiCell(
                180,
                4,
                Settings::getString('core.documents.contracts.handover.texts.activation_fee_obligation') . PHP_EOL,
                align: 'J',
            );

            $this->Cell(4, 5);
            $this->Cell(
                155,
                5,
                Settings::getString('core.documents.contracts.handover.tables.sold_equipments.total'),
                1,
            );
            $this->Cell(
                25,
                5,
                Number::currency($subtotal->toFloat()),
                border: 1,
                align: 'R',
            );
            $this->Ln();

            $this->Ln(6);

            // CONNECTION POINT STATE
            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(4, Settings::getString('core.documents.contracts.handover.sections.connection_point_state'));
            $this->Ln();

            $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);
            $this->SetFont('DejaVuSerif', '', 8);
            $this->Ln(3);
            $this->MultiCell(
                180,
                4,
                Settings::getString('core.documents.contracts.handover.texts.connection_point_state_text') . PHP_EOL,
                align: 'J',
            );
            $this->Ln(6);

            // GENERAL STATEMENTS
            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(4, Settings::getString('core.documents.contracts.handover.sections.general_statements'));
            $this->Ln();

            $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);
            $this->SetFont('DejaVuSerif', '', 8);
            $this->Ln(4);
            $this->MultiCell(
                180,
                4,
                Settings::getString('core.documents.contracts.handover.texts.general_statements_text') . PHP_EOL,
                align: 'J',
            );
            $this->Ln(6);

            // EARLY TERMINATION TERMS
            if ($sold_equipments_discount->isPositive()) {
                $this->SetFont('DejaVuSerif', 'B', 9);
                $this->Write(4, Settings::getString('core.documents.contracts.handover.sections.early_termination'));
                $this->Ln();

                $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);

                $this->SetFont('DejaVuSerif', 'B', 8);
                /** @psalm-suppress ImplicitToStringCast */
                $this->MultiCell(
                    180,
                    4,
                    strtr(
                        Settings::getString('core.documents.contracts.handover.texts.early_termination_clause'),
                        [
                            '{full_price}' => Number::currency($sold_equipments_value->toFloat()),
                            '{duration}' => $this->contractDurationBefore($contract_version->minimum_duration),
                            '{discounted_price}' => Number::currency(
                                $sold_equipments_value->subtract($sold_equipments_discount)->toFloat(),
                            ),
                            '{remaining_payment}' => Number::currency($sold_equipments_discount->toFloat()),
                        ],
                    ) . PHP_EOL,
                    align: 'J',
                );
                $this->Ln(3);
            }

            // FINAL STATEMENTS
            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(4, Settings::getString('core.documents.contracts.handover.sections.final_statements'));
            $this->Ln();

            $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);
            $this->SetFont('DejaVuSerif', '', 8);
            $this->MultiCell(
                180,
                4,
                strtr(
                    Settings::getString('core.documents.contracts.handover.texts.final_statements_text'),
                    ['{contract_number}' => $contract->number],
                ) . PHP_EOL,
                align: 'J',
            );
            $this->Ln(3);
        endif;
        // END INSTALLATION

        // BEGIN UNINSTALLATION
        if ($type == ContractPrintType::HandoverUninstallation) :
            // BORROWED EQUIPMENTS
            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(
                4,
                Settings::getString('core.documents.contracts.handover.sections.uninstallation_borrowed_equipment'),
            );
            $this->Ln();

            $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);
            $this->SetFont('DejaVuSerif', '', 8);
            $this->Write(
                4,
                Settings::getString('core.documents.contracts.handover.texts.uninstallation_borrowed_equipment_intro'),
            );
            $this->Ln(5);

            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(4, 5);
            $this->Cell(
                130,
                5,
                Settings::getString('core.documents.contracts.handover.tables.borrowed_equipments.device'),
                1,
            );
            $this->Cell(
                25,
                5,
                Settings::getString('core.documents.contracts.handover.tables.borrowed_equipments.serial'),
                border: 1,
                align: 'C',
            );
            $this->Cell(
                25,
                5,
                Settings::getString('core.documents.contracts.handover.tables.borrowed_equipments.value'),
                border: 1,
                align: 'R',
            );
            $this->Ln();

            $this->SetFont('DejaVuSerif', '', 8);
            foreach ($contract->borrowed_equipments as $borrowed_equipment) {
                $this->Cell(4, 5);
                $this->Cell(130, 5, $borrowed_equipment->equipment_type->name, 1);
                $this->Cell(25, 5, $borrowed_equipment->serial_number, border: 1, align: 'C');
                $this->Cell(
                    25,
                    5,
                    Number::currency($borrowed_equipment->equipment_type->price?->toFloat() ?? ''),
                    border: 1,
                    align: 'R',
                );
                $this->Ln();
            }
            $count = 5 - min(5, count($contract->borrowed_equipments));
            for ($i = 1; $i <= $count; $i++) {
                $this->Cell(4, 5);
                $this->Cell(130, 5, '', 1);
                $this->Cell(25, 5, '', border: 1, align: 'C');
                $this->Cell(25, 5, '', border: 1, align: 'C');
                $this->Ln();
            }

            $this->Ln(2);
            $this->SetFont('DejaVuSerif', 'U', 8);
            $this->Write(
                4,
                Settings::getString('core.documents.contracts.handover.texts.uninstallation_equipment_state'),
            );
            $this->Ln(5);

            $this->SetFont('DejaVuSerif', '', 8);
            $this->MultiCell(
                180,
                4,
                Settings::getString('core.documents.contracts.handover.texts.uninstallation_equipment_checks_text')
                    . PHP_EOL,
                align: 'J',
            );

            // CROSS
            $this->drawCross();

            // add a page
            $this->AddPage();

            // CASH PAYMENT
            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(
                4,
                Settings::getString('core.documents.contracts.handover.sections.uninstallation_cash_payment'),
            );
            $this->Ln();

            $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);
            $this->SetFont('DejaVuSerif', '', 8);
            $this->Ln(4);
            $this->MultiCell(
                180,
                4,
                Settings::getString('core.documents.contracts.handover.texts.uninstallation_cash_payment_text')
                    . PHP_EOL,
                align: 'J',
            );
            $this->Ln(6);

            // GENERAL STATEMENTS (shared section name, unique text)
            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(4, Settings::getString('core.documents.contracts.handover.sections.general_statements'));
            $this->Ln();

            $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);
            $this->SetFont('DejaVuSerif', '', 8);
            $this->Ln(4);
            $this->MultiCell(
                180,
                4,
                Settings::getString('core.documents.contracts.handover.texts.uninstallation_general_statements_text')
                    . PHP_EOL,
                align: 'J',
            );
            $this->Ln(6);

            // FINAL STATEMENTS (shared section name, unique text)
            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(4, Settings::getString('core.documents.contracts.handover.sections.final_statements'));
            $this->Ln();

            $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);
            $this->SetFont('DejaVuSerif', '', 8);
            $this->MultiCell(
                180,
                4,
                strtr(
                    Settings::getString('core.documents.contracts.handover.texts.uninstallation_final_statements_text'),
                    ['{contract_number}' => $data->numberOfContractToBeTerminated],
                ) . PHP_EOL,
                align: 'J',
            );
            $this->Ln(3);
        endif;
        // END UNINSTALLATION

        // Signature section
        $this->printSignatureSection('double', $signed);

        $this->Close();
    }

    /**
     * Generate PDF document - contract
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data
     * @return void
     */
    public function generateContract(ContractPrintData $data): void {
        // Load data from print data object
        $type = $data->type;
        $contract = $data->contract;
        $contract_version = $data->contractVersion;
        $contract_version_to_be_replaced = $data->contractVersionToBeReplaced;
        $signed = $data->signed;

        // Disable default header and footer
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);

        // Add first page
        $this->AddPage();

        // Company logo
        $this->Image(K_PATH_IMAGES . 'logo-contract.png', 10, 5, 28);

        $this->SetFont('DejaVuSerif', 'BI', 8);

        // Document title and subtitle
        switch ($type) {
            case ContractPrintType::ContractNew:
            case ContractPrintType::ContractNewX:
                $this->SetFont('DejaVuSerif', 'B', 18);
                $this->Cell(187, 6, Settings::getString('core.documents.contracts.contract.title_new'), align: 'C');
                $this->Ln();

                $this->SetFont('DejaVuSerif', 'B', 12);
                $this->Cell(187, 2, Settings::getString('core.documents.contracts.contract.subtitle_new'), align: 'C');
                $this->Ln(3);
                break;

            case ContractPrintType::ContractAmendment:
                $this->SetFont('DejaVuSerif', 'B', 18);
                $this->Cell(
                    187,
                    6,
                    Settings::getString('core.documents.contracts.contract.title_amendment'),
                    align: 'C',
                );
                $this->Ln();

                $this->SetFont('DejaVuSerif', 'B', 12);
                $this->Cell(
                    187,
                    2,
                    Settings::getString('core.documents.contracts.contract.subtitle_amendment'),
                    align: 'C',
                );
                $this->Ln(3);
                break;

            case ContractPrintType::ContractTermination:
                $this->SetFont('DejaVuSerif', 'B', 18);
                $this->Cell(
                    187,
                    6,
                    Settings::getString('core.documents.contracts.contract.title_termination'),
                    align: 'C',
                );
                $this->Ln();

                $this->SetFont('DejaVuSerif', 'B', 12);
                $this->Cell(
                    187,
                    2,
                    Settings::getString('core.documents.contracts.contract.subtitle_termination'),
                    align: 'C',
                );
                $this->Ln(3);
                break;
        }

        // Separator line
        $this->drawSeparator(lnBefore: 4, lnAfter: 0.5);

        // Contract number + date
        switch ($type) {
            case ContractPrintType::ContractNew:
            case ContractPrintType::ContractNewX:
                $this->SetFont('DejaVuSerif', '', 8);
                $this->Cell(90, 4, Settings::getString('core.documents.common.labels.contract_number'), align: 'C');
                $this->Cell(90, 4, Settings::getString('core.documents.common.labels.start_date'), align: 'C');
                $this->Ln();
                $this->SetFont('DejaVuSerif', 'B', 8);
                $this->Cell(90, 4, $contract->number, align: 'C');
                $this->Cell(90, 4, (string)$contract_version->valid_from, align: 'C');
                $this->Ln();

                $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X, lnAfter: 3.0);

                $this->SetFont('DejaVuSerif', 'B', 9);
                $this->Cell(187, 2, Settings::getString('core.documents.common.labels.between_new'), align: 'C');
                $this->Ln();
                break;

            case ContractPrintType::ContractAmendment:
                $this->SetFont('DejaVuSerif', '', 8);
                $this->Cell(45, 4, Settings::getString('core.documents.common.labels.contract_number'), align: 'C');
                $this->Cell(45, 4, Settings::getString('core.documents.common.labels.conclusion_date'), align: 'C');
                $this->Cell(45, 4, Settings::getString('core.documents.common.labels.amendment_number'), align: 'C');
                $this->Cell(45, 4, Settings::getString('core.documents.common.labels.amendment_effective'), align: 'C');
                $this->Ln();
                $this->SetFont('DejaVuSerif', 'B', 8);
                $this->Cell(45, 4, $contract->number, align: 'C');
                $this->Cell(45, 4, (string)$contract_version->conclusion_date, align: 'C');
                $this->Cell(45, 4, (string)($contract_version->number_of_amendments + 1), align: 'C');
                $this->Cell(45, 4, (string)$data->effectiveDateOfAmendment, align: 'C');
                $this->Ln();

                $this->drawSeparator(lnAfter: 3.0);

                $this->SetFont('DejaVuSerif', 'B', 9);
                $this->Cell(187, 2, Settings::getString('core.documents.common.labels.between_amendment'), align: 'C');
                $this->Ln();
                break;

            case ContractPrintType::ContractTermination:
                $this->SetFont('DejaVuSerif', '', 8);
                $this->Cell(60, 4, Settings::getString('core.documents.common.labels.contract_number'), align: 'C');
                $this->Cell(60, 4, Settings::getString('core.documents.common.labels.conclusion_date'), align: 'C');
                $this->Cell(60, 4, Settings::getString('core.documents.common.labels.end_date'), align: 'C');
                $this->Ln();
                $this->SetFont('DejaVuSerif', 'B', 8);
                $this->Cell(60, 4, $data->numberOfContractToBeTerminated, align: 'C');
                $this->Cell(60, 4, (string)$contract_version->conclusion_date, align: 'C');
                $this->Cell(60, 4, (string)$contract_version->valid_until, align: 'C');
                $this->Ln();

                $this->drawSeparator(lnAfter: 3.0);

                $this->SetFont('DejaVuSerif', 'B', 9);
                $this->Cell(
                    187,
                    2,
                    Settings::getString('core.documents.common.labels.between_termination'),
                    align: 'C',
                );
                $this->Ln();
                break;
        }

        // Provider section - company details block
        $this->printCompanyDetails(Settings::getString('core.documents.common.labels.provider'));

        $this->SetFont('DejaVuSerif', 'B', 9);
        $this->Cell(187, 4, Settings::getString('core.documents.common.labels.and'), align: 'C');
        $this->Ln();
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.user'));

        $this->SetFont('DejaVuSerif', '', 8);
        $this->printUserType($contract);

        // Subscriber Verification Code
        if (!empty($contract->subscriber_verification_code)) {
            $this->Cell(
                60,
                4,
                Settings::getString('core.documents.common.labels.subscriber_verification_code') . ':',
                align: 'R',
            );
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(60, 4, $contract->subscriber_verification_code);
        }

        $this->Ln();

        // Separator line
        $this->drawSeparator(lnAfter: 0.5);

        // Customer personal and business data
        $this->SetFont('DejaVuSerif', '', 8);
        $this->printTable(
            [
                __d('documents', 'Billing Address') . ':',
                '',
            ],
            [
                [
                    [
                        'label' => Settings::getString('core.documents.common.labels.company'),
                        'value' => $this->strOrX($contract->billing_address->company, ''),
                    ],
                    [
                        'label' => Settings::getString('core.documents.common.labels.birth_date'),
                        'value' => (string)$contract->customer->date_of_birth,
                        'label_width' => 25,
                        'value_width' => 25,
                    ],
                    [
                        'label' => Settings::getString('core.documents.common.labels.identity_number'),
                        'value' => $this->strOrX($contract->customer->identity_number),
                        'label_width' => 10,
                        'value_width' => 30,
                    ],
                ],
                [
                    [
                        'label' => is_null($contract->billing_address->company)
                            ? Settings::getString('core.documents.common.labels.name')
                            : Settings::getString('core.documents.common.labels.represented'),
                        'value' => $contract->billing_address->full_name,
                    ],
                    [
                        'label' => Settings::getString('core.documents.common.labels.identity_card_number'),
                        'value' => (string)$contract->customer->identity_card_number,
                        'label_width' => 25,
                        'value_width' => 25,
                    ],
                    [
                        'label' => Settings::getString('core.documents.common.labels.vat_number'),
                        'value' => $this->strOrX($contract->customer->vat_number),
                        'label_width' => 10,
                        'value_width' => 30,
                    ],
                ],
                [
                    [
                        'label' => Settings::getString('core.documents.common.labels.street'),
                        'value' => $contract->billing_address->street_and_number,
                    ],
                    [
                        'label' => Settings::getString('core.documents.common.labels.phone'),
                        'value' => $contract->customer->phone,
                        'label_width' => 25,
                        'value_width' => 65,
                    ],
                ],
                [
                    [
                        'label' => Settings::getString('core.documents.common.labels.zip_city'),
                        'value' => $contract->billing_address->zip_and_city,
                    ],
                    [
                        'label' => Settings::getString('core.documents.common.labels.email'),
                        'value' => $contract->customer->email,
                        'label_width' => 25,
                        'value_width' => 65,
                    ],
                ],
            ],
        );

        // Installation / Delivery / Permanent addresses
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

        // Separator line
        $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X, lnAfter: 3.0);

        if ($type === ContractPrintType::ContractTermination) {
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Write(
                4,
                strtr(Settings::getString('core.documents.contracts.contract.texts.termination_intro'), [
                    '{contract_number}' => $data->numberOfContractToBeTerminated,
                    '{conclusion_date}' => $contract_version->conclusion_date->__toString(),
                    '{valid_until}' => $contract_version->valid_until->__toString(),
                ]),
            );
            $this->Ln();
            $this->Ln();
            $this->SetFont('DejaVuSerif', '', 8);
            $this->Write(4, Settings::getString('core.documents.contracts.contract.texts.termination_final'));
            $this->Ln();
        }

        if ($type === ContractPrintType::ContractNew || $type === ContractPrintType::ContractNewX) {
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Write(
                4,
                strtr(Settings::getString('core.documents.contracts.contract.texts.new_intro'), [
                    '{minimum_duration}' => $this->contractDuration($contract_version->minimum_duration),
                ]),
            );
            $this->Ln();
            $this->Write(
                4,
                strtr(Settings::getString('core.documents.contracts.contract.texts.new_start_date'), [
                    '{valid_from}' => $contract_version->valid_from->__toString(),
                ]),
            );
            $this->Ln();
            $this->Ln();

            if ($type === ContractPrintType::ContractNewX) {
                $this->Write(
                    4,
                    strtr(Settings::getString('core.documents.contracts.contract.texts.new_x_intro'), [
                        '{contract_number}' => $data->numberOfContractToBeTerminated,
                        '{old_conclusion_date}' => $contract_version_to_be_replaced->conclusion_date,
                        '{termination_date}' => $contract_version->valid_from->subDays(1)->__toString(),
                    ]),
                );
                $this->Ln();
                $this->Ln();
            }
        }

        if ($type === ContractPrintType::ContractAmendment) {
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Write(
                4,
                strtr(Settings::getString('core.documents.contracts.contract.texts.amendment_intro'), [
                    '{valid_from}' => $data->effectiveDateOfAmendment->__toString(),
                ]),
            );
            $this->Ln();
            $this->Ln();
        }

        if ($type === ContractPrintType::ContractNew || $type === ContractPrintType::ContractNewX || $type === ContractPrintType::ContractAmendment) {
            if ($type === ContractPrintType::ContractAmendment) {
                $format = 'I';
            } else {
                $format = '';
            }

            // sum of all items
            $totalCost = Decimal::create(0, 2);

            // billing of pricelist items
            if (count($data->getActiveStandardBillings()) > 0) {
                $this->SetFont('DejaVuSerif', 'B' . $format, 9);
                $this->Cell(
                    187,
                    3,
                    Settings::getString('core.documents.contracts.contract.sections.billing_pricelist'),
                );
                $this->Ln();

                $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);

                $totalCost = $totalCost->add(
                    $this->billingTable($data->getActiveStandardBillings(), $contract_version, $format),
                );
                $this->Ln();
            }

            // billing of non-pricelist items
            if (count($data->getActiveIndividualBillings()) > 0) {
                $this->SetFont('DejaVuSerif', 'B' . $format, 9);
                $this->Cell(
                    187,
                    3,
                    Settings::getString('core.documents.contracts.contract.sections.billing_individual'),
                );
                $this->Ln();

                $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);

                $totalCost = $totalCost->add(
                    $this->billingTable($data->getActiveIndividualBillings(), $contract_version, $format),
                );

                $this->SetFont('DejaVuSerif', $format, 7);
                $this->Cell(4, 4);
                $this->MultiCell(
                    180,
                    4,
                    Settings::getString('core.documents.contracts.contract.texts.individual_clause'),
                    align: 'L',
                );
                $this->Ln();
            }

            // future billing of pricelist items
            if (count($data->getFutureStandardBillings()) > 0) {
                $this->SetFont('DejaVuSerif', 'B' . $format, 9);
                $this->Cell(
                    187,
                    3,
                    Settings::getString('core.documents.contracts.contract.sections.billing_future_pricelist'),
                );
                $this->Ln();

                $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);

                $this->billingTable($data->getFutureStandardBillings(), $contract_version, $format);
                $this->Ln();
            }

            // future billing of non-pricelist items
            if (count($data->getFutureIndividualBillings()) > 0) {
                $this->SetFont('DejaVuSerif', 'B' . $format, 9);
                $this->Cell(
                    187,
                    3,
                    Settings::getString('core.documents.contracts.contract.sections.billing_future_individual'),
                );
                $this->Ln();

                $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);

                $this->billingTable($data->getFutureIndividualBillings(), $contract_version, $format);

                $this->SetFont('DejaVuSerif', $format, 7);
                $this->Cell(4, 4);
                $this->MultiCell(
                    180,
                    4,
                    Settings::getString('core.documents.contracts.contract.texts.individual_clause'),
                    align: 'L',
                );
                $this->Ln();
            }

            $this->SetFont('DejaVuSerif', 'B' . $format, 9);
            $this->Cell(187, 3, Settings::getString('core.documents.contracts.contract.sections.payment_info'));
            $this->Ln();

            $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);
            $this->SetFont('DejaVuSerif', $format, 8);
            $this->Cell(45, 4, Settings::getString('core.documents.common.labels.payment_period'), align: 'C');
            $this->Cell(45, 4, Settings::getString('core.documents.common.labels.payment_method'), align: 'C');
            $this->Cell(45, 4, Settings::getString('core.documents.common.labels.first_payment_date'), align: 'C');
            $this->Cell(45, 4, Settings::getString('core.documents.common.labels.first_payment_total'), align: 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', 'B' . $format, 8);
            $this->Cell(45, 4, Settings::getString('core.documents.common.labels.monthly'), align: 'C');
            $this->Cell(45, 4, Settings::getString('core.documents.common.labels.bank_transfer'), align: 'C');
            $this->Cell(
                45,
                4,
                'do ' . $contract_version->valid_from->day(1)->addMonths(1)->addDays(9)->__toString(),
                align: 'C',
            );

            // reverse charge
            if ($contract->customer->accounting_profile->reverse_charge) {
                $this->Cell(
                    45,
                    4,
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

                $this->SetFont('DejaVuSerif', $format, 7);
                $this->Cell(4, 4);
                $this->MultiCell(
                    180,
                    4,
                    Settings::getString('core.documents.contracts.contract.texts.reverse_charge_clause') . PHP_EOL,
                    align: 'J',
                );
            } else {
                $this->Cell(45, 4, Number::currency($totalCost->toFloat()), align: 'C');
                $this->Ln();
            }

            $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X, lnAfter: 1.0);

            $this->SetFont('DejaVuSerif', $format, 8);
            $this->Cell(90, 4, Settings::getString('core.documents.common.labels.provider_bank'), align: 'C');
            $this->Cell(45, 4, Settings::getString('core.documents.common.labels.provider_account'), align: 'C');
            $this->Cell(45, 4, Settings::getString('core.documents.common.labels.variable_symbol'), align: 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', 'B' . $format, 8);
            $this->Cell(90, 4, Settings::getString('core.company.bank_name'), align: 'C');
            $this->Cell(45, 4, Settings::getString('core.company.bank_account_number'), align: 'C');
            $this->Cell(45, 4, $contract->customer->number . ' *', align: 'C');
            $this->Ln();

            $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X, lnAfter: 1.0);

            $this->SetFont('DejaVuSerif', $format, 7);
            $this->Cell(4, 4);
            $this->Cell(180, 4, Settings::getString('core.documents.contracts.contract.texts.standing_order_note'));
            $this->Ln();

            unset($format);
        }

        if ($type === ContractPrintType::ContractAmendment) {
            $this->SetFont('DejaVuSerif', '', 8);
            $this->Ln();
            $this->Write(4, Settings::getString('core.documents.contracts.contract.texts.amendment_final_clause'));
            $this->Ln();
            $this->Ln();
            $this->Write(4, Settings::getString('core.documents.contracts.contract.texts.amendment_final_statement'));
            $this->Ln();
        }

        if ($type === ContractPrintType::ContractNew || $type === ContractPrintType::ContractNewX) {
            // CROSS
            $this->drawCross();

            // add a page
            $this->AddPage();

            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Ln();
            $this->Ln();
            $this->Write(4, Settings::getString('core.documents.contracts.contract.texts.new_equipment_intro'));
            $this->Ln();

            $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);

            if (count($contract->borrowed_equipments) > 0) {
                // intro text
                $this->SetFont('DejaVuSerif', '', 8);
                if ($type === ContractPrintType::ContractNew) {
                    $this->Write(
                        4,
                        Settings::getString('core.documents.contracts.contract.texts.borrowed_equipment_intro_new'),
                    );
                } else {
                    $this->Write(
                        4,
                        strtr(
                            Settings::getString('core.documents.contracts.contract.texts.borrowed_equipment_intro_old'),
                            [
                                '{old_conclusion_date}' => $contract_version_to_be_replaced->conclusion_date,
                            ],
                        ),
                    );
                }
                $this->Ln(5);

                // table header
                $this->SetFont('DejaVuSerif', 'B', 8);
                $this->Cell(4, 5);
                $this->Cell(
                    130,
                    5,
                    Settings::getString('core.documents.contracts.contract.tables.borrowed_equipments.device'),
                    1,
                );
                $this->Cell(
                    30,
                    5,
                    Settings::getString('core.documents.contracts.contract.tables.borrowed_equipments.value'),
                    border: 1,
                    align: 'R',
                );
                $this->Ln();

                // table rows
                $this->SetFont('DejaVuSerif', '', 8);
                foreach ($contract->borrowed_equipments as $borrowed_equipment) {
                    $this->Cell(4, 5);
                    $this->Cell(130, 5, $borrowed_equipment->equipment_type->name, 1);
                    $this->Cell(
                        30,
                        5,
                        Number::currency($borrowed_equipment->equipment_type->price?->toFloat() ?? ''),
                        border: 1,
                        align: 'R',
                    );
                    $this->Ln();
                }
                $this->Ln();

                if ($type === ContractPrintType::ContractNewX) {
                    $this->MultiCell(
                        180,
                        4,
                        Settings::getString('core.documents.contracts.contract.texts.borrowed_equipment_continue')
                             . PHP_EOL,
                        align: 'J',
                    );
                    $this->Ln(3);
                }

                $this->MultiCell(
                    180,
                    4,
                    Settings::getString('core.documents.contracts.contract.texts.borrowed_equipment_return')
                         . PHP_EOL,
                    align: 'J',
                );
                $this->Ln(3);

                $this->MultiCell(
                    180,
                    4,
                    Settings::getString('core.documents.contracts.contract.texts.borrowed_equipment_installation_costs')
                         . PHP_EOL,
                    align: 'J',
                );
                $this->Ln(3);

                // activation fee with installation
                $this->printActivationFee($contract, $contract_version, $type, true);
            } else {
                $this->SetFont('DejaVuSerif', '', 8);
                $this->MultiCell(
                    180,
                    4,
                    Settings::getString('core.documents.contracts.contract.texts.user_equipment_installation_costs')
                        . PHP_EOL,
                    align: 'J',
                );
                $this->Ln(3);

                // activation fee without installation
                $this->printActivationFee($contract, $contract_version, $type, false);
            }

            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(4, Settings::getString('core.documents.contracts.contract.sections.final_statements'));
            $this->Ln();

            $this->drawSeparator(lnBefore: 0.4, lnAfter: 1.0);
            $this->SetFont('DejaVuSerif', '', 8);
            $this->setListIndentWidth(4);
            $this->writeHTML(
                Settings::getString('core.documents.contracts.contract.texts.final_statements_html'),
                true,
                false,
                false,
                true,
                '',
            );
            $this->Ln(3);
            $this->MultiCell(
                180,
                4,
                Settings::getString('core.documents.contracts.contract.texts.final_prices') . PHP_EOL,
                align: 'J',
            );
            $this->Ln(3);
            $this->MultiCell(
                180,
                4,
                strtr(Settings::getString('core.documents.contracts.contract.texts.final_copies'), [
                    '{contract_number}' => $contract->number,
                ]) . PHP_EOL,
                align: 'J',
            );
            $this->Ln(3);
        }

        // Signature section
        $this->printSignatureSection('double', $signed);

        $this->Close();
    }
}
