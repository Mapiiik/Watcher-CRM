<?php
declare(strict_types=1);

namespace Bookkeeping\Service;

use Bookkeeping\Model\Table\InvoicesTable;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Class InvoiceMapper
 *
 * Maps InvoiceDraft objects into CRM invoice entities.
 * Handles customer lookup, VS mapping and persistence.
 */
class InvoiceMapperService
{
    use LocatorAwareTrait;

    /**
     * Map invoice drafts into CRM entities and save them.
     *
     * @param list<\Bookkeeping\Model\ValueObject\InvoiceDraft> $drafts Parsed invoice drafts.
     * @return array{imported:int, created:int, modified:int, skipped:int} Import results.
     */
    public function mapAndSave(array $drafts): array
    {
        $invoicesTable = $this->fetchTable(InvoicesTable::class);

        // Load customer IDs indexed by VS offset
        $customerIds = $invoicesTable->Customers
            ->find('list', keyField: 'nid', valueField: 'id')
            ->toArray();

        $imported = 0;
        $created = 0;
        $modified = 0;

        foreach ($drafts as $draft) {
            $imported++;

            // Structural validation (handled by draft itself)
            if (!$draft->isValid()) {
                // Skip invalid rows
                continue;
            }

            // Validate variable symbol range
            $vs = $draft->variableSymbol;
            $series = (int)env('CUSTOMER_SERIES', '0');

            if (!($series < $vs && $vs < $series + 50000)) {
                // Skip invoices outside customer variable symbol range
                continue;
            }

            // Find or create invoice
            $invoice =
                $invoicesTable->find()->where(['number' => $draft->number])->first()
                ?? $invoicesTable->newEntity(['number' => $draft->number]);

            // Map fields
            $invoice->customer_id = $customerIds[$vs - $series] ?? null;
            $invoice->variable_symbol = $vs;
            $invoice->creation_date = $draft->creationDate;
            $invoice->due_date = $draft->dueDate;
            $invoice->text = $draft->text;
            $invoice->total = $draft->total;
            $invoice->debt = $draft->debt;
            $invoice->payment_date = $draft->paymentDate;

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
