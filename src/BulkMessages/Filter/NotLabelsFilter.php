<?php
declare(strict_types=1);

namespace App\BulkMessages\Filter;

/**
 * Excludes customers that carry *any* of the selected labels, mirroring the
 * customers index "not labels" filter.
 */
final class NotLabelsFilter extends AbstractLabelFilter
{
    /**
     * @inheritDoc
     */
    public function id(): string
    {
        return 'not_label_ids';
    }

    /**
     * @inheritDoc
     */
    protected function controlLabel(): string
    {
        return __('Exclude labels (has none selected)');
    }

    /**
     * @inheritDoc
     */
    public function conditions(mixed $value): ?array
    {
        $labelIds = $this->labelIds($value);
        if ($labelIds === []) {
            return null;
        }

        // customer must not be linked to any of the selected labels
        $filterQuery = $this->customerMessages->Customers->CustomerLabels
            ->find()
            ->select(['customer_id'])
            ->distinct()
            ->where(['CustomerLabels.label_id IN' => $labelIds]);

        return ['Customers.id NOT IN' => $filterQuery];
    }
}
