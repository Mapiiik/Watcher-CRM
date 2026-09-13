<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Files\Model\Entity\DocumentationType as FilesDocumentationType;

/**
 * DocumentationType Entity
 *
 * What a kind of folder requires of the folders under it is what this application adds to the
 * shared one: a folder filed under a customer, and one filed under a connection.
 *
 * @property bool $customer_required
 * @property bool $contract_required
 */
class DocumentationType extends FilesDocumentationType
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'position' => true,
        'currently_offered' => true,
        'date_required' => true,
        'customer_required' => true,
        'contract_required' => true,
        'note' => true,
    ];
}
