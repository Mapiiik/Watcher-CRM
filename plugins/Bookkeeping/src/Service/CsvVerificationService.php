<?php
declare(strict_types=1);

namespace Bookkeeping\Service;

use App\Model\Entity\AccountingProfile;
use App\Model\Table\CustomersTable;
use Cake\I18n\Date;
use Laminas\Diactoros\UploadedFile;
use PhpCollective\DecimalObject\Decimal;
use RuntimeException;

/**
 * CsvVerificationService
 *
 * Responsible for validating and comparing external CSV billing data
 * against CRM billing data for a given invoiced month.
 *
 * This service:
 * - parses and validates CSV input
 * - aggregates CSV billing totals per customer
 * - compares CSV totals with CRM billing data
 *
 * It contains no UI logic and performs no redirects or flash messaging.
 */
final class CsvVerificationService
{
    /**
     * Errors
     */
    private array $errors = [];

    /**
     * Constructor
     */
    public function __construct(
        private readonly CustomersTable $customers,
    ) {
    }

    /**
     * Verify CRM billing data against CSV verification file.
     *
     * @param \Cake\I18n\Date $invoicedMonth Month being invoiced.
     * @param \App\Model\Entity\AccountingProfile $accountingProfile Selected accounting profile.
     * @param \Laminas\Diactoros\UploadedFile $csvFile Uploaded CSV file.
     * @return \Bookkeeping\Service\VerificationResult
     */
    public function verify(
        Date $invoicedMonth,
        AccountingProfile $accountingProfile,
        UploadedFile $csvFile,
    ): VerificationResult {
        $csvData = $this->parseCsv($csvFile);

        /** @var iterable<\App\Model\Entity\Customer> $customers */
        $customers = $this->customers->find(
            'billingDataForMonth',
            invoicedMonth: $invoicedMonth,
            accountingProfileId: $accountingProfile->id,
        );

        $differences = $this->compareWithCrm($customers, $csvData);

        return new VerificationResult($differences, $this->errors);
    }

    /**
     * Parse and aggregate CSV verification data.
     *
     * @return array<string, array>
     */
    private function parseCsv(UploadedFile $csvFile): array
    {
        $stream = $csvFile->getStream();
        $stream->rewind();

        $resource = $stream->detach();

        if ($resource === null) {
            throw new RuntimeException('Unable to process CSV file.');
        }

        $verificationData = [];

        try {
            $lineNumber = 0;
            while (($parsedLine = fgetcsv($resource, 1000, ',', '"', '\\')) !== false) {
                $lineNumber++;

                // Skip empty lines
                if (array_filter($parsedLine) === []) {
                    continue;
                }

                $result = $this->validateAndProcessCsvLine($parsedLine, $lineNumber);
                if ($result === null) {
                    continue;
                }

                $customerNumber = $result['customerNumber'];
                $item = $result['item'];

                if (!isset($verificationData[$customerNumber])) {
                    $verificationData[$customerNumber]['csv']['total'] = Decimal::create(0, 2);
                    $verificationData[$customerNumber]['csv']['items'] = [];
                }

                $verificationData[$customerNumber]['csv']['total'] =
                    $verificationData[$customerNumber]['csv']['total']->add($item->period_total);

                $verificationData[$customerNumber]['csv']['items'][] = $item;
            }
        } finally {
            fclose($resource);
        }

        return $verificationData;
    }

    /**
     * Validate and normalize a single CSV line.
     *
     * @param array $parsedLine Raw CSV row.
     * @return array{
     *   customerNumber: string,
     *   item: object{period_total: \PhpCollective\DecimalObject\Decimal, name: string}
     * }|null Normalized data or null if invalid.
     */
    private function validateAndProcessCsvLine(array $parsedLine, int $lineNumber): ?array
    {
        $customerNumber = isset($parsedLine[0]) ? trim((string)$parsedLine[0]) : null;
        $periodTotalRaw = isset($parsedLine[1]) ? trim((string)$parsedLine[1]) : null;
        $name = isset($parsedLine[2]) ? trim((string)$parsedLine[2]) : '';

        $periodTotalNormalized = $periodTotalRaw !== null
            ? str_replace(',', '.', $periodTotalRaw)
            : null;

        if (!is_numeric($customerNumber)) {
            $this->errors[] = [
                'line' => $lineNumber,
                'message' => __d('bookkeeping', 'Invalid customer number'),
                'value' => $parsedLine[0] ?? '',
            ];

            return null;
        }

        if (!is_numeric($periodTotalNormalized)) {
            $this->errors[] = [
                'line' => $lineNumber,
                'message' => __d('bookkeeping', 'Invalid price'),
                'value' => $parsedLine[1] ?? '',
            ];

            return null;
        }

        return [
            'customerNumber' => $customerNumber,
            'item' => (object)[
                'period_total' => Decimal::create($periodTotalNormalized, 2),
                'name' => $name,
            ],
        ];
    }

    /**
     * Compare CSV billing data with CRM billing data.
     *
     * @param iterable<\App\Model\Entity\Customer> $customers
     * @return array<string, array{
     *   csv?: array{total: \PhpCollective\DecimalObject\Decimal, items: array},
     *   crm?: array{total: \PhpCollective\DecimalObject\Decimal, items: array},
     *   customer?: \App\Model\Entity\Customer
     * }>
     */
    private function compareWithCrm(iterable $customers, array $csvData): array
    {
        foreach ($customers as $customer) {
            $billingData['total'] = Decimal::create(0, 2);
            $billingData['items'] = [];

            foreach ($customer->contracts as $contract) {
                foreach ($contract->billings as $billing) {
                    $billingData['total'] = $billingData['total']->add($billing->period_total);
                    $billingData['items'][] = $billing;
                }
            }

            if (isset($csvData[$customer->number])) {
                if ($csvData[$customer->number]['csv']['total'] == $billingData['total']) {
                    unset($csvData[$customer->number]);
                } else {
                    $csvData[$customer->number]['customer'] = $customer;
                    $csvData[$customer->number]['crm'] = $billingData;
                }
            } elseif (!$billingData['total']->isZero()) {
                $csvData[$customer->number]['customer'] = $customer;
                $csvData[$customer->number]['crm'] = $billingData;
            }

            unset($billingData);
        }

        return $csvData;
    }
}
