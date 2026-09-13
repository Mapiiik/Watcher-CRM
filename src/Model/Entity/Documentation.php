<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Files\Model\Entity\Documentation as FilesDocumentation;

/**
 * Documentation Entity
 *
 * What a folder hangs on is what this application adds to the shared one. A folder filed under a
 * connection carries the customer as well, because the route it was filed from named both - so
 * everything about a customer is found by asking about the customer.
 *
 * @property string|null $customer_id
 * @property string|null $contract_id
 *
 * @property \App\Model\Entity\Customer $customer
 * @property \App\Model\Entity\Contract $contract
 */
class Documentation extends FilesDocumentation
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'customer_id' => true,
        'contract_id' => true,
        'documentation_type_id' => true,
        'happened_on' => true,
        'name' => true,
        'note' => true,
    ];
}
