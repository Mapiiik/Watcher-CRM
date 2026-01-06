<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Eurofaktura;

use App\Model\Entity\AccountingProfile;
use App\Model\Entity\Billing;
use App\Model\Entity\Customer;
use Bookkeeping\Model\ValueObject\InvoiceDraft;
use Cake\I18n\Date;
use PhpCollective\DecimalObject\Decimal;
use Settings\Utility\Settings;

/**
 * Builds request payloads for Eurofaktura / E-racuni API.
 *
 * This class is responsible only for mapping domain objects
 * to API-compatible request structures.
 */
class JsonRequestBuilder
{
    /**
     * Build SalesInvoice object.
     *
     * @param \Bookkeeping\Model\ValueObject\InvoiceDraft $invoice
     * @param \Cake\I18n\Date $invoicedMonth
     * @param \App\Model\Entity\AccountingProfile $accountingProfile
     * @return array
     */
    public function buildSalesInvoice(
        InvoiceDraft $invoice,
        Date $invoicedMonth,
        AccountingProfile $accountingProfile,
    ): array {
        // Load provider config
        $city = Settings::getString(EurofakturaProvider::SETTINGS_ROOT . '.issuer.city', 'Makarska');
        $businessUnit = Settings::getString(EurofakturaProvider::SETTINGS_ROOT . '.issuer.business_unit', 'POSL1');
        $businessYearFormat = Settings::getString(
            EurofakturaProvider::SETTINGS_ROOT . '.issuer.business_year_format',
            'yyyy',
        );

        $currency = Settings::getString(EurofakturaProvider::SETTINGS_ROOT . '.document.currency', 'EUR');
        $language = Settings::getString(EurofakturaProvider::SETTINGS_ROOT . '.document.language', 'Croatian');

        $status = Settings::getString(
            EurofakturaProvider::SETTINGS_ROOT . '.invoice.status',
            'Draft',
        );

        $vatClause = Settings::getString(
            EurofakturaProvider::SETTINGS_ROOT . '.vat.clause',
            'Registered',
        );
        $vatTypeStandard = Settings::getString(
            EurofakturaProvider::SETTINGS_ROOT . '.vat.transaction_type.standard',
            '0',
        );
        $vatTypeReverseCharge = Settings::getString(
            EurofakturaProvider::SETTINGS_ROOT . '.vat.transaction_type.reverse_charge',
            '14',
        );

        $invoiceTypeStandard = Settings::getString(
            EurofakturaProvider::SETTINGS_ROOT . '.invoice_type.standard',
            'Retail',
        );
        $invoiceTypeRC = Settings::getString(
            EurofakturaProvider::SETTINGS_ROOT . '.invoice_type.reverse_charge',
            'Gross',
        );

        $paymentMethod = Settings::getString(EurofakturaProvider::SETTINGS_ROOT . '.payment.method', 'BankTransfer');
        $referencePrefix = Settings::getString(EurofakturaProvider::SETTINGS_ROOT . '.payment.reference_prefix', '00 ');

        $useBuyerCode = (bool)Settings::get(EurofakturaProvider::SETTINGS_ROOT . '.customers.use_buyer_code', false);
        $buyerCodePrefix = Settings::getString(EurofakturaProvider::SETTINGS_ROOT . '.customers.code_prefix', 'CRM-');

        // Build payload
        $payload = [
            'status' => $status,
            'costPosition' => $accountingProfile->activity_code,

            // Issuer context
            'city' => $city,
            'businessUnit' => $businessUnit,
            'businessYear' => $invoicedMonth->lastOfMonth()->i18nFormat($businessYearFormat),

            // Document metadata
            'documentCurrency' => $currency,
            'documentLanguage' => $language,

            // Invoice dates
            #'date' => $invoice->creationDate->i18nFormat('yyyy-MM-dd'), // Backdating is not allowed
            'paymentDueDate' => $invoice->dueDate->i18nFormat('yyyy-MM-dd'),
            'dateOfSupplyFrom' => $invoicedMonth->firstOfMonth()->i18nFormat('yyyy-MM-dd'),
            'dateOfSupplyUntil' => $invoicedMonth->lastOfMonth()->i18nFormat('yyyy-MM-dd'),

            // VAT
            'vatOutgoingDocumentVatClause' => $vatClause,
            'vatTransactionType' => $accountingProfile->reverse_charge ? $vatTypeReverseCharge : $vatTypeStandard,

            // Buyer
            'buyerName' => $invoice->customer->billing_address->company
                ?? $invoice->customer->billing_address->full_name
                ?? '',
            'buyerName1' => $invoice->customer->billing_address->company ?
                ($invoice->customer->billing_address->full_name ?? '')
                : '',
            'buyerStreet' => $invoice->customer->billing_address->street_and_number ?? '',
            'buyerPostalCode' => $invoice->customer->billing_address->zip ?? '',
            'buyerCity' => $invoice->customer->billing_address->city ?? '',
            'buyerCountry' => $invoice->customer->billing_address->country->code ?? '',
            'buyerEMail' => $invoice->customer->billing_emails[0]->email ?? '',
            'buyerPhone' => $invoice->customer->billing_phones[0]->phone ?? '',

            // Intro text
            'introductionText' => $invoice->text,

            // Payment
            'methodOfPayment' => $paymentMethod,
            'bankAccountNumber' => $accountingProfile->bank_account_code,
            'reference' => $referencePrefix . (string)$invoice->variableSymbol,

            // Contract / subscription context
            'type' => $accountingProfile->reverse_charge ? $invoiceTypeRC : $invoiceTypeStandard,
            'contractNumber' => (string)$invoice->variableSymbol,
            'ourContractNumber' => (string)$invoice->variableSymbol,

            // Items
            'Items' => $this->buildSalesInvoiceItems($invoice, $invoicedMonth, $accountingProfile),
        ];

        // Buyer code
        if ($useBuyerCode) {
            $payload['buyerCode'] = $buyerCodePrefix . $invoice->customer->number;
        }

        // ID / VAT registration number
        if ($invoice->customer->vat_number !== null || $invoice->customer->identity_number !== null) {
            $payload['buyerTaxNumber'] = $invoice->customer->vat_number ?? $invoice->customer->identity_number;
        }

        // Reverse charge country
        if ($accountingProfile->reverse_charge) {
            $payload['vatCountryIsoCode'] = $invoice->customer->billing_address->country->code ?? '';
        }

        return $payload;
    }

