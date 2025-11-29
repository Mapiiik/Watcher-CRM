<?php
declare(strict_types=1);

namespace App;

use App\Model\Entity\Billing;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersion;
use App\Model\Enum\IpAddressTypeOfUse;
use App\Utility\Settings;
use Cake\I18n\Date;
use Cake\I18n\Number;
use Override;
use PhpCollective\DecimalObject\Decimal;
use stdClass;
use TCPDF;

//set image path for TCPDF
define('K_PATH_IMAGES', dirname(__DIR__) . DS . 'webroot' . DS . 'legacy' . DS . 'images' . DS);

class ContractPDF extends TCPDF
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function cell(
        mixed $w,
        mixed $h = 0,
        mixed $txt = '',
        mixed $border = 0,
        mixed $ln = 0,
        mixed $align = '',
        mixed $fill = false,
        mixed $link = '',
        mixed $stretch = 0,
        mixed $ignore_min_height = false,
        mixed $calign = 'T',
        mixed $valign = '',
    ): void {
        $valign = $valign == '' ? ($border == 0 ? 'T' : 'M') : $valign;
        parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link, $stretch, $ignore_min_height, $calign, $valign);
    }

    /**
     * getter for contract duration text - short
     *
     * @param int|null $duration Duration in months
     * @return string
     */
    private function contractDurationBefore(?int $duration): string
    {
        if ($duration <= 0) {
            die('Wrong duration');
        } else {
            if ($duration < 2) {
                return $duration . ' měsíce';
            }

            return $duration . ' měsíců';
        }
    }

    /**
     * prints billing table
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
        $this->Cell(140, 4, 'služba:');
        $this->Cell(35, 4, 'cena / měsíc:', align: 'R');
        $this->Ln();

        $totalCost = Decimal::create(0, 2);

        foreach ($billings as $billing) {
            $this->SetFont('DejaVuSerif', 'B' . $format, 8);
            $this->Cell(4, 4);
            $this->Cell(
                140,
                4,
                $billing->name
                . ($billing->billing_from > $contract_version->valid_from ? ' od ' . $billing->billing_from->__toString() : '')
                . ($billing->billing_until ? ' do ' . $billing->billing_until->__toString() : ''),
                align: 'L',
                stretch: 1,
            );
            $this->Cell(35, 4, Number::currency($billing->sum->toFloat()), align: 'R');
            $this->Ln();

            if ($billing->percentage_discount_sum->isPositive()) {
                $this->SetFont('DejaVuSerif', '' . $format, 8);
                $this->Cell(4, 4);
                $this->Cell(140, 4, ' - sleva ve výši ' . (string)$billing->percentage_discount . ' % z ceny této služby');
                $this->Cell(35, 4, Number::currency($billing->percentage_discount_sum->negate()->toFloat()), align: 'R');
                $this->Ln();
            }
            if ($billing->fixed_discount_sum->isPositive()) {
                $this->SetFont('DejaVuSerif', '' . $format, 8);
                $this->Cell(4, 4);
                $this->Cell(140, 4, ' - sleva v pevné výši z ceny této služby');
                $this->Cell(35, 4, Number::currency($billing->fixed_discount_sum->negate()->toFloat()), align: 'R');
                $this->Ln();
            }

            /** @psalm-suppress ImplicitToStringCast */
            $totalCost = $totalCost->add($billing->total_price);
        }

        return $totalCost;
    }

    /**
     * getter for contract duration text - long
     *
     * @param int|null $duration Duration in months
     * @return string
     */
    private function contractDuration(?int $duration): string
    {
        if ($duration <= 0) {
            return 'na dobu neurčitou';
        } else {
            if ($duration < 2) {
                return 'na dobu neurčitou s minimální dobou plnění v trvání ' . $duration . ' měsíce';
            }

            return 'na dobu neurčitou s minimální dobou plnění v trvání ' . $duration . ' měsíců';
        }
    }

    /**
     * generate PDF document - handover protocol
     *
     * @param \App\Model\Entity\Contract $contract Contract with all related data
     * @param \App\Model\Entity\ContractVersion $contract_version Contract version for dates
     * @param string $type Type of requested document
     * @param bool $signed Create signed document?
     * @param \stdClass|null $technical_details Technical details about service
     * @return void
     */
    public function generateHandoverProtocol(
        Contract $contract,
        ContractVersion $contract_version,
        string $type = 'handover-protocol-installation',
        bool $signed = false,
        ?stdClass $technical_details = null,
    ): void {
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);

        $this->AddPage();

        $this->Image(K_PATH_IMAGES . 'logo-contract.png', 10, 5, 28);

        // Title + subtitle
        $this->SetFont('DejaVuSerif', 'B', 18);
        $this->Cell(187, 6, Settings::getString('core.documents.contracts.handover.title'), align: 'C');
        $this->Ln();

        $this->SetFont('DejaVuSerif', 'B', 12);
        if ($type === 'handover-protocol-installation') {
            $this->Cell(187, 2, Settings::getString('core.documents.contracts.handover.subtitle_installation'), align: 'C');
        } else {
            $this->Cell(187, 2, Settings::getString('core.documents.contracts.handover.subtitle_uninstallation'), align: 'C');
        }
        $this->Ln(3);

        // Separator line
        $this->Ln(4);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
        $this->Ln(0.5);

        // Contract number + date
        $this->SetFont('DejaVuSerif', '', 8);
        if ($type === 'handover-protocol-installation') {
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
            $this->Cell(90, 4, $contract_version['number_of_the_contract_to_be_terminated'], align: 'C');
            $this->Cell(90, 4, (string)$contract_version->valid_until, align: 'C');
        }
        $this->Ln();

        $this->Line($this->GetX() + 4.0, $this->GetY(), $this->GetX() + 187.0, $this->GetY());
        $this->Ln(3);

        // BETWEEN
        $this->SetFont('DejaVuSerif', 'B', 9);
        $this->Cell(187, 2, Settings::getString('core.documents.common.labels.between'), align: 'C');
        $this->Ln();

        // Provider section
        $this->SetFont('DejaVuSerif', 'B', 9);
        $this->Cell(45, 4, Settings::getString('core.documents.common.labels.provider'));
        $this->Ln();

        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());

        // Company details (from settings)
        $this->Ln(1);
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(30, 4);
        $this->Cell(40, 4, Settings::getString('core.company.name'));
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4);
        $this->Cell(40, 4);
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.phone'));
        $this->Cell(40, 4, Settings::getString('core.company.phone'));
        $this->Ln();

        $this->Cell(30, 4);
        $this->Cell(40, 4, Settings::getString('core.documents.contracts.provider_address_line1'));
        $this->Cell(20, 4);
        $this->Cell(10, 4, Settings::getString('core.documents.common.labels.identity_number'));
        $this->Cell(40, 4, Settings::getString('core.company.identity_number'));
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.mobile'));
        $this->Cell(40, 4, Settings::getString('core.company.mobile'));
        $this->Ln();

        $this->Cell(30, 4);
        $this->Cell(40, 4, Settings::getString('core.documents.contracts.provider_address_line2'));
        $this->Cell(20, 4);
        $this->Cell(10, 4, Settings::getString('core.documents.common.labels.vat_number'));
        $this->Cell(40, 4, Settings::getString('core.company.vat_number'));
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.email'));
        $this->Cell(40, 4, Settings::getString('core.company.email'));
        $this->Ln();

        $this->Ln(3);
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4);
        $this->MultiCell(157, 4, Settings::getString('core.documents.contracts.provider_executive'), align: 'L');
        $this->Cell(30, 4);
        $this->MultiCell(157, 4, Settings::getString('core.documents.contracts.provider_registry'), align: 'L');

        $this->Line($this->GetX() + 4.0, $this->GetY(), $this->GetX() + 187.0, $this->GetY());
        $this->Ln(3);

        // User section
        $this->SetFont('DejaVuSerif', 'B', 9);
        $this->Cell(187, 4, Settings::getString('core.documents.common.labels.and'), align: 'C');
        $this->Ln();
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.user'));

        $this->SetFont('DejaVuSerif', '', 8);
        if (is_null($contract->customer->ic)) {
            $this->Cell(60, 4, Settings::getString('core.documents.gdpr.user_types.non_business'));
        } elseif (is_null($contract->billing_address->company)) {
            $this->Cell(60, 4, Settings::getString('core.documents.gdpr.user_types.business'));
        } else {
            $this->Cell(60, 4, Settings::getString('core.documents.gdpr.user_types.legal'));
        }
        $this->Ln();

        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
        $this->Ln(0.5);

        // Billing address section
        $addressStartY = $this->GetY();

        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(60, 4, __d('documents', 'Billing Address') . ':');
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.company'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->MultiCell(60, 4, $contract->billing_address->company ?? '', align: 'L');

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, is_null($contract->billing_address->company)
            ? Settings::getString('core.documents.common.labels.name')
            : Settings::getString('core.documents.common.labels.represented'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->MultiCell(60, 4, $contract->billing_address->full_name, align: 'L');

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.street'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->MultiCell(60, 4, $contract->billing_address->street_and_number, align: 'L');

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.zip_city'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->MultiCell(60, 4, $contract->billing_address->zip_and_city, align: 'L');

        // NEXT COLUMN
        $addressStopY = $this->GetY();
        $this->SetY($addressStartY);
        $this->Ln();

        // Specifiers (birth date, IC, ID card, DIC)
        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.birth_date'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(30, 4, (string)$contract->customer->date_of_birth);
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(10, 4, Settings::getString('core.documents.common.labels.identity_number'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(60, 4, $contract->customer->ic ?? 'X');
        $this->Ln();

        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.identity_card_number'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(30, 4, $contract->customer->identity_card_number);
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(10, 4, Settings::getString('core.documents.common.labels.vat_number'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(60, 4, $contract->customer->dic ?? 'X');
        $this->Ln();

        // Contact info
        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.phone'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->MultiCell(70, 4, $contract->customer->phone, align: 'L');

        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.email'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->MultiCell(70, 4, $contract->customer->email, align: 'L');

        // GO BACK TO END
        $this->SetY(max($this->GetY(), $addressStopY));

        // Installation / Delivery / Permanent addresses
        if ($contract->__isset('installation_address')) {
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(30, 4, __d('documents', 'Installation Address') . ': ');
            $this->Ln();
            $this->Cell(30, 4);
            $this->MultiCell(160, 4, $contract->installation_address->full_address, align: 'L');
        }
        if ($contract->__isset('delivery_address')) {
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(30, 4, __d('documents', 'Delivery Address') . ': ');
            $this->Ln();
            $this->Cell(30, 4);
            $this->MultiCell(160, 4, $contract->delivery_address->full_address, align: 'L');
        }
        if ($contract->__isset('permanent_address')) {
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(30, 4, __d('documents', 'Permanent Address') . ': ');
            $this->Ln();
            $this->Cell(30, 4);
            $this->MultiCell(160, 4, $contract->permanent_address->full_address, align: 'L');
        }

        $this->Line($this->GetX() + 4.0, $this->GetY(), $this->GetX() + 187.0, $this->GetY());
        $this->Ln(4);

        // BEGIN INSTALLATION
        if ($type == 'handover-protocol-installation') :
            // ACCESS INFORMATION
            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(4, Settings::getString('core.documents.contracts.handover.sections.access_info'));
            $this->Ln();

            $this->Ln(0.4);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
            $this->Ln(1);

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
            //$this->Cell(60, 5, $technical_details->access_point ?? '-', border: 1, align: 'C');
            $this->Cell(90, 5, $technical_details->radius_username ?? '-', border: 1, align: 'C');
            $this->Cell(90, 5, $technical_details->radius_password ?? '-', border: 1, align: 'C');
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
                $this->MultiCell(180, 4, implode(', ', array_column($contract->ip_networks, 'ip_network')), border: 1, align: 'L');

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
            $this->Cell(60, 5, Settings::getString('core.documents.contracts.handover.defaults.ip_network'), border: 1, align: 'C');
            $this->Cell(60, 5, Settings::getString('core.documents.contracts.handover.defaults.ip_gateway'), border: 1, align: 'C');
            $this->Cell(60, 5, Settings::getString('core.documents.contracts.handover.defaults.dns_servers'), border: 1, align: 'C');
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
                '<strong>Přístup do Uživatelského portálu je možné zřídit na Webu Poskytovatele:</strong><br>' . PHP_EOL
                . '<u>https://netair.cz/internet/uzivatelsky-portal</u>' . PHP_EOL,
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

                $this->Ln(0.4);
                $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
                $this->Ln(1);

                $this->SetFont('DejaVuSerif', '', 8);
                $this->Write(4, Settings::getString('core.documents.contracts.handover.texts.borrowed_equipment_intro'));
                $this->Ln(5);

                $this->SetFont('DejaVuSerif', 'B', 8);
                $this->Cell(4, 5);
                $this->Cell(130, 5, Settings::getString('core.documents.contracts.handover.tables.borrowed_equipments.device'), 1);
                $this->Cell(25, 5, Settings::getString('core.documents.contracts.handover.tables.borrowed_equipments.serial'), border: 1, align: 'C');
                $this->Cell(25, 5, Settings::getString('core.documents.contracts.handover.tables.borrowed_equipments.value'), border: 1, align: 'R');
                $this->Ln();

                $this->SetFont('DejaVuSerif', '', 8);
                foreach ($contract->borrowed_equipments as $borrowed_equipment) {
                    $this->Cell(4, 5);
                    $this->Cell(130, 5, $borrowed_equipment->equipment_type->name, 1);
                    $this->Cell(25, 5, $borrowed_equipment->serial_number, border: 1, align: 'C');
                    $this->Cell(25, 5, Number::currency($borrowed_equipment->equipment_type->price->toFloat()), border: 1, align: 'R');
                    $this->Ln();
                }

                $this->Ln(2);
                $this->SetFont('DejaVuSerif', '', 8);
                $this->MultiCell(180, 4, Settings::getString('core.documents.contracts.handover.texts.borrowed_equipment_return') . PHP_EOL, align: 'J');
                $this->Ln(2);
            }

            // CROSS
            $this->Ln(5);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY()); // --
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, 285.0); // \
            $this->Line($this->GetX(), 285.0, $this->GetX() + 187.0, $this->GetY()); // /
            $this->Line($this->GetX(), $this->GetY(), $this->GetX(), 285.0); // |
            $this->Line($this->GetX() + 187.0, $this->GetY(), $this->GetX() + 187.0, 285.0); // |
            $this->Line($this->GetX(), 285.0, $this->GetX() + 187.0, 285.0); // --

            // add a page
            $this->AddPage();

            // SOLD EQUIPMENTS
            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(4, Settings::getString('core.documents.contracts.handover.sections.activation_fee'));
            $this->Ln();

            $this->Ln(0.4);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', '', 8);
            if (count($contract->borrowed_equipments) > 0) {
                $this->MultiCell(
                    180,
                    4,
                    Settings::getString('core.documents.contracts.handover.texts.activation_fee_intro_with_equipment') . PHP_EOL,
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
                        $sold_equipment->equipment_type->price->subtract($sold_equipment->equipment_type->price_with_obligation),
                    );

                    $sold_equipment_price = $sold_equipment->equipment_type->price_with_obligation;
                } else {
                    $sold_equipment_price = $sold_equipment->equipment_type->price;
                }

                /** @psalm-suppress ImplicitToStringCast */
                $sold_equipments_value = $sold_equipments_value->add(
                    $sold_equipment->equipment_type->price,
                );

                /** @psalm-suppress ImplicitToStringCast */
                $subtotal = $subtotal->add($sold_equipment_price);
                $this->Cell(4, 5);
                $this->Cell(130, 5, $sold_equipment->equipment_type->name, 1);
                $this->Cell(25, 5, $sold_equipment->serial_number, border: 1, align: 'C');
                $this->Cell(25, 5, Number::currency($sold_equipment_price->toFloat()), border: 1, align: 'R');
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

            // CASH PAYMENT
            /*
            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(4, 'Úhrada v hotovosti');
            $this->Ln();

            $this->Ln(0.4);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', '', 8);
            $this->Ln(4);
            $this->MultiCell(180, 4, 'Placeno hotově: ____________________,- Kč, podpis příjemce: ____________________' . PHP_EOL, align: 'J');
            $this->Ln(6);
            */

            // CONNECTION POINT STATE
            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(4, Settings::getString('core.documents.contracts.handover.sections.connection_point_state'));
            $this->Ln();

            $this->Ln(0.4);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
            $this->Ln(1);

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

            $this->Ln(0.4);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
            $this->Ln(1);

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

                $this->Ln(0.4);
                $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
                $this->Ln(1);

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
                            '{discounted_price}' => Number::currency($sold_equipments_value->subtract($sold_equipments_discount)->toFloat()),
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

            $this->Ln(0.4);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
            $this->Ln(1);

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
        if ($type == 'handover-protocol-uninstallation') :
            // BORROWED EQUIPMENTS
            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(4, Settings::getString('core.documents.contracts.handover.sections.uninstallation_borrowed_equipment'));
            $this->Ln();

            $this->Ln(0.4);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', '', 8);
            $this->Write(4, Settings::getString('core.documents.contracts.handover.texts.uninstallation_borrowed_equipment_intro'));
            $this->Ln(5);

            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(4, 5);
            $this->Cell(130, 5, Settings::getString('core.documents.contracts.handover.tables.borrowed_equipments.device'), 1);
            $this->Cell(25, 5, Settings::getString('core.documents.contracts.handover.tables.borrowed_equipments.serial'), border: 1, align: 'C');
            $this->Cell(25, 5, Settings::getString('core.documents.contracts.handover.tables.borrowed_equipments.value'), border: 1, align: 'R');
            $this->Ln();

            $this->SetFont('DejaVuSerif', '', 8);
            foreach ($contract->borrowed_equipments as $borrowed_equipment) {
                $this->Cell(4, 5);
                $this->Cell(130, 5, $borrowed_equipment->equipment_type->name, 1);
                $this->Cell(25, 5, $borrowed_equipment->serial_number, border: 1, align: 'C');
                $this->Cell(25, 5, Number::currency($borrowed_equipment->equipment_type->price->toFloat()), border: 1, align: 'R');
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
            $this->Write(4, Settings::getString('core.documents.contracts.handover.texts.uninstallation_equipment_state'));
            $this->Ln(5);

            $this->SetFont('DejaVuSerif', '', 8);
            $this->MultiCell(
                180,
                4,
                Settings::getString('core.documents.contracts.handover.texts.uninstallation_equipment_checks') . PHP_EOL,
                align: 'J',
            );

            // CROSS
            $this->Ln(5);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, 285.0);
            $this->Line($this->GetX(), 285.0, $this->GetX() + 187.0, $this->GetY());
            $this->Line($this->GetX(), $this->GetY(), $this->GetX(), 285.0);
            $this->Line($this->GetX() + 187.0, $this->GetY(), $this->GetX() + 187.0, 285.0);
            $this->Line($this->GetX(), 285.0, $this->GetX() + 187.0, 285.0);

            // add a page
            $this->AddPage();

            // CASH PAYMENT
            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(4, Settings::getString('core.documents.contracts.handover.sections.uninstallation_cash_payment'));
            $this->Ln();

            $this->Ln(0.4);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', '', 8);
            $this->Ln(4);
            $this->MultiCell(
                180,
                4,
                Settings::getString('core.documents.contracts.handover.texts.uninstallation_cash_payment_text') . PHP_EOL,
                align: 'J',
            );
            $this->Ln(6);

            // GENERAL STATEMENTS (shared section name, unique text)
            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(4, Settings::getString('core.documents.contracts.handover.sections.general_statements'));
            $this->Ln();

            $this->Ln(0.4);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', '', 8);
            $this->Ln(4);
            $this->MultiCell(
                180,
                4,
                Settings::getString('core.documents.contracts.handover.texts.uninstallation_general_statements_text') . PHP_EOL,
                align: 'J',
            );
            $this->Ln(6);

            // FINAL STATEMENTS (shared section name, unique text)
            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(4, Settings::getString('core.documents.contracts.handover.sections.final_statements'));
            $this->Ln();

            $this->Ln(0.4);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', '', 8);
            $this->MultiCell(
                180,
                4,
                strtr(
                    Settings::getString('core.documents.contracts.handover.texts.uninstallation_final_statements_text'),
                    ['{contract_number}' => $contract_version['number_of_the_contract_to_be_terminated']],
                ) . PHP_EOL,
                align: 'J',
            );
            $this->Ln(3);
        endif;
        // END UNINSTALLATION

        // SIGNS
        $this->SetFont('DejaVuSerif', '', 8);
        if ($this->GetY() > 240) {
            $this->AddPage();
        }
        $this->Ln(10);

        if ($signed) {
            $this->Cell(
                90,
                4,
                Settings::getString('core.documents.common.signatures.date') . ' ' . Date::now()->__toString(),
                align: 'C',
            );
        } else {
            $this->Cell(
                90,
                4,
                Settings::getString('core.documents.common.signatures.date') . ' ' . Settings::getString('core.documents.common.signatures.date_line'),
                align: 'C',
            );
        }

        $this->Cell(
            90,
            4,
            Settings::getString('core.documents.common.signatures.date') . ' ' . Settings::getString('core.documents.common.signatures.date_line'),
            align: 'C',
        );

        $this->Ln(20);

        $this->Cell(90, 4, Settings::getString('core.documents.common.signatures.sign_line'), align: 'C');
        $this->Cell(90, 4, Settings::getString('core.documents.common.signatures.sign_line'), align: 'C');
        $this->Ln();

        $this->Cell(90, 4, Settings::getString('core.documents.common.signatures.provider'), align: 'C');
        $this->Cell(90, 4, Settings::getString('core.documents.common.signatures.user'), align: 'C');
        $this->Ln();

        if ($signed) {
            $this->Image(K_PATH_IMAGES . 'signature.png', 38.0, $this->GetY() - 19.0, 35.0);
        }

        $this->Close();
    }

    /**
     * generate PDF document - contract
     *
     * @param \App\Model\Entity\Contract $contract Contract with all related data
     * @param \App\Model\Entity\ContractVersion $contract_version Contract version for dates
     * @param string $type Type of requested document
     * @param bool $signed Create signed document?
     * @return void
     */
    public function generateContract(Contract $contract, ContractVersion $contract_version, string $type = 'contract-new', bool $signed = false): void
    {
        $this->setPrintHeader(false);
        $this->setPrintFooter(false);

        $this->AddPage();

        $this->Image(K_PATH_IMAGES . 'logo-contract.png', 10, 5, 28);

        $this->SetFont('DejaVuSerif', 'BI', 8);

        switch ($type) {
            case 'contract-new':
            case 'contract-new-x':
                $this->SetFont('DejaVuSerif', 'B', 18);
                $this->Cell(187, 6, Settings::getString('core.documents.contracts.contract.title_new'), align: 'C');
                $this->Ln();

                $this->SetFont('DejaVuSerif', 'B', 12);
                $this->Cell(187, 2, Settings::getString('core.documents.contracts.contract.subtitle_new'), align: 'C');
                $this->Ln(3);
                break;

            case 'contract-amendment':
                $this->SetFont('DejaVuSerif', 'B', 18);
                $this->Cell(187, 6, Settings::getString('core.documents.contracts.contract.title_amendment'), align: 'C');
                $this->Ln();

                $this->SetFont('DejaVuSerif', 'B', 12);
                $this->Cell(187, 2, Settings::getString('core.documents.contracts.contract.subtitle_amendment'), align: 'C');
                $this->Ln(3);
                break;

            case 'contract-termination':
                $this->SetFont('DejaVuSerif', 'B', 18);
                $this->Cell(187, 6, Settings::getString('core.documents.contracts.contract.title_termination'), align: 'C');
                $this->Ln();

                $this->SetFont('DejaVuSerif', 'B', 12);
                $this->Cell(187, 2, Settings::getString('core.documents.contracts.contract.subtitle_termination'), align: 'C');
                $this->Ln(3);
                break;
        }

        $this->Ln(4);
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
        $this->Ln(0.5);

        switch ($type) {
            case 'contract-new':
            case 'contract-new-x':
                $this->SetFont('DejaVuSerif', '', 8);
                $this->Cell(90, 4, Settings::getString('core.documents.common.labels.contract_number'), align: 'C');
                $this->Cell(90, 4, Settings::getString('core.documents.common.labels.start_date'), align: 'C');
                $this->Ln();
                $this->SetFont('DejaVuSerif', 'B', 8);
                $this->Cell(90, 4, $contract->number, align: 'C');
                $this->Cell(90, 4, (string)$contract_version->valid_from, align: 'C');
                $this->Ln();

                $this->Line($this->GetX() + 4.0, $this->GetY(), $this->GetX() + 187.0, $this->GetY());
                $this->Ln(3);

                $this->SetFont('DejaVuSerif', 'B', 9);
                $this->Cell(187, 2, Settings::getString('core.documents.common.labels.between_new'), align: 'C');
                $this->Ln();
                break;

            case 'contract-amendment':
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
                $this->Cell(45, 4, (string)$contract_version->valid_from, align: 'C');
                $this->Ln();

                $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
                $this->Ln(3);

                $this->SetFont('DejaVuSerif', 'B', 9);
                $this->Cell(187, 2, Settings::getString('core.documents.common.labels.between_amendment'), align: 'C');
                $this->Ln();
                break;

            case 'contract-termination':
                $this->SetFont('DejaVuSerif', '', 8);
                $this->Cell(60, 4, Settings::getString('core.documents.common.labels.contract_number'), align: 'C');
                $this->Cell(60, 4, Settings::getString('core.documents.common.labels.conclusion_date'), align: 'C');
                $this->Cell(60, 4, Settings::getString('core.documents.common.labels.end_date'), align: 'C');
                $this->Ln();
                $this->SetFont('DejaVuSerif', 'B', 8);
                $this->Cell(60, 4, $contract_version['number_of_the_contract_to_be_terminated'], align: 'C');
                $this->Cell(60, 4, (string)$contract_version->conclusion_date, align: 'C');
                $this->Cell(60, 4, (string)$contract_version->valid_until, align: 'C');
                $this->Ln();

                $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
                $this->Ln(3);

                $this->SetFont('DejaVuSerif', 'B', 9);
                $this->Cell(187, 2, Settings::getString('core.documents.common.labels.between_termination'), align: 'C');
                $this->Ln();
                break;
        }

        $this->SetFont('DejaVuSerif', 'B', 9);
        $this->Cell(45, 4, Settings::getString('core.documents.common.labels.provider'));
        $this->Ln();

        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());

        $this->Ln(1);
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(30, 4);
        $this->Cell(40, 4, Settings::getString('core.company.name'));
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4);
        $this->Cell(40, 4);
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.phone'));
        $this->Cell(40, 4, Settings::getString('core.company.phone'));
        $this->Ln();

        $this->Cell(30, 4);
        $this->Cell(40, 4, Settings::getString('core.documents.contracts.provider_address_line1'));
        $this->Cell(20, 4);
        $this->Cell(10, 4, Settings::getString('core.documents.common.labels.identity_number'));
        $this->Cell(40, 4, Settings::getString('core.company.identity_number'));
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.mobile'));
        $this->Cell(40, 4, Settings::getString('core.company.mobile'));
        $this->Ln();

        $this->Cell(30, 4);
        $this->Cell(40, 4, Settings::getString('core.documents.contracts.provider_address_line2'));
        $this->Cell(20, 4);
        $this->Cell(10, 4, Settings::getString('core.documents.common.labels.vat_number'));
        $this->Cell(40, 4, Settings::getString('core.company.vat_number'));
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.email'));
        $this->Cell(40, 4, Settings::getString('core.company.email'));
        $this->Ln();

        $this->Ln(3);
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4);
        $this->MultiCell(157, 4, Settings::getString('core.documents.common.labels.executive'), align: 'L');
        $this->Cell(30, 4);
        $this->MultiCell(157, 4, Settings::getString('core.documents.common.labels.company_registry'), align: 'L');

        $this->Line($this->GetX() + 4.0, $this->GetY(), $this->GetX() + 187.0, $this->GetY());
        $this->Ln(3);

        $this->SetFont('DejaVuSerif', 'B', 9);
        $this->Cell(187, 4, Settings::getString('core.documents.common.labels.and'), align: 'C');
        $this->Ln();
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.user'));

        $this->SetFont('DejaVuSerif', '', 8);
        if (is_null($contract->customer->ic)) {
            $this->Cell(60, 4, Settings::getString('core.documents.common.labels.customer_natural_nonbusiness'));
        } elseif (is_null($contract->billing_address->company)) {
            $this->Cell(60, 4, Settings::getString('core.documents.common.labels.customer_natural_business'));
        } else {
            $this->Cell(60, 4, Settings::getString('core.documents.common.labels.customer_legal'));
        }

        // Subscriber Verification Code
        if (!empty($contract->subscriber_verification_code)) {
            $this->Cell(60, 4, Settings::getString('core.documents.common.labels.subscriber_verification_code') . ':', align: 'R');
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(60, 4, $contract->subscriber_verification_code);
        }

        $this->Ln();

        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
        $this->Ln(0.5);

        $addressStartY = $this->GetY();

        // BILLING
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(60, 4, __d('documents', 'Billing Address') . ':');
        $this->Ln();

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.company'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->MultiCell(60, 4, $contract->billing_address->company ?? '', align: 'L');

        $this->SetFont('DejaVuSerif', '', 8);
        if (is_null($contract->billing_address->company)) {
            $this->Cell(30, 4, Settings::getString('core.documents.common.labels.name'), align: 'R');
        } else {
            $this->Cell(30, 4, Settings::getString('core.documents.common.labels.represented'), align: 'R');
        }
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->MultiCell(60, 4, $contract->billing_address->full_name, align: 'L');

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.street'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->MultiCell(60, 4, $contract->billing_address->street_and_number, align: 'L');

        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(30, 4, Settings::getString('core.documents.common.labels.zip_city'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->MultiCell(60, 4, $contract->billing_address->zip_and_city, align: 'L');

        // NEXT COLUMN
        $addressStopY = $this->GetY();
        $this->SetY($addressStartY);
        $this->Ln();

        // SPECIFIERS
        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.birth_date'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(30, 4, (string)$contract->customer->date_of_birth);
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(10, 4, Settings::getString('core.documents.common.labels.identity_number'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(60, 4, $contract->customer->ic ?? 'X');
        $this->Ln();

        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.identity_card_number'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(30, 4, $contract->customer->identity_card_number);
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(10, 4, Settings::getString('core.documents.common.labels.vat_number'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->Cell(60, 4, $contract->customer->dic ?? 'X');
        $this->Ln();

        // CONTACT
        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.phone'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->MultiCell(70, 4, $contract->customer->phone, align: 'L');

        $this->Cell(105);
        $this->SetFont('DejaVuSerif', '', 8);
        $this->Cell(15, 4, Settings::getString('core.documents.common.labels.email'), align: 'R');
        $this->SetFont('DejaVuSerif', 'B', 8);
        $this->MultiCell(70, 4, $contract->customer->email, align: 'L');

        // GO BACK TO END
        $this->SetY(max($this->GetY(), $addressStopY));

        // INSTALLATION ADDRESS
        if ($contract->__isset('installation_address')) {
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(30, 4, __d('documents', 'Installation Address') . ': ');
            $this->Ln();
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(30, 4);
            $this->MultiCell(160, 4, $contract->installation_address->full_address, align: 'L');
        }
        // DELIVERY ADDRESS
        if ($contract->__isset('delivery_address')) {
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(30, 4, __d('documents', 'Delivery Address') . ': ');
            $this->Ln();
            $this->Cell(30, 4);
            $this->MultiCell(160, 4, $contract->delivery_address->full_address, align: 'L');
        }
        // PERMANENT ADDRESS
        if ($contract->__isset('permanent_address')) {
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Cell(30, 4, __d('documents', 'Permanent Address') . ': ');
            $this->Ln();
            $this->Cell(30, 4);
            $this->MultiCell(160, 4, $contract->permanent_address->full_address, align: 'L');
        }

        $this->Line($this->GetX() + 4.0, $this->GetY(), $this->GetX() + 187.0, $this->GetY());

        $this->Ln(3);

        if ($type === 'contract-termination') {
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Write(
                4,
                strtr(Settings::getString('core.documents.contracts.contract.texts.termination_intro'), [
                    '{contract_number}' => $contract_version['number_of_the_contract_to_be_terminated'],
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

        if ($type === 'contract-new' || $type === 'contract-new-x') {
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

            if ($type === 'contract-new-x') {
                $this->Write(
                    4,
                    strtr(Settings::getString('core.documents.contracts.contract.texts.new_x_intro'), [
                        '{contract_number}' => $contract_version['number_of_the_contract_to_be_terminated'],
                        '{old_conclusion_date}' => $contract_version['old']->conclusion_date,
                        '{termination_date}' => $contract_version->valid_from->subDays(1)->__toString(),
                    ])
                );
                $this->Ln();
                $this->Ln();
            }
        }

        if ($type === 'contract-amendment') {
            $this->SetFont('DejaVuSerif', 'B', 8);
            $this->Write(
                4,
                strtr(Settings::getString('core.documents.contracts.contract.texts.amendment_intro'), [
                    '{valid_from}' => $contract_version->valid_from->__toString(),
                ])
            );
            $this->Ln();
            $this->Ln();
        }

        if ($type === 'contract-new' || $type === 'contract-new-x' || $type === 'contract-amendment') {
            if ($type === 'contract-amendment') {
                $format = 'I';
            } else {
                $format = '';
            }

            // sum of all items
            $totalCost = Decimal::create(0, 2);

            // billing of pricelist items
            if (count($contract['standard_billings']) > 0) {
                $this->SetFont('DejaVuSerif', 'B' . $format, 9);
                $this->Cell(187, 3, Settings::getString('core.documents.contracts.contract.sections.billing_pricelist'));
                $this->Ln();

                $this->Ln(0.4);
                $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
                $this->Ln(1);

                $totalCost = $totalCost->add($this->billingTable($contract['standard_billings'], $contract_version, $format));
                $this->Ln();
            }

            // billing of non-pricelist items
            if (count($contract['individual_billings']) > 0) {
                $this->SetFont('DejaVuSerif', 'B' . $format, 9);
                $this->Cell(187, 3, Settings::getString('core.documents.contracts.contract.sections.billing_individual'));
                $this->Ln();

                $this->Ln(0.4);
                $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
                $this->Ln(1);

                $totalCost = $totalCost->add($this->billingTable($contract['individual_billings'], $contract_version, $format));

                $this->SetFont('DejaVuSerif', $format, 7);
                $this->Cell(4, 4);
                $this->MultiCell(180, 4, Settings::getString('core.documents.contracts.contract.texts.individual_clause'), align: 'L');
                $this->Ln();
            }

            // future billing of pricelist items
            if (count($contract['future_standard_billings']) > 0) {
                $this->SetFont('DejaVuSerif', 'B' . $format, 9);
                $this->Cell(187, 3, Settings::getString('core.documents.contracts.contract.sections.billing_future_pricelist'));
                $this->Ln();

                $this->Ln(0.4);
                $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
                $this->Ln(1);

                $this->billingTable($contract['future_standard_billings'], $contract_version, $format);
                $this->Ln();
            }

            // future billing of non-pricelist items
            if (count($contract['future_individual_billings']) > 0) {
                $this->SetFont('DejaVuSerif', 'B' . $format, 9);
                $this->Cell(187, 3, Settings::getString('core.documents.contracts.contract.sections.billing_future_individual'));
                $this->Ln();

                $this->Ln(0.4);
                $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
                $this->Ln(1);

                $this->billingTable($contract['future_individual_billings'], $contract_version, $format);

                $this->SetFont('DejaVuSerif', $format, 7);
                $this->Cell(4, 4);
                $this->MultiCell(180, 4, Settings::getString('core.documents.contracts.contract.texts.individual_clause'), align: 'L');
                $this->Ln();
            }

            $this->SetFont('DejaVuSerif', 'B' . $format, 9);
            $this->Cell(187, 3, Settings::getString('core.documents.contracts.contract.sections.payment_info'));
            $this->Ln();

            $this->Ln(0.4);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', $format, 8);
            $this->Cell(45, 4, Settings::getString('core.documents.common.labels.payment_period'), align: 'C');
            $this->Cell(45, 4, Settings::getString('core.documents.common.labels.payment_method'), align: 'C');
            $this->Cell(45, 4, Settings::getString('core.documents.common.labels.first_payment_date'), align: 'C');
            $this->Cell(45, 4, Settings::getString('core.documents.common.labels.first_payment_total'), align: 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', 'B' . $format, 8);
            $this->Cell(45, 4, Settings::getString('core.documents.common.labels.monthly'), align: 'C');
            $this->Cell(45, 4, Settings::getString('core.documents.common.labels.bank_transfer'), align: 'C');
            $this->Cell(45, 4, 'do ' . $contract_version->valid_from->day(1)->addMonths(1)->addDays(9)->__toString(), align: 'C');

            // reverse charge
            if ($contract->customer->tax_rate->reverse_charge) {
                $this->Cell(
                    45,
                    4,
                    Number::currency(
                        Billing::calcVatBaseFromTotal($totalCost, $contract->customer->tax_rate->vat_rate)->toFloat(),
                    ) . ' *',
                    align: 'C',
                );
                $this->Ln();

                $this->Line($this->GetX() + 4.0, $this->GetY(), $this->GetX() + 187.0, $this->GetY());
                $this->Ln(1);

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

            $this->Line($this->GetX() + 4.0, $this->GetY(), $this->GetX() + 187.0, $this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', $format, 8);
            $this->Cell(90, 4, Settings::getString('core.documents.common.labels.provider_bank'), align: 'C');
            $this->Cell(45, 4, Settings::getString('core.documents.common.labels.provider_account'), align: 'C');
            $this->Cell(45, 4, Settings::getString('core.documents.common.labels.variable_symbol'), align: 'C');
            $this->Ln();

            $this->SetFont('DejaVuSerif', 'B' . $format, 8);
            $this->Cell(90, 4, Settings::getString('core.documents.common.labels.bank_name'), align: 'C');
            $this->Cell(45, 4, Settings::getString('core.documents.common.labels.bank_account_number'), align: 'C');
            $this->Cell(45, 4, $contract->customer->number . ' *', align: 'C');
            $this->Ln();

            $this->Line($this->GetX() + 4.0, $this->GetY(), $this->GetX() + 187.0, $this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', $format, 7);
            $this->Cell(4, 4);
            $this->Cell(180, 4, Settings::getString('core.documents.contracts.contract.texts.standing_order_note'));
            $this->Ln();

            unset($format);
        }

        if ($type === 'contract-amendment') {
            $this->SetFont('DejaVuSerif', '', 8);
            $this->Ln();
            $this->Write(4, Settings::getString('core.documents.contracts.contract.texts.amendment_final_clause'));
            $this->Ln();
            $this->Ln();
            $this->Write(4, Settings::getString('core.documents.contracts.contract.texts.amendment_final_statement'));
            $this->Ln();
        }

        if ($type === 'contract-new' || $type === 'contract-new-x') {
            // cross
            $this->Ln(5);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY()); // --
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, 285.0); // \
            $this->Line($this->GetX(), 285.0, $this->GetX() + 187.0, $this->GetY()); // /
            $this->Line($this->GetX(), $this->GetY(), $this->GetX(), 285.0); // |
            $this->Line($this->GetX() + 187.0, $this->GetY(), $this->GetX() + 187.0, 285.0); // |
            $this->Line($this->GetX(), 285.0, $this->GetX() + 187.0, 285.0); // --

            // add a page
            $this->AddPage();

            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Ln();
            $this->Ln();
            $this->Write(4, Settings::getString('core.documents.contracts.contract.texts.new_equipment_intro'));
            $this->Ln();

            $this->Ln(0.4);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
            $this->Ln(1);

            if (count($contract->borrowed_equipments) > 0) {
                $this->SetFont('DejaVuSerif', '', 8);
                if ($type === 'contract-new') {
                    $this->Write(4, Settings::getString('core.documents.contracts.contract.texts.borrowed_equipment_intro_new'));
                } else {
                    $this->Write(
                        4,
                        strtr(Settings::getString('core.documents.contracts.contract.texts.borrowed_equipment_intro_old'), [
                            '{old_conclusion_date}' => $contract_version['old']->conclusion_date,
                        ]),
                    );
                }

                $this->Ln(5);

                $this->SetFont('DejaVuSerif', 'B', 8);
                $this->Cell(4, 5);
                $this->Cell(130, 5, Settings::getString('core.documents.contracts.contract.tables.borrowed_equipments.device'), 1);
                $this->Cell(30, 5, Settings::getString('core.documents.contracts.contract.tables.borrowed_equipments.value'), border: 1, align: 'R');
                $this->Ln();

                $this->SetFont('DejaVuSerif', '', 8);
                foreach ($contract->borrowed_equipments as $borrowed_equipment) {
                    $this->Cell(4, 5);
                    $this->Cell(130, 5, $borrowed_equipment->equipment_type->name, 1);
                    $this->Cell(30, 5, Number::currency($borrowed_equipment->equipment_type->price->toFloat()), border: 1, align: 'R');
                    $this->Ln();
                }

                $this->Ln();

                if ($type === 'contract-new-x') {
                    $this->MultiCell(180, 4, Settings::getString('core.documents.contracts.contract.texts.borrowed_equipment_continue') . PHP_EOL, align: 'J');
                    $this->Ln(3);
                }

                $this->SetFont('DejaVuSerif', '', 8);
                $this->MultiCell(180, 4, Settings::getString('core.documents.contracts.contract.texts.borrowed_equipment_return') . PHP_EOL, align: 'J');
                $this->Ln(3);

                $this->MultiCell(180, 4, Settings::getString('core.documents.contracts.contract.texts.borrowed_equipment_installation_costs') . PHP_EOL, align: 'J');
                $this->Ln(3);

                if ($contract->activation_fee_sum->isPositive()) {
                    $this->SetFont('DejaVuSerif', 'B', 8);

                    if ($contract_version->minimum_duration <= 0) {
                        if ($type === 'contract-new') {
                            $this->MultiCell(
                                180,
                                4,
                                strtr(Settings::getString('core.documents.contracts.contract.texts.activation_fee_no_commitment'), [
                                    '{activation_fee}' => Number::currency($contract->activation_fee_sum->toFloat()),
                                    '{with_installation}' => ' a instalaci Poskytnutých zařízení',
                                ]) . PHP_EOL,
                                align: 'J',
                            );
                            $this->Ln(3);
                        }
                    } else {
                        if ($type === 'contract-new') {
                            $this->MultiCell(
                                180,
                                4,
                                strtr(Settings::getString('core.documents.contracts.contract.texts.activation_fee_with_commitment'), [
                                    '{activation_fee_obligation}' => Number::currency($contract->activation_fee_with_obligation_sum->toFloat()),
                                    '{with_installation}' => ' a instalaci Poskytnutých zařízení',
                                ]) . PHP_EOL,
                                align: 'J',
                            );
                            $this->Ln(3);
                        }
                        $this->MultiCell(
                            180,
                            4,
                            strtr(Settings::getString('core.documents.contracts.contract.texts.activation_fee_clause_equipment'), [
                                '{duration}' => $this->contractDurationBefore($contract_version->minimum_duration),
                                '{difference}' => Number::currency($contract->activation_fee_sum->subtract($contract->activation_fee_with_obligation_sum)->toFloat()),
                                '{full_fee}' => Number::currency($contract->activation_fee_sum->toFloat()),
                            ]) . PHP_EOL,
                            align: 'J',
                        );
                        $this->Ln(3);
                    }
                }
            } else {
                $this->SetFont('DejaVuSerif', '', 8);
                $this->MultiCell(180, 4, Settings::getString('core.documents.contracts.contract.texts.user_equipment_installation_costs') . PHP_EOL, align: 'J');
                $this->Ln(3);

                if ($contract->activation_fee_sum->isPositive()) {
                    $this->SetFont('DejaVuSerif', 'B', 8);

                    if ($contract_version->minimum_duration <= 0) {
                        if ($type === 'contract-new') {
                            $this->MultiCell(
                                180,
                                4,
                                strtr(Settings::getString('core.documents.contracts.contract.texts.activation_fee_no_commitment'), [
                                    '{activation_fee}' => Number::currency($contract->activation_fee_sum->toFloat()),
                                    '{with_installation}' => '',
                                ]) . PHP_EOL,
                                align: 'J',
                            );
                            $this->Ln(3);
                        }
                    } else {
                        if ($type === 'contract-new') {
                            $this->MultiCell(
                                180,
                                4,
                                strtr(Settings::getString('core.documents.contracts.contract.texts.activation_fee_with_commitment'), [
                                    '{activation_fee_obligation}' => Number::currency($contract->activation_fee_with_obligation_sum->toFloat()),
                                    '{with_installation}' => '',
                                ]) . PHP_EOL,
                                align: 'J',
                            );
                            $this->Ln(3);
                        }
                        $this->MultiCell(
                            180,
                            4,
                            strtr(Settings::getString('core.documents.contracts.contract.texts.activation_fee_clause_installation'), [
                                '{duration}' => $this->contractDurationBefore($contract_version->minimum_duration),
                                '{difference}' => Number::currency($contract->activation_fee_sum->subtract($contract->activation_fee_with_obligation_sum)->toFloat()),
                                '{full_fee}' => Number::currency($contract->activation_fee_sum->toFloat()),
                            ]) . PHP_EOL,
                            align: 'J',
                        );
                        $this->Ln(3);
                    }
                }
            }

            $this->SetFont('DejaVuSerif', 'B', 9);
            $this->Write(4, Settings::getString('core.documents.contracts.contract.sections.final_provisions'));
            $this->Ln();

            $this->Ln(0.4);
            $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 187.0, $this->GetY());
            $this->Ln(1);

            $this->SetFont('DejaVuSerif', '', 8);
            $this->setListIndentWidth(4);
            $this->writeHTML(
                Settings::getString('core.documents.contracts.contract.texts.final_documents_intro') . PHP_EOL
                . Settings::getString('core.documents.contracts.contract.texts.final_documents_list') . PHP_EOL
                . Settings::getString('core.documents.contracts.contract.texts.final_documents_confirm') . '<br>' . PHP_EOL
                . Settings::getString('core.documents.contracts.contract.texts.final_documents_web') . PHP_EOL
                . Settings::getString('core.documents.contracts.contract.texts.final_documents_web_list'),
                true,
                false,
                false,
                true,
                '',
            );
            $this->Ln(3);
            $this->MultiCell(180, 4, Settings::getString('core.documents.contracts.contract.texts.final_prices') . PHP_EOL, align: 'J');
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

        $this->SetFont('DejaVuSerif', '', 8);
        if ($this->GetY() > 240) {
            $this->AddPage();
        }
        $this->Ln();
        $this->Ln();

        if ($signed) {
            $this->Cell(
                90,
                4,
                Settings::getString('core.documents.common.signatures.date') . ' ' . Date::now()->__toString(),
                align: 'C',
            );
        } else {
            $this->Cell(
                90,
                4,
                Settings::getString('core.documents.common.signatures.date') . ' ' . Settings::getString('core.documents.common.signatures.date_line'),
                align: 'C',
            );
        }

        $this->Cell(
            90,
            4,
            Settings::getString('core.documents.common.signatures.date') . ' ' . Settings::getString('core.documents.common.signatures.date_line'),
            align: 'C',
        );

        $this->Ln(20);

        $this->Cell(90, 4, Settings::getString('core.documents.common.signatures.sign_line'), align: 'C');
        $this->Cell(90, 4, Settings::getString('core.documents.common.signatures.sign_line'), align: 'C');
        $this->Ln();
        $this->Cell(90, 4, Settings::getString('core.documents.common.signatures.provider'), align: 'C');
        $this->Cell(90, 4, Settings::getString('core.documents.common.signatures.user'), align: 'C');
        $this->Ln();

        if ($signed) {
            $this->Image(K_PATH_IMAGES . 'signature.png', 38.0, $this->GetY() - 19.0, 35.0);
        }

        $this->Close();
    }
}
