<?php
declare(strict_types=1);

namespace App\Pdf;

use App\Model\Entity\Billing;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersion;
use App\Model\Entity\Customer;
use App\Model\Enum\CustomerDocumentType;
use App\Pdf\Trait\BillingTableTrait;
use App\Pdf\Trait\ContractDurationTrait;
use App\Service\CustomerPrint\CustomerPrintData;
use App\Service\CustomerPrint\MonthlyInvoice;
use App\Service\CustomerPrint\MonthlyInvoices;
use Cake\I18n\Date;
use Cake\I18n\Number;
use InvalidArgumentException;
use PhpCollective\DecimalObject\Decimal;
use Settings\Utility\Settings;

class CustomerPDF extends AppPDF
{
    use BillingTableTrait;
    use ContractDurationTrait;

    /**
     * The list runs over as many pages as the customer has contracts, so a contract's block is
     * kept together rather than split under its heading.
     */
    protected const KEEPS_BLOCKS_WHOLE = true;

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
                    CustomerDocumentType::GdprNew => 'new',
                    CustomerDocumentType::GdprChange => 'change',
                    default => throw new InvalidArgumentException('Not a consent to the processing of personal data.'),
                })],
                [$this->label('agreement_number'), $customer->number],
                [$this->label('agreement_duration'), $this->label('duration_indefinite')],
            ],
            62.0,
        );

        // Controller section
        $this->SetFont(self::FONT_FAMILY, 'B', self::HEADING_FONT_SIZE);
        $this->printFullWidth($this->label('between'), 2);
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
        $this->Ln();
        $this->SetFont(self::FONT_FAMILY, 'B', self::BODY_FONT_SIZE);
        $this->Write(3, $this->gdprText('checkboxes.billing'), ln: true);
        $this->Write(3, $this->gdprText('checkboxes.outages'), ln: true);
        $this->Write(3, $this->gdprText('checkboxes.marketing'), ln: true);
        $this->Ln();
        $this->Write(3, $this->gdprText('checkboxes.note'), ln: true);

        // Signature section
        $this->printSignatureSection('single-right');
    }

    /**
     * Generate PDF document - the list of the user's contracts and the services provided
     *
     * What the customer has with us on the day it is drawn up: each contract with its services
     * and their monthly prices, and at the end how the whole of it is paid. Nothing is agreed in
     * it, so nobody signs it.
     *
     * @param \App\Service\CustomerPrint\CustomerPrintData $data Customer print data object
     * @return void
     */
    public function generateServicesOverview(CustomerPrintData $data): void
    {
        $customer = $data->customer;
        $day = Date::now();

        if ($customer->billing_address === null) {
            throw new InvalidArgumentException('Customer billing address is required to list the services.');
        }

        $this->printDocumentHeader($this->overviewText('title'), $this->overviewText('subtitle'));

        $this->printLabelledRow([
            [$this->label('customer_number'), (string)$customer->number],
            [$this->overviewText('labels.as_of'), (string)$day],
        ]);

        $this->SetFont(self::FONT_FAMILY, 'B', self::HEADING_FONT_SIZE);
        $this->printFullWidth($this->label('between'), 2);
        $this->Ln();

        $this->printParties($this->label('provider'), $customer);
        $this->Ln();
        $this->drawSeparator(lnAfter: 0.5);

        $this->printCustomerIdentification($customer);
        $this->Ln();

        $this->printParagraph(strtr($this->overviewText('texts.intro'), ['{date}' => (string)$day]));

        foreach ($customer->contracts as $contract) {
            $this->printContractServices($contract, $day);
        }

        $this->printMonthlyPayments($customer, $day);

        $this->Ln();
        $this->printParagraph($this->overviewText('texts.final_prices'), format: 'I');
    }

    /**
     * Who the list is about, in the few lines that identify them on an invoice.
     *
     * @param \App\Model\Entity\Customer $customer The customer
     * @return void
     */
    private function printCustomerIdentification(Customer $customer): void
    {
        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->printTable([], [
            [
                ['label' => $this->label('name'), 'value' => $customer->billing_address->full_name ?? ''],
                ['label' => $this->label('company'), 'value' => $this->strOrX($customer->billing_address?->company)],
            ],
            [
                ['label' => $this->label('email'), 'value' => (string)$customer->email],
                ['label' => $this->label('identity_number'), 'value' => $this->strOrX($customer->identity_number)],
            ],
            [
                ['label' => $this->label('phone'), 'value' => (string)$customer->phone],
                ['label' => $this->label('vat_number'), 'value' => $this->strOrX($customer->vat_number)],
            ],
        ]);

        // Named for what it is used for here rather than for its type: where the customer keeps
        // no billing address, invoices go to whichever address stands in for one.
        $this->printAddressBlock(
            __d('documents', 'Billing Address'),
            $customer->billing_address->full_address ?? null,
        );
        $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X);
    }

    /**
     * One contract: what it is, where, since when and for how long, and what it provides.
     *
     * Kept on one page where it fits, so a contract is never read half here and half overleaf.
     *
     * @param \App\Model\Entity\Contract $contract The contract, with its billings
     * @param \Cake\I18n\Date $day The day the list is drawn up on
     * @return void
     */
    private function printContractServices(Contract $contract, Date $day): void
    {
        $facts = $this->contractFacts($contract, $day);

        $current = [];
        $future = [];
        foreach ($contract->billings ?? [] as $billing) {
            if ($billing->billing_from > $day) {
                $future[] = $billing;
            } else {
                $current[] = $billing;
            }
        }

        $this->keepTogether($this->contractBlockHeight(count($facts), $current, $future));

        $this->printSectionHeading(strtr($this->overviewText('sections.contract'), [
            '{number}' => (string)$contract->number,
            '{service_type}' => (string)$contract->service_type?->name,
        ]));

        if ($facts !== []) {
            $this->printTable([], $facts);
            $this->Ln(1);
        }

        if ($current === [] && $future === []) {
            $this->printParagraph($this->overviewText('texts.no_services'), format: 'I');

            return;
        }

        $atTheirOwnPrice = static fn(Billing $billing): bool => $billing->price !== null;

        if ($current !== []) {
            $total = $this->printServices(
                array_filter($current, fn(Billing $billing): bool => !$atTheirOwnPrice($billing)),
                array_filter($current, $atTheirOwnPrice),
                [],
                [],
                $day,
                '',
            );

            // A single service without a discount already states its own sum.
            if (count($current) > 1 || !$total->equals($current[0]->sum)) {
                $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X, lnAfter: 0.5);
                $this->SetFont(self::FONT_FAMILY, 'B', self::BODY_FONT_SIZE);
                $this->Cell(140, self::LINE_HEIGHT, $this->overviewText('labels.contract_total'));
                $this->Cell(35, self::LINE_HEIGHT, Number::currency($total->toFloat()), align: 'R');
                $this->Ln();
                $this->Ln();
            }
        }

        // Shown so the customer knows what is coming, set apart and left out of the sum, which is
        // what the contract costs today.
        $this->printServices(
            [],
            [],
            array_filter($future, fn(Billing $billing): bool => !$atTheirOwnPrice($billing)),
            array_filter($future, $atTheirOwnPrice),
            $day,
            'I',
        );
    }

    /**
     * Heads a table of services inside a contract's block.
     *
     * Set in the body's own size and indent, under the contract's heading rather than beside it.
     *
     * @param string $text The heading
     * @param string $format Additional font format
     * @return void
     */
    protected function printServicesHeading(string $text, string $format): void
    {
        $this->keepTogether(self::HEADING_ORPHAN_GUARD);

        $this->SetFont(self::FONT_FAMILY, 'B' . $format, self::BODY_FONT_SIZE);
        $this->Cell(self::TEXT_WIDTH, self::LINE_HEIGHT, $text);
        $this->Ln();

        $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X, lnAfter: 0.8);
    }

    /**
     * Where the contract is provided, since when it holds and for how long.
     *
     * Read off the version that holds today, and only once it is signed: until then the contract
     * is not concluded, whatever its dates say. A service that keeps no versions has only the day
     * it was installed to go by.
     *
     * @param \App\Model\Entity\Contract $contract The contract, with its versions
     * @param \Cake\I18n\Date $day The day the list is drawn up on
     * @return list<list<array{label: string, value: string, label_width: int}>>
     */
    private function contractFacts(Contract $contract, Date $day): array
    {
        $fact = static fn(string $label, string $value): array => [
            ['label' => $label, 'value' => $value, 'label_width' => 40],
        ];

        $facts = [];
        if ($contract->installation_address !== null) {
            $facts[] = $fact(
                $this->overviewText('labels.installation_address'),
                (string)$contract->installation_address->full_address,
            );
        }

        if (!($contract->service_type->have_contract_versions ?? true)) {
            if ($contract->installation_date !== null) {
                $facts[] = $fact(
                    $this->overviewText('labels.validity'),
                    $this->period($contract->installation_date, $contract->termination_date),
                );
            }

            return $facts;
        }

        $version = $this->versionInForce($contract, $day);
        if ($version?->conclusion_date === null) {
            $facts[] = $fact($this->overviewText('labels.validity'), $this->overviewText('texts.not_concluded'));

            return $facts;
        }

        $facts[] = $fact($this->label('conclusion_date'), (string)$version->conclusion_date);
        $facts[] = $fact(
            $this->overviewText('labels.validity'),
            $this->period($version->valid_from, $contract->termination_date ?? $version->valid_until),
        );

        try {
            $facts[] = $fact($this->overviewText('labels.duration'), $this->contractDuration($version));
        } catch (InvalidArgumentException) {
            // A term the records cannot put into words is left unsaid rather than guessed at.
        }

        return $facts;
    }

    /**
     * A span of days in words, open-ended where it has no end.
     *
     * @param \Cake\I18n\Date $from First day
     * @param \Cake\I18n\Date|null $until Last day
     * @return string
     */
    private function period(Date $from, ?Date $until): string
    {
        $said = strtr($this->billingText('from'), ['{date}' => (string)$from]);

        return $until === null
            ? $said
            : $said . ' ' . strtr($this->billingText('until'), ['{date}' => (string)$until]);
    }

    /**
     * Roughly how tall a contract's block comes out, so that it can be kept on one page.
     *
     * Erring on the tall side costs a little white space at the foot of a page, while erring the
     * other way splits the block anyway.
     *
     * @param int $facts How many rows of facts
     * @param list<\App\Model\Entity\Billing> $current The services provided today
     * @param list<\App\Model\Entity\Billing> $future Those still to start
     * @return float In mm
     */
    private function contractBlockHeight(int $facts, array $current, array $future): float
    {
        // Each table the services split into: a heading, the row naming the columns, the air after
        // it, and a row for every service and every discount on one.
        $tables = static function (array $billings): float {
            $height = 0.0;
            foreach ([true, false] as $pricelist) {
                $rows = 0;
                foreach ($billings as $billing) {
                    if (($billing->price === null) === $pricelist) {
                        $rows += 1
                            + ($billing->percentage_discount_sum->isPositive() ? 1 : 0)
                            + ($billing->fixed_discount_sum->isPositive() ? 1 : 0);
                    }
                }
                if ($rows > 0) {
                    $height += 5.0 + ($rows + 2) * self::LINE_HEIGHT;
                }
            }

            return $height;
        };

        $height = 10.0 + $facts * 4.5 + 1.0;

        if ($current === [] && $future === []) {
            return $height + 2 * self::LINE_HEIGHT;
        }

        // The sum under what is running.
        if ($current !== []) {
            $height += $tables($current) + 2 * self::LINE_HEIGHT;
        }

        return $height + $tables($future);
    }

    /**
     * The version of the contract that holds on the day, or the next one where none does yet.
     *
     * @param \App\Model\Entity\Contract $contract The contract, with its versions
     * @param \Cake\I18n\Date $day The day
     * @return \App\Model\Entity\ContractVersion|null
     */
    private function versionInForce(Contract $contract, Date $day): ?ContractVersion
    {
        $next = null;

        foreach ($contract->contract_versions ?? [] as $version) {
            if ($version->valid_from > $day) {
                if ($next === null || $version->valid_from < $next->valid_from) {
                    $next = $version;
                }

                continue;
            }

            if ($version->valid_until === null || $version->valid_until >= $day) {
                return $version;
            }
        }

        return $next;
    }

    /**
     * How the whole of it is paid: each invoice the customer is sent every month, what it comes
     * to, when it falls due and where it goes.
     *
     * @param \App\Model\Entity\Customer $customer The customer, with their contracts
     * @param \Cake\I18n\Date $day The day the list is drawn up on
     * @return void
     */
    private function printMonthlyPayments(Customer $customer, Date $day): void
    {
        $this->printBillingHeading($this->paymentText('heading'), '');

        $periods = $this->paymentPeriods($customer, $day);

        if (count($periods) === 1 && $periods[0]['invoices'] === []) {
            $this->printParagraph($this->overviewText('texts.no_payments'));

            return;
        }

        $reverseCharge = $customer->accounting_profile->reverse_charge;
        $shown = function (Decimal $total) use ($customer, $reverseCharge): string {
            if (!$reverseCharge) {
                return Number::currency($total->toFloat());
            }

            // A customer the tax is reverse charged to is shown the base, and the clause below
            // says why the figure is not what they will pay.
            return Number::currency(
                Billing::calcVatBaseFromTotal($total, $customer->accounting_profile->vat_rate)->toFloat(),
            ) . ' *';
        };

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->Cell(140, self::LINE_HEIGHT, $this->overviewText('labels.invoice'));
        $this->Cell(35, self::LINE_HEIGHT, $this->overviewText('labels.monthly_amount'), align: 'R');
        $this->Ln();

        foreach ($periods as $index => $period) {
            if ($index > 0) {
                $this->Ln(1.5);
            }

            $this->keepTogether((count($period['invoices']) + 1) * self::LINE_HEIGHT);
            $this->SetFont(self::FONT_FAMILY, 'I', self::BODY_FONT_SIZE);
            $this->Cell(175, self::LINE_HEIGHT, $this->period($period['from'], $period['until']));
            $this->Ln();

            if ($period['invoices'] === []) {
                $this->SetFont(self::FONT_FAMILY, 'B', self::BODY_FONT_SIZE);
                $this->Cell(140, self::LINE_HEIGHT, $this->overviewText('periods.nothing'));
                $this->Ln();
            }

            foreach ($period['invoices'] as $invoice) {
                $this->SetFont(self::FONT_FAMILY, 'B', self::BODY_FONT_SIZE);
                $this->Cell(140, self::LINE_HEIGHT, $this->invoiceName($invoice), stretch: self::STRETCH_TO_FIT);
                $this->Cell(35, self::LINE_HEIGHT, $shown($invoice->total), align: 'R');
                $this->Ln();
            }
        }

        $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X, lnAfter: 1.0);

        // How and where to pay is read as one, so it is not split across the fold.
        $this->keepTogether(8 * self::LINE_HEIGHT + 10);

        $column = self::PAGE_WIDTH / 3;

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->frameLeft();
        $this->Cell($column, self::LINE_HEIGHT, $this->label('payment_period'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, $this->label('payment_method'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, $this->overviewText('labels.payment_due'), align: 'C');
        $this->Ln();

        $this->SetFont(self::FONT_FAMILY, 'B', self::BODY_FONT_SIZE);
        $this->frameLeft();
        $this->Cell($column, self::LINE_HEIGHT, $this->label('monthly'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, $this->label('bank_transfer'), align: 'C');
        $this->Cell(
            $column,
            self::LINE_HEIGHT,
            strtr($this->overviewText('invoices.due'), [
                '{day}' => (string)($customer->individual_maturity_period ?? 10),
            ]),
            align: 'C',
        );
        $this->Ln();

        $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X, lnAfter: 1.0);

        $this->SetFont(self::FONT_FAMILY, '', self::BODY_FONT_SIZE);
        $this->frameLeft();
        $this->Cell($column, self::LINE_HEIGHT, $this->label('provider_bank'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, $this->label('provider_account'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, $this->label('variable_symbol'), align: 'C');
        $this->Ln();

        $this->SetFont(self::FONT_FAMILY, 'B', self::BODY_FONT_SIZE);
        $this->frameLeft();
        $this->Cell($column, self::LINE_HEIGHT, Settings::getString('core.company.bank_name'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, Settings::getString('core.company.bank_account_number'), align: 'C');
        $this->Cell($column, self::LINE_HEIGHT, (string)$customer->number, align: 'C');
        $this->Ln();

        $this->drawSeparator(AppPDF::SEPARATOR_OFFSET_X, lnAfter: 1.0);

        $this->SetFont(self::FONT_FAMILY, '', self::NOTE_FONT_SIZE);
        $notes = [$this->overviewText('texts.billed_in_arrears')];
        if ($reverseCharge) {
            $notes[] = $this->paymentText('reverse_charge_clause');
        }
        $notes[] = $this->paymentText('standing_order_note');

        foreach ($notes as $note) {
            $this->MultiCell(self::TEXT_WIDTH, self::LINE_HEIGHT, $note, align: 'J');
        }
    }

    /**
     * What is paid from the day on, period by period: a new one starts wherever a service starts
     * or ends, and one that would say the same as the one before it is folded into it.
     *
     * @param \App\Model\Entity\Customer $customer The customer, with their contracts
     * @param \Cake\I18n\Date $day The day the list is drawn up on
     * @return list<array{from: \Cake\I18n\Date, until: \Cake\I18n\Date|null, invoices: list<\App\Service\CustomerPrint\MonthlyInvoice>}>
     */
    private function paymentPeriods(Customer $customer, Date $day): array
    {
        $invoices = new MonthlyInvoices();
        $starts = [$day, ...$invoices->changesAfter($customer, $day)];

        $periods = [];
        $current = null;
        foreach ($starts as $index => $from) {
            $until = isset($starts[$index + 1]) ? $starts[$index + 1]->subDays(1) : null;
            $billed = $invoices->of($customer, $from);

            if ($current !== null && $this->sameInvoices($current['invoices'], $billed)) {
                $current['until'] = $until;

                continue;
            }

            if ($current !== null) {
                $periods[] = $current;
            }
            $current = ['from' => $from, 'until' => $until, 'invoices' => $billed];
        }

        // There is always the one starting on the day itself.
        $periods[] = $current;

        return $periods;
    }

    /**
     * Whether two periods ask for the same payments.
     *
     * @param list<\App\Service\CustomerPrint\MonthlyInvoice> $one One period's invoices
     * @param list<\App\Service\CustomerPrint\MonthlyInvoice> $other The other's
     * @return bool
     */
    private function sameInvoices(array $one, array $other): bool
    {
        $said = fn(array $invoices): array => array_map(
            fn(MonthlyInvoice $invoice): string => $this->invoiceName($invoice) . '=' . $invoice->total->toString(),
            $invoices,
        );

        return $said($one) === $said($other);
    }

    /**
     * What an invoice is called on the list.
     *
     * @param \App\Service\CustomerPrint\MonthlyInvoice $invoice The invoice
     * @return string
     */
    private function invoiceName(MonthlyInvoice $invoice): string
    {
        if ($invoice->billing !== null) {
            return strtr($this->overviewText('invoices.billing'), ['{name}' => (string)$invoice->billing->name]);
        }

        if ($invoice->contract !== null) {
            return strtr($this->overviewText('invoices.contract'), ['{number}' => (string)$invoice->contract->number]);
        }

        return $this->overviewText('invoices.common');
    }

    /**
     * Reads one of the list's own texts.
     *
     * @param string $key Key under the list of services block
     * @return string
     */
    private function overviewText(string $key): string
    {
        return Settings::getString('core.documents.services_overview.' . $key);
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
