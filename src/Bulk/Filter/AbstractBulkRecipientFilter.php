<?php
declare(strict_types=1);

namespace App\Bulk\Filter;

use App\Model\Table\CustomerMessagesTable;

/**
 * Common base for recipient filters: holds the root table used to reach
 * associations and the NMS-backed helpers.
 */
abstract class AbstractBulkRecipientFilter implements BulkRecipientFilterInterface
{
    /**
     * @param \App\Model\Table\CustomerMessagesTable $customerMessages Root table used to reach associations.
     */
    public function __construct(protected readonly CustomerMessagesTable $customerMessages)
    {
    }
}