    /**
     * Build invoice items.
     *
     * @param \Bookkeeping\Model\ValueObject\InvoiceDraft $invoice
     * @return array
     */
    private function buildSalesInvoiceItems(
        InvoiceDraft $invoice,
        Date $invoicedMonth,
        AccountingProfile $accountingProfile,
    ): array {
        $defaultClassificationCode = Settings::getString(
            EurofakturaProvider::SETTINGS_ROOT . '.items.default_classification_code',
            'K61.10.00',
        );

        $items = [];

        // No itemized breakdown → single-line invoice
        if (empty($invoice->items)) {
            $items[] = [
                'quantity' => 1,
                'description' => $invoice->text,
                'price' => $invoice->total?->toFloat() ?? 0.0,
                'netPrice' => Billing::calcVatBaseFromTotal(
                    $invoice->total ?? new Decimal(0, 2),
                    $accountingProfile->vat_rate,
                )->toFloat(),
                'classificationCode' => $defaultClassificationCode,
            ];

            return $items;
        }

        // Itemized invoice
        foreach ($invoice->items as $item) {
            $line = [
                'quantity' => 1, // calculated price is for all items (1 month - accounting unit)
                'price' => $item->period_total->toFloat(),
                'netPrice' => Billing::calcVatBaseFromTotal(
                    $item->period_total,
                    $accountingProfile->vat_rate,
                )->toFloat(),
            ];

            if ($item->service?->accounting_product_code !== null) {
                $line['productCode'] = $item->service->accounting_product_code;
                $line['productName'] = $item->name; // mame contains quantity if >1
            } else {
                $line['description'] = $item->name; // mame contains quantity if >1
                $line['classificationCode'] = $defaultClassificationCode;
            }

            $items[] = $line;
        }

        return $items;
    }

