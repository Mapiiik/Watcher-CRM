<?php
declare(strict_types=1);

namespace App\BulkMessages\Filter;

use App\Model\Table\CustomerMessagesTable;

/**
 * Common base for recipient filters: holds the root table used to reach
 * associations and the NMS-backed helpers.
 */
abstract class AbstractBulkRecipientFilter implements BulkRecipientFilterInterface
{
    /**
     * Warning set while loading the filter's data source, if any.
     *
     * @var string|null
     */
    protected ?string $warning = null;

    /**
     * @param \App\Model\Table\CustomerMessagesTable $customerMessages Root table used to reach associations.
     */
    public function __construct(protected readonly CustomerMessagesTable $customerMessages)
    {
    }

    /**
     * @inheritDoc
     */
    public function warning(): ?string
    {
        return $this->warning;
    }

    /**
     * @inheritDoc
     */
    public function defaultValue(): mixed
    {
        // most filters offer a choice and start out selecting nothing
        return null;
    }

    /**
     * Render a selection as "<label>: <name>, <name>" for {@see describe()}.
     *
     * Ids with no matching option are shown as-is rather than dropped: a report
     * that quietly omits part of the selection would be worse than an ugly one.
     *
     * @param string $label Human-readable filter label.
     * @param array<array-key, string> $options Option id => name map.
     * @param array<array-key, string> $ids Selected ids.
     * @return string|null
     */
    protected function describeSelection(string $label, array $options, array $ids): ?string
    {
        if ($ids === []) {
            return null;
        }

        $names = array_map(
            static fn(string $id): string => $options[$id] ?? $id,
            $ids,
        );

        return $label . ': ' . implode(', ', $names);
    }
}
