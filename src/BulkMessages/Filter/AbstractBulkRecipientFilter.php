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
}