    /**
     * Build Partner object.
     *
     * Eurofaktura supports only a single phone/email per partner/address.
     * We intentionally select the first valid phone/email and ignore the rest
     *
     * @param \App\Model\Entity\Customer $customer
     * @return array
     */
    public function buildPartner(Customer $customer): array
    {
        $buyerCodePrefix = Settings::getString(EurofakturaProvider::SETTINGS_ROOT . '.customers.code_prefix', 'CRM-');

        // Stable partner / buyer code
        $buyerCode = $buyerCodePrefix . $customer->number;

        // Base partner payload
        $partner = [
            'partnerCode' => $buyerCode,
            'eMail' => $customer->emails[0]->email ?? '',

            'BuyerData' => [
                'buyerCode' => $buyerCode,
                'contractNumber' => $customer->number,
                'status' => 'defaultBuyer',
                'sendDocumentsViaPost' => false,
                'sendDocumentsViaEmail' => true,
            ],
        ];

        // Legal entity vs natural person
        if (!empty($customer->company)) {
            $partner['companyName'] = $customer->company;
            $partner['companyType'] = 'Organization';
        }

        if (!empty($customer->first_name)) {
            $partner['firstName'] = $customer->first_name;
        }

        if (!empty($customer->last_name)) {
            $partner['lastName'] = $customer->last_name;
        }

        // Identification numbers
        if (!empty($customer->identity_number)) {
            $partner['personalID'] = $customer->identity_number; // taxID - not working for OIB
        }

        if (!empty($customer->vat_number)) {
            $partner['vatID'] = $customer->vat_number;
        }

        // Date of Birth
        if (!empty($customer->date_of_birth)) {
            $partner['dateOfBirth'] = $customer->date_of_birth->i18nFormat('yyyy-MM-dd');
        }

        // Addresses
        $primaryAddress = $customer->permanent_address ?? $customer->billing_address;
        $invoicingAddress = $customer->billing_address;
        $partner['Addresses'] = [];
        if ($primaryAddress !== null) {
            $partner['Addresses'][] = [
                'type' => 'Primary',
                'firstAddressLine' => $primaryAddress->company
                    ?? $primaryAddress->full_name
                    ?? '',
                'additionalLine' => $primaryAddress->company ?
                    ($primaryAddress->full_name ?? '')
                    : '',
                'street' => trim($primaryAddress->street_and_number),
                'postalCode' => $primaryAddress->zip,
                'city' => $primaryAddress->city,
                'country' => $primaryAddress->country->code ?? '',
                'eMail' => $customer->emails[0]->email ?? '',
                'telephone' => $customer->phones[0]->phone ?? '',
            ];
        }
        if ($invoicingAddress !== null) {
            $partner['Addresses'][] = [
                'type' => 'Invoicing',
                'firstAddressLine' => $invoicingAddress->company
                    ?? $invoicingAddress->full_name
                    ?? '',
                'additionalLine' => $invoicingAddress->company ?
                    ($invoicingAddress->full_name ?? '')
                    : '',
                'street' => trim($invoicingAddress->street_and_number),
                'postalCode' => $invoicingAddress->zip,
                'city' => $invoicingAddress->city,
                'country' => $invoicingAddress->country->code ?? '',
                'eMail' => $customer->billing_emails[0]->email ?? '',
                'telephone' => $customer->billing_phones[0]->phone ?? '',
            ];
        }

        // Bank Accounts
        if ($customer->bank_account) {
            $bankAccount = [
                'accountNumber' => $customer->bank_account,
            ];

            if ($customer->bank_code) {
                $bankAccount['accountNumber'] .= '/' . $customer->bank_code;
            }

            if ($customer->bank_name) {
                $bankAccount['bank'] = $customer->bank_name;
            }

            $partner['BankAccounts'] = [$bankAccount];
        }

        // Final Partner payload
        return $partner;
    }
}
