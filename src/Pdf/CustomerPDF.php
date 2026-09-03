<?php
declare(strict_types=1);

namespace App\Pdf;

use App\Model\Enum\CustomerPrintType;
use App\Service\CustomerPrint\CustomerPrintData;
use InvalidArgumentException;
use Settings\Utility\Settings;

class CustomerPDF extends AppPDF
{
    /**
     * Generate PDF document - GDPR agreement
     *
     * @param \App\Service\CustomerPrint\CustomerPrintData $data Customer print data object
     * @return void
     */
    public function generateGDPRAgreement(CustomerPrintData $data): void
    {
        // Load data from print data object
        $type = $data->type;
        $customer = $data->customer;

        if ($customer->billing_address === null) {
            throw new InvalidArgumentException('Customer billing address is required to generate GDPR agreement.');
        }

        $this->printDocumentHeader($this->gdprText('title'), $this->gdprText('subtitle'));

        // What this agreement is, which customer it belongs to, and for how long it holds
        $this->printLabelledRow(
            [
                [$this->label('new_or_change'), $this->label(match ($type) {
                    CustomerPrintType::GdprNew => 'new',
                    CustomerPrintType::GdprChange => 'change',
                })],
                [$this->label('agreement_number'), $customer->number],
                [$this->label('agreement_duration'), $this->label('duration_indefinite')],
            ],
            62.0,
        );

        // Controller section
        $this->SetFont(self::FONT_FAMILY, 'B', self::HEADING_FONT_SIZE);
        $this->Cell(self::PAGE_WIDTH, 2, $this->label('between'), align: 'C');
        $this->Ln();

        $this->printParties($this->label('controller'), $customer);

        $this->Ln();

        // Separator line
        $this->drawSeparator(lnAfter: 0.5);

        // Customer personal and business data
        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->printTable(
            [
                $this->label('personal_data'),
                $this->label('business_data'),
            ],
            [
                [
                    [
                        'label' => $this->label('name'),
                        'value' => $customer->billing_address->full_name ?? '',
                    ],
                    [
                        'label' => $this->label('company'),
                        'value' => $this->strOrX($customer->billing_address?->company),
                    ],
                ],
                [
                    [
                        'label' => $this->label('birth_date'),
                        'value' => (string)$customer->date_of_birth,
                    ],
                    [
                        'label' => $this->label('identity_number'),
                        'value' => $this->strOrX($customer->identity_number),
                    ],
                ],
                [
                    [
                        'label' => $this->label('identity_card_number'),
                        'value' => (string)$customer->identity_card_number,
                    ],
                    [
                        'label' => $this->label('vat_number'),
                        'value' => $this->strOrX($customer->vat_number),
                    ],
                ],
                [
                    ['label' => $this->label('phone'), 'value' => $customer->phone],
                ],
                [
                    ['label' => $this->label('email'), 'value' => $customer->email],
                ],
            ],
        );

        // Addresses loop
        foreach ($customer->addresses as $address) {
            $this->printAddressBlock(
                $address->type->label(),
                $address->full_address ?? null,
            );
        }
        $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X);
        $this->Ln();

        // Declaration text
        $this->SetFont(self::FONT_FAMILY, '', self::NOTE_FONT_SIZE);
        $this->Write(3, $this->gdprText('declaration_text'), ln: true);

        // Checkboxes
        $this->SetFont(self::FONT_FAMILY, 'B', self::BODY_FONT_SIZE);
        $this->Write(3, $this->gdprText('checkboxes.billing'), ln: true);
        $this->Write(3, $this->gdprText('checkboxes.outages'), ln: true);
        $this->Write(3, $this->gdprText('checkboxes.marketing'), ln: true);
        $this->Ln();
        $this->Write(3, $this->gdprText('checkboxes.note'), ln: true);

        // Signature section
        $this->printSignatureSection('single-right', false);
    }

    /**
     * Reads one of this document's own texts.
     *
     * @param string $key Key under the GDPR documents block
     * @return string
     */
    private function gdprText(string $key): string
    {
        return Settings::getString('core.documents.gdpr.' . $key);
    }
}
