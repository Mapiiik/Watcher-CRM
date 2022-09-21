<?php
declare(strict_types=1);

namespace App\Model\Table;

use AuditLog\Persister\TablePersister;
use CakeDC\Users\Model\Table\UsersTable;

/**
 * Users database table
 */
class AppUsersTable extends UsersTable
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        // Persisting audit log
        $this->addBehavior('AuditLog.AuditLog');
        /** @var \AuditLog\Model\Behavior\AuditLogBehavior $auditLog */
        $auditLog = $this->getBehavior('AuditLog');
        /** @var \AuditLog\Persister\TablePersister $auditLogPersister */
        $auditLogPersister = $auditLog->persister();
        $auditLogPersister->setConfig([
            'serializeFields' => false,
            'primaryKeyExtractionStrategy' => TablePersister::STRATEGY_RAW,
        ]);
    }
}
