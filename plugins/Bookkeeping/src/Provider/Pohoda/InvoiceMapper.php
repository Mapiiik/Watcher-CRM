<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Pohoda;

use Bookkeeping\Model\Table\InvoicesTable;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Class InvoiceMapper
 *
 * Maps parsed Pohoda invoice data into CRM invoice entities.
 * Handles customer lookup, VS mapping, totals, dates and persistence.
 */
class InvoiceMapper
{
    use LocatorAwareTrait;

    /**
     * Map parsed invoice data into CRM entities and save them.
     *
     * @param array $parsedInvoices Parsed invoice data from XmlParser.
     * @return array Mapping results: ['imported' => X, 'created' => Y, 'modified' => Z, 'skipped' => W]
     */
    public function mapAndSave(array $parsedInvoices): array
    {
        /** @var \Bookkeeping\Model\Table\InvoicesTable $invoicesTable */
        $invoicesTable = $this->fetchTable(InvoicesTable::class);

        // Load customer IDs indexed by VS offset
        $customerIds = $invoicesTable->Customers
            ->find('list', keyField: 'nid', valueField: 'id')
            ->toArray();

        $imported = 0;
        $created = 0;
        $modified = 0;

        foreach ($parsedInvoices as $data) {
            $imported++;

            // Validate required fields
            if (
                !isset(
                    $data['numberRequested'],
                    $data['symVar'],
                    $data['date'],
                    $data['dateDue'],
                    $data['text'],
                    $data['totalAmount'],
                    $data['remainingDebt'],
                )
            ) {
                // Skip invalid rows
                continue;
            }

            // Validate VS range
            $vs = (int)$data['symVar'];
            $series = (int)env('CUSTOMER_SERIES', '0');

            if (!($series < $vs && $vs < $series + 50000)) {
                // Skip invoices outside customer VS range
                continue;
            }

            // Find or create invoice
            $invoice =
                $invoicesTable->find()->where(['number' => $data['numberRequested']])->first()
                ?? $invoicesTable->newEntity(['number' => $data['numberRequested']]);

            // Map fields
            $invoice->customer_id = $customerIds[$vs - $series] ?? null;
            $invoice->variable_symbol = $vs;
            $invoice->creation_date = $data['date'];
            $invoice->due_date = $data['dateDue'];
            $invoice->text = $data['text'];
            $invoice->total = $data['totalAmount'];
            $invoice->debt = $data['remainingDebt'];
            $invoice->payment_date = $data['liquidationDate'] ?: null;

            // Count stats
            if ($invoice->isNew()) {
                $created++;
            } else {
                $modified++;
            }

            // Save
            $invoicesTable->saveOrFail($invoice);
        }

        return [
            'imported' => $imported,
            'created' => $created,
            'modified' => $modified,
            'skipped' => $imported - $created - $modified,
        ];
    }
}
