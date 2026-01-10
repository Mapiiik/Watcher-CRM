<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Pohoda;

use Bookkeeping\Model\ValueObject\InvoiceDraft;
use Cake\I18n\Date;
use PhpCollective\DecimalObject\Decimal;
use RuntimeException;

/**
 * Class DbfParser
 *
 * Responsible for parsing DBF invoice files exported from Pohoda.
 * Converts DBF records into InvoiceDraft objects.
 *
 * This parser performs no validation or persistence logic.
 * It only reads, normalizes and maps DBF data into a domain-neutral structure.
 */
final class DbfParser
{
    /**
     * Parse invoices from DBF file.
     *
     * @param string $filePath Path to uploaded DBF file.
     * @return list<\Bookkeeping\Model\ValueObject\InvoiceDraft>
     */
    public function parseFile(string $filePath): array
    {
        $drafts = [];

        $dbase = dbase_open($filePath, 0);
        if ($dbase === false) {
            throw new RuntimeException(__d('bookkeeping', 'Unable to open DBF file.'));
        }

        $recordCount = dbase_numrecords($dbase);

        for ($recordNumber = 1; $recordNumber <= $recordCount; $recordNumber++) {
            $record = dbase_get_record_with_names($dbase, $recordNumber);

            $record = $this->normalizeRecord($record);

            $draft = new InvoiceDraft();

            // Invoice number
            $draft->number = $record['CISLO'] ?? null;

            // Variable symbol
            $draft->variableSymbol = $record['VARSYM'] ?? null;

            // Customer number
            $draft->customerNumber = $record['VARSYM'] ?? null;

            // Dates
            $draft->creationDate = $this->parseDate($record['DATUM'] ?? null);
            $draft->dueDate = $this->parseDate($record['DATSPLAT'] ?? null);
            $draft->paymentDate = $this->parseDate($record['DATLIKV'] ?? null);

            // Text
            $draft->text = $record['STEXT'] ?? null;

            // Amounts
            $draft->total = Decimal::create(
                (string)($record['KCCELKEM'] ?? 0),
                2,
            );

            $draft->debt = Decimal::create(
                (string)($record['KCLIKV'] ?? 0),
                2,
            );

            // Optional metadata
            $draft->metadata['source'] = 'pohoda-dbf';
            $draft->metadata['record'] = $recordNumber;

            $drafts[] = $draft;
        }

        /** @psalm-suppress UnusedFunctionCall */
        dbase_close($dbase);

        return $drafts;
    }

    /**
     * Normalize DBF record values.
     *
     * Converts encoding to UTF-8 and trims string values.
     *
     * @param array<string, mixed> $record
     * @return array<string, mixed>
     */
    private function normalizeRecord(array $record): array
    {
        foreach ($record as $key => $value) {
            if (is_string($value)) {
                $record[$key] = trim(
                    iconv('CP852', 'UTF-8', $value),
                );
            }
        }

        return $record;
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

        return new Date($value);
    }
}
