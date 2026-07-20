<?php
declare(strict_types=1);

namespace App\BulkMessages\Filter;

/**
 * Filters customers that carry *all* of the selected labels (AND semantics),
 * mirroring the customers index filter.
 */
final class LabelsFilter extends AbstractLabelFilter
{
    /**
     * @inheritDoc
     */
    public function id(): string
    {
        return 'label_ids';
    }

    /**
     * @inheritDoc
     */
    protected function controlLabel(): string
    {
        return __('Labels (has all selected)');
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

        // customer must be linked to every selected label
        $filterQuery = $this->customerMessages->Customers->CustomerLabels
            ->find()
            ->select(['customer_id'])
            ->where(['CustomerLabels.label_id IN' => $labelIds])
            ->groupBy('CustomerLabels.customer_id')
            ->having(['COUNT(DISTINCT CustomerLabels.label_id) =' => count($labelIds)]);

        return ['Customers.id IN' => $filterQuery];
    }
}
