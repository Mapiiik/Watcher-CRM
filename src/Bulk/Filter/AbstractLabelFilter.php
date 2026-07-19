<?php
declare(strict_types=1);

namespace App\Bulk\Filter;

use Cake\Validation\Validation;

/**
 * Common base for the (multiselect) label filters.
 */
abstract class AbstractLabelFilter extends AbstractBulkRecipientFilter
{
    /**
     * Label shown above the multiselect control.
     *
     * @return string
     */
    abstract protected function controlLabel(): string;

    /**
     * @inheritDoc
     */
    public function controls(mixed $value): array
    {
        return [
            [
                'name' => $this->id(),
                'options' => [
                    'label' => $this->controlLabel(),
                    'options' => $this->labelOptions(),
                    'multiple' => true,
                    'empty' => false,
                    'val' => is_array($value) ? $value : [],
                ],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function buildValue(array $data): mixed
    {
        $ids = $this->labelIds($data[$this->id()] ?? null);

        return $ids === [] ? null : $ids;
    }

    /**
     * Available labels as a value => name list.
     *
     * @return array<array-key, string>
     */
    protected function labelOptions(): array
    {
        return $this->customerMessages->Customers->CustomerLabels->Labels
            ->find('list', order: ['name'])
            ->toArray();
    }

    /**
     * Normalise a submitted value to a list of valid label uuids.
     *
     * @param mixed $value Submitted filter value.
     * @return array<string>
     */
    protected function labelIds(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            $value,
            static fn(mixed $id): bool => is_string($id) && Validation::uuid($id),
        ));
    }
}
