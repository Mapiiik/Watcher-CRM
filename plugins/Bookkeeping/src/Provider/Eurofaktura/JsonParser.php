<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Eurofaktura;

use Bookkeeping\Model\ValueObject\InvoiceDraft;
use Cake\I18n\Date;
use PhpCollective\DecimalObject\Decimal;
use RuntimeException;
use Throwable;

final class JsonParser
{
    /**
     * Parse SalesInvoiceList response.
     *
     * @param array $json Raw API response
     * @return list<\Bookkeeping\Model\ValueObject\InvoiceDraft>
     */
    public function parseSalesInvoiceList(array $json): array
    {
        $response = $json['response'] ?? null;

        if (!is_array($response)) {
            throw new RuntimeException(
                __d(
                    'bookkeeping',
                    'Invalid response structure received from Eurofaktura API.',
                ),
            );
        }

        // noDocumentsFound → valid empty result
        if (
            ($response['status'] ?? null) === 'error'
            && str_contains(
                (string)($response['description'] ?? ''),
                'noDocumentsFound',
            )
        ) {
            return [];
        }

        if (($response['status'] ?? null) !== 'ok') {
            throw new RuntimeException(
                __d(
                    'bookkeeping',
                    'Eurofaktura API error: {0}',
                    [
                        $response['description'] ?? __d('bookkeeping', 'Unknown error'),
                    ],
                ),
            );
        }

        $items = $response['result'] ?? [];

        if (!is_array($items)) {
            throw new RuntimeException(
                __d(
                    'bookkeeping',
                    'Invalid invoice list received from Eurofaktura API.',
                ),
            );
        }

        $drafts = [];

        foreach ($items as $item) {
            $drafts[] = $this->parseSalesInvoice($item);
        }

        return $drafts;
    }

    /**
     * Parse single sales invoice JSON into InvoiceDraft.
     */
    private function parseSalesInvoice(array $data): InvoiceDraft
    {
        $draft = new InvoiceDraft();

        // Number
        $draft->number = $data['number'] ?? null;

        // Variable symbol
        $draft->variableSymbol = $data['reference'] ?? null;

        // Customer number
        $draft->customerNumber = $data['ourContractNumber']
            ?? $data['contractNumber']
            ?? preg_replace('~\D~', '', (string)$data['reference'])
            ?? null;

        // Accounting ID
        $draft->accountingIdentifier = $data['documentID'] ?? null;

        // Dates
        $draft->creationDate = $this->parseDate($data['date'] ?? null);
        $draft->dueDate = $this->parseDate($data['paymentDueDate'] ?? null);
        $draft->paymentDate = $this->getLatestPaymentDate(
            $data['PaymentRecords'] ?? [],
        );

        // Text
        $draft->text = $this->resolveText($data);

        // Amounts
        if (isset($data['documentAmount'])) {
            $draft->total = new Decimal((string)$data['documentAmount']);
        }

        if (isset($data['amountLeftToBePaid'])) {
            $draft->debt = new Decimal((string)$data['amountLeftToBePaid']);
        }

        // Provider-specific metadata
        $draft->metadata = [
            'source' => 'eurofaktura',
            'documentID' => $data['documentID'] ?? null,
            'buyerCode' => $data['buyerCode'] ?? null,
            'currency' => $data['documentCurrency'] ?? null,
            'status' => $data['status'] ?? null,
            'issuedTimestamp' => $data['issuedTimestamp'] ?? null,
            //'raw' => $data,
        ];

        // Warnings
        if ($draft->paymentDate === null && ($draft->debt?->isZero() ?? false)) {
            $draft->addWarning('Invoice marked as paid but payment date is missing');
        }

        return $draft;
    }

    /**
     * Parse date string into Cake Date object.
     *
     * @param string|null $value
     * @return \Cake\I18n\Date|null
     */
    private function parseDate(?string $value): ?Date
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new Date($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Parse sales invoice texts and items into string.
     */
    private function resolveText(array $data): ?string
    {
        $texts = [];

        if (!empty($data['introductionText'])) {
            $texts[] = $data['introductionText'];
        }

        if (!empty($data['Items'])) {
            foreach ($data['Items'] as $item) {
                $texts[] =
                    ((int)$item['quantity'] <> 1 ? $item['quantity'] . 'x ' : '')
                    . ($item['productName'] ?? $item['description']);
            }
        }

        $text = implode(', ', $texts);

        return $text == '' ? null : $text;
    }

    /**
     * Returns the latest (maximum) payment date from payment records.
     *
     * The method iterates over all payment records and determines
     * the most recent payment date. Records without a paymentDate
     * are ignored.
     *
     * This represents the date of the last payment made for the document,
     * not the document issue date or due date.
     *
     * @param array<int, array<string, mixed>> $paymentRecords
     *     List of payment records as returned by the API.
     * @return \Cake\I18n\Date|null
     *     Date of the latest payment, or null if no valid payment date exists.
     */
    private function getLatestPaymentDate(array $paymentRecords): ?Date
    {
        $latest = null;

        foreach ($paymentRecords as $record) {
            if (empty($record['paymentDate'])) {
                continue;
            }

            $date = $this->parseDate($record['paymentDate']);

            if ($date === null) {
                continue;
            }

            if ($latest === null || $date > $latest) {
                $latest = $date;
            }
        }

        return $latest;
    }
}
